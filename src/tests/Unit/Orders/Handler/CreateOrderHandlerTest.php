<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Handler;

use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\ProductDto;
use App\Orders\Dto\Request\CreateOrderRequest;
use App\Orders\Entity\Order;
use App\Orders\Entity\OrderItem as OrderItemEntity;
use App\Orders\Exception\Domain\InvalidValueException;
use App\Orders\Exception\Domain\OrderAlreadyExistsException;
use App\Orders\Factory\OrderEntityFactory;
use App\Orders\Handler\CreateOrderHandler;
use App\Orders\Repository\OrderRepository;
use DateTimeImmutable;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreateOrderHandlerTest extends TestCase
{
    /**
     * Ověřuje, že handler vytvoří novou objednávku, když ještě neexistuje.
     * Vstup: platný CreateOrderRequest, repository vrací null.
     * Důvod: první požadavek s daným partnerId + orderId musí objednávku uložit do databáze.
     */
    public function testHappyFlowCreatesNewOrderWhenItDoesNotExist(): void
    {
        $request = self::createRequest();
        $orderEntity = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('findByPartnerAndOrderId')
            ->with('partner-1', 'order-1')
            ->willReturn(null);
        $orderRepository->expects(self::once())
            ->method('save')
            ->with($orderEntity);

        $orderEntityFactory = $this->createMock(OrderEntityFactory::class);
        $orderEntityFactory->expects(self::once())
            ->method('create')
            ->willReturn($orderEntity);

        $handler = new CreateOrderHandler($transactionManager, $orderRepository, $orderEntityFactory);
        $response = ($handler)($request);

        self::assertSame('Order was created successfully.', $response->message);
    }

    /**
     * Ověřuje, že handler vrátí úspěch bez uložení, když již existuje identická objednávka.
     * Vstup: platný CreateOrderRequest, repository vrací objednávku se shodnými položkami.
     * Důvod: opakované požadavky na vytvoření se stejným obsahem musí být idempotentní a přeskočit persistenci.
     */
    public function testHappyFlowReturnsSuccessWithoutSaveWhenSameOrderAlreadyExists(): void
    {
        $request = self::createRequest();
        $existingOrder = self::createExistingOrderWithItems([
            ['product-1', 'Product title', '19.99', 2],
        ]);

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::never())->method('transactional');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::once())
            ->method('findByPartnerAndOrderId')
            ->willReturn($existingOrder);
        $orderRepository->expects(self::never())->method('save');

        $orderEntityFactory = $this->createMock(OrderEntityFactory::class);
        $orderEntityFactory->expects(self::never())->method('create');

        $handler = new CreateOrderHandler($transactionManager, $orderRepository, $orderEntityFactory);
        $response = ($handler)($request);

        self::assertSame('Order was created successfully.', $response->message);
    }

    /**
     * Ověřuje, že handler je idempotentní podle položek a ignoruje rozdílné datum doručení v requestu.
     * Vstup: existující objednávka se shodnými položkami, request s jiným expectedDeliveryDate.
     * Důvod: isSameOrderCreated() porovnává jen položky — shodný obsah nesmí znovu persistovat entitu.
     */
    public function testHappyFlowReturnsSuccessWithoutSaveWhenSameItemsIgnoreDeliveryDate(): void
    {
        $request = self::createRequest(expectedDeliveryDate: '2026-05-25T12:00:00+00:00');
        $existingOrder = self::createExistingOrderWithItems([
            ['product-1', 'Product title', '19.99', 2],
        ]);

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::never())->method('transactional');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn($existingOrder);
        $orderRepository->expects(self::never())->method('save');

        $handler = new CreateOrderHandler($transactionManager, $orderRepository, self::createStub(OrderEntityFactory::class));
        $response = ($handler)($request);

        self::assertSame('Order was created successfully.', $response->message);
    }

    /** @return Iterator<string, array{items: list<array{string, string, string, int}>}> */
    public static function existingOrderConflictProvider(): Iterator
    {
        yield 'different product id' => [
            'items' => [
                ['product-2', 'Other product', '9.99', 1],
            ],
        ];

        yield 'different price' => [
            'items' => [
                ['product-1', 'Product title', '11.00', 2],
            ],
        ];

        yield 'different quantity' => [
            'items' => [
                ['product-1', 'Product title', '19.99', 3],
            ],
        ];

        yield 'different item count' => [
            'items' => [
                ['product-1', 'Product title', '19.99', 2],
                ['product-2', 'Product B', '10.00', 1],
            ],
        ];
    }

    /**
     * Ověřuje, že handler vyhodí OrderAlreadyExistsException, když objednávka existuje s jinými položkami.
     * Vstup: platný CreateOrderRequest, existující objednávka z existingOrderConflictProvider.
     * Důvod: konfliktní požadavky na vytvoření se stejným orderId musí být odmítnuty se sémantikou HTTP 409.
     *
     * @param list<array{string, string, string, int}> $items
     */
    #[DataProvider('existingOrderConflictProvider')]
    public function testThrowsWhenOrderExistsWithDifferentItems(array $items): void
    {
        $existingOrder = self::createExistingOrderWithItems($items);

        $orderRepository = self::createStub(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn($existingOrder);

        $handler = new CreateOrderHandler(self::createStub(TransactionManager::class), $orderRepository, self::createStub(OrderEntityFactory::class));

        try
        {
            ($handler)(self::createRequest());
            self::fail('Expected OrderAlreadyExistsException.');
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(OrderAlreadyExistsException::class, $exception);
        }
    }

    /**
     * Ověřuje, že handler vyhodí InvalidValueException, když je seznam produktů prázdný.
     * Vstup: CreateOrderRequest s prázdným polem products.
     * Důvod: každá objednávka musí podle doménových pravidel obsahovat alespoň jeden produkt.
     */
    public function testThrowsWhenProductsListIsEmpty(): void
    {
        $handler = new CreateOrderHandler(
            self::createStub(TransactionManager::class),
            self::createStub(OrderRepository::class),
            self::createStub(OrderEntityFactory::class),
        );

        try
        {
            ($handler)(self::createRequest(products: []));
            self::fail('Expected InvalidValueException.');
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(InvalidValueException::class, $exception);
            self::assertSame('Order must contain at least one product.', $exception->getMessage());
        }
    }

    /**
     * @param list<ProductDto> $products
     */
    private static function createRequest(string $expectedDeliveryDate = '2026-05-24T12:00:00+00:00', ?array $products = null): CreateOrderRequest
    {
        $request = new CreateOrderRequest();
        $request->partnerId = 'partner-1';
        $request->orderId = 'order-1';
        $request->expectedDeliveryDate = $expectedDeliveryDate;
        $request->products = $products ?? [self::createProductDto()];

        return $request;
    }

    private static function createProductDto(string $id = 'product-1', string $title = 'Product title', string $price = '19.99', int $quantity = 2): ProductDto
    {
        $product = new ProductDto();
        $product->id = $id;
        $product->title = $title;
        $product->price = $price;
        $product->quantity = $quantity;

        return $product;
    }

    /**
     * @param list<array{string, string, string, int}> $items
     */
    private static function createExistingOrderWithItems(array $items): Order
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));

        foreach ($items as [$productId, $title, $price, $quantity])
        {
            $order->addItem(new OrderItemEntity($productId, $title, $price, $quantity));
        }

        return $order;
    }
}
