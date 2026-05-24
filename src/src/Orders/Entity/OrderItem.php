<?php

declare(strict_types=1);

namespace App\Orders\Entity;

use App\Core\Trait\RecordTimestamps;
use App\Orders\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'order_items')]
#[ORM\UniqueConstraint(name: 'uq_order_items_uuid', columns: ['uuid'])]
class OrderItem
{
    use RecordTimestamps;

    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    private Order $order;

    #[ORM\Column(name: 'product_id', type: 'string', length: 64)]
    private string $productId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function __construct(string $productId, string $title, string $price, int $quantity)
    {
        $this->uuid = Uuid::v7();
        $this->productId = $productId;
        $this->title = $title;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function order(): Order
    {
        return $this->order;
    }

    public function setOrder(Order $orderEntity): self
    {
        $this->order = $orderEntity;

        return $this;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function price(): string
    {
        return $this->price;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function signature(): string
    {
        return self::signatureFromValues(
            $this->productId,
            $this->title,
            $this->price,
            $this->quantity,
        );
    }

    public static function signatureFromValues(string $productId, string $title, string $price, int $quantity): string
    {
        return implode("\0", [$productId, $title, $price, (string) $quantity]);
    }
}
