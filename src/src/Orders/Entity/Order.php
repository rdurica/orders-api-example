<?php

declare(strict_types=1);

namespace App\Orders\Entity;

use App\Core\Trait\RecordTimestamps;
use App\Orders\Repository\OrderRepository;
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
    private \DateTimeImmutable $expectedDeliveryDate;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
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

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function partnerId(): string
    {
        return $this->partnerId;
    }

    public function setPartnerId(string $partnerId): self
    {
        $this->partnerId = $partnerId;

        return $this;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function expectedDeliveryDate(): \DateTimeImmutable
    {
        return $this->expectedDeliveryDate;
    }

    public function setExpectedDeliveryDate(\DateTimeImmutable $expectedDeliveryDate): self
    {
        $this->expectedDeliveryDate = $expectedDeliveryDate;

        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function items(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item))
        {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }
}
