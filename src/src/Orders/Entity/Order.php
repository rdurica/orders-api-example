<?php

declare(strict_types=1);

namespace App\Orders\Entity;

use App\Core\Trait\RecordTimestamps;
use App\Orders\Repository\OrderRepository;
use App\Orders\Value\ExpectedDeliveryDate;
use App\Orders\Value\OrderItem as OrderItemVo;
use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_orders_partner_id', columns: ['partner_id'])]
#[ORM\Index(name: 'idx_orders_order_id', columns: ['order_id'])]
#[ORM\Table(name: 'orders')]
#[ORM\UniqueConstraint(name: 'uq_orders_partner_order', columns: ['partner_id', 'order_id'])]
#[ORM\UniqueConstraint(name: 'uq_orders_uuid', columns: ['uuid'])]
class Order
{
    use RecordTimestamps;

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(name: 'partner_id', type: 'string', length: 64)]
    private string $partnerId;

    #[ORM\Column(name: 'order_id', type: 'string', length: 64)]
    private string $orderId;

    #[ORM\Column(name: 'expected_delivery_date', type: 'datetime_immutable')]
    private DateTimeImmutable $expectedDeliveryDate;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(string $partnerId, string $orderId, DateTimeImmutable $expectedDeliveryDate)
    {
        $this->uuid = Uuid::v7();
        $this->partnerId = $partnerId;
        $this->orderId = $orderId;
        $this->expectedDeliveryDate = $expectedDeliveryDate;
        $this->items = new ArrayCollection();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function partnerId(): string
    {
        return $this->partnerId;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function expectedDeliveryDate(): DateTimeImmutable
    {
        return $this->expectedDeliveryDate;
    }

    public function setExpectedDeliveryDate(DateTimeImmutable $expectedDeliveryDate): self
    {
        $this->expectedDeliveryDate = $expectedDeliveryDate;

        return $this;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function hasSameExpectedDeliveryDateAs(ExpectedDeliveryDate $deliveryDate): bool
    {
        $currentUtc = new DateTimeImmutable($this->expectedDeliveryDate->format('Y-m-d H:i:s'), new DateTimeZone('UTC'));
        $incomingUtc = $deliveryDate->value()->setTimezone(new DateTimeZone('UTC'));

        return $currentUtc->getTimestamp() === $incomingUtc->getTimestamp();
    }

    /** @return Collection<int, OrderItem> */
    public function items(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $orderItemEntity): self
    {
        if (!$this->items->contains($orderItemEntity))
        {
            $this->items->add($orderItemEntity);
            $orderItemEntity->setOrder($this);
        }

        return $this;
    }

    /**
     * Check if order has same items as ValueObject. Compare theirs signatures.
     *
     * @param list<OrderItemVo> $orderItems
     */
    public function hasSameItemsAs(array $orderItems): bool
    {
        if (count($orderItems) !== $this->items->count())
        {
            return false;
        }

        $requestSignatures = array_map(
            static fn (OrderItemVo $orderItem): string => OrderItem::signatureFromValues(
                $orderItem->productId()->value(),
                $orderItem->title()->value(),
                $orderItem->price()->value(),
                $orderItem->quantity()->value(),
            ),
            $orderItems,
        );

        $entitySignatures = array_map(
            static fn (OrderItem $orderItemEntity): string => $orderItemEntity->signature(),
            $this->items->toArray(),
        );

        sort($requestSignatures);
        sort($entitySignatures);

        return $requestSignatures === $entitySignatures;
    }
}
