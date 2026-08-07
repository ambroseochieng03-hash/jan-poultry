<?php

require_once
__DIR__ .
'/../models/Order.php';

require_once
__DIR__ .
'/../services/OrderService.php';

class OrderController
{
    private Order $order;

    private OrderService $orderService;

    public function __construct()
    {
        $this->order =
        new Order();

        $this->orderService =
        new OrderService();
    }

    public function getUserOrders(
        ?int $userId,
        ?string $guestId
    ): array {

        return
        $this->order
        ->getUserOrders(
            $userId,
            $guestId
        );
    }

    public function getOrderItems(
        int $orderId
    ): array {

        return
        $this->order
        ->getOrderItems(
            $orderId
        );
    }

    public function getSingleOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): ?array {

        return
        $this->order
        ->getSingleOrder(
            $orderId,
            $userId,
            $guestId
        );
    }

    public function getAll(): array
    {
        return
        $this->order
        ->getAll();
    }

    public function getAllOrders(): array
    {
        return
        $this->order
        ->getAllOrders();
    }

    public function updateStatus(
        int $orderId,
        string $status
    ): bool {

        return
        $this->order
        ->updateStatus(
            $orderId,
            $status
        );
    }

    public function acceptOrder(
        int $orderId
    ): array {

        return
        $this->orderService
        ->acceptOrder(
            $orderId
        );

    }

    public function cancelAcceptedOrder(
        int $orderId
    ): array {

        return
        $this->orderService
        ->cancelAcceptedOrder(
            $orderId
        );

    }

    public function cancelOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): bool {

        return
        $this->order
        ->cancelOrder(
            $orderId,
            $userId,
            $guestId
        );
    }

    public function deleteOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): bool {

        return
        $this->order
        ->deleteOrder(
            $orderId,
            $userId,
            $guestId
        );
    }

    public function getAdminOrder(
        int $orderId
    ): ?array {

        return
        $this->order
        ->getAdminOrder(
            $orderId
        );
    }

    public function getOrderById(
        int $orderId
    ): ?array {

        return
        $this->order
        ->getOrderById(
            $orderId
        );
    }

    public function getCompletedOrders(): array
    {
        return
        $this->order
        ->getCompletedOrders();
    }

    public function getCompletedOrdersCount(): int
    {
        return
        $this->order
        ->getCompletedOrdersCount();
    }

    public function getTotalRevenue(): float
    {
        return
        $this->order
        ->getTotalRevenue();
    }

    public function getTodayRevenue(): float
    {
        return
        $this->order
        ->getTodayRevenue();
    }

    public function getMonthRevenue(): float
    {
        return
        $this->order
        ->getMonthRevenue();
    }

    public function count(): int
    {
        return
        $this->order
        ->count();
    }
}