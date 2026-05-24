<?php

declare(strict_types=1);

namespace App\Tests\Unit\Orders\Handler;

use App\Core\Transaction\TransactionManager;
use App\Orders\Dto\Request\UpdateDeliveryDateRequest;
use App\Orders\Entity\Order;
use App\Orders\Exception\Domain\OrderNotFoundException;
use App\Orders\Handler\UpdateDeliveryDateHandler;
use App\Orders\Repository\OrderRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Iterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UpdateDeliveryDateHandlerTest extends TestCase
{
    /**
     * Ověřuje, že handler vyhodí OrderNotFoundException, když objednávka neexistuje.
     * Vstup: platný UpdateDeliveryDateRequest, repository vrací null.
     * Důvod: aktualizace neexistující objednávky musí selhat se sémantikou HTTP 404.
     */
    public function testThrowsWhenOrderNotFound(): void
    {
        $orderRepository = self::createStub(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn(null);

        $handler = new UpdateDeliveryDateHandler(self::createStub(TransactionManager::class), $orderRepository);

        try
        {
            ($handler)(self::createRequest('2026-05-25T12:00:00+00:00'));
            self::fail('Expected OrderNotFoundException.');
        }
        catch (Exception $exception)
        {
            self::assertInstanceOf(OrderNotFoundException::class, $exception);
        }
    }

    /** @return Iterator<string, array{storedDate: string, requestDate: string}> */
    public static function unchangedDeliveryDateProvider(): Iterator
    {
        yield 'matching rfc3339 utc' => [
            'storedDate'  => '2026-05-24 12:00:00',
            'requestDate' => '2026-05-24T12:00:00+00:00',
        ];
    }

    /**
     * Ověřuje, že handler vrátí úspěch bez uložení, když se datum doručení nezměnilo.
     * Vstup: objednávka a request se shodným formátovaným datem z unchangedDeliveryDateProvider.
     * Důvod: opakované patch požadavky se stejným datem musí být idempotentní a přeskočit persistenci.
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('unchangedDeliveryDateProvider')]
    public function testHappyFlowReturnsSuccessWithoutSaveWhenDeliveryDateIsSame(string $storedDate, string $requestDate): void
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable($storedDate));

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::never())->method('transactional');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn($order);
        $orderRepository->expects(self::never())->method('save');

        $handler = new UpdateDeliveryDateHandler($transactionManager, $orderRepository);
        $response = ($handler)(self::createRequest($requestDate));

        self::assertSame('Order delivery date was updated successfully.', $response->message);
    }

    /** @return Iterator<string, array{storedDate: string, requestDate: string, expectedDate: string}> */
    public static function changedDeliveryDateProvider(): Iterator
    {
        yield 'next day utc' => [
            'storedDate'   => '2026-05-24 12:00:00',
            'requestDate'  => '2026-05-25T12:00:00+00:00',
            'expectedDate' => '2026-05-25 12:00:00',
        ];
    }

    /**
     * Ověřuje, že handler aktualizuje a uloží objednávku, když se datum doručení liší.
     * Vstup: objednávka a request s odlišným datem z changedDeliveryDateProvider.
     * Důvod: změněné datum doručení musí být uloženo uvnitř transakce.
     *
     * @throws DateMalformedStringException
     */
    #[DataProvider('changedDeliveryDateProvider')]
    public function testHappyFlowUpdatesDeliveryDateWhenItDiffers(string $storedDate, string $requestDate, string $expectedDate): void
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable($storedDate));

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::once())
            ->method('transactional')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn($order);
        $orderRepository->expects(self::once())->method('save')->with($order);

        $handler = new UpdateDeliveryDateHandler($transactionManager, $orderRepository);
        $response = ($handler)(self::createRequest($requestDate));

        self::assertSame('Order delivery date was updated successfully.', $response->message);
        self::assertSame($expectedDate, $order->expectedDeliveryDate()->format('Y-m-d H:i:s'));
    }

    /**
     * Ověřuje, že handler vrátí úspěch bez uložení pro stejný UTC okamžik s jiným offsetem.
     * Vstup: entita "2026-05-24 12:00:00", RFC3339 "2026-05-24T14:00:00+02:00" (stejný UTC instant).
     * Důvod: idempotentní PATCH nesmí znovu persistovat ekvivalentní RFC3339 reprezentaci stejného data doručení.
     */
    public function testHappyFlowReturnsSuccessWithoutSaveWhenSameUtcInstantWithDifferentOffset(): void
    {
        $order = new Order('partner-1', 'order-1', new DateTimeImmutable('2026-05-24 12:00:00'));

        $transactionManager = $this->createMock(TransactionManager::class);
        $transactionManager->expects(self::never())->method('transactional');

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->method('findByPartnerAndOrderId')->willReturn($order);
        $orderRepository->expects(self::never())->method('save');

        $handler = new UpdateDeliveryDateHandler($transactionManager, $orderRepository);
        $response = ($handler)(self::createRequest('2026-05-24T14:00:00+02:00'));

        self::assertSame('Order delivery date was updated successfully.', $response->message);
        self::assertSame('2026-05-24 12:00:00', $order->expectedDeliveryDate()->format('Y-m-d H:i:s'));
    }

    private static function createRequest(string $expectedDeliveryDate): UpdateDeliveryDateRequest
    {
        $request = new UpdateDeliveryDateRequest();
        $request->partnerId = 'partner-1';
        $request->orderId = 'order-1';
        $request->expectedDeliveryDate = $expectedDeliveryDate;

        return $request;
    }
}
