<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/InventoryService.php';

class OrderService
{
    private PDO $db;

    private Order $orderModel;

    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->db =
            (new Database())
            ->getConnection();

        $this->orderModel =
            new Order();

        $this->inventoryService =
            new InventoryService();
    }

    /**
     * Standard service response.
     */
    private function response(
        bool $success,
        string $message,
        ?array $data = null
    ): array {

        return [

            'success' => $success,

            'message' => $message,

            'data' => $data

        ];
    }

    private function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {

            $this->db->beginTransaction();

        }
    }

    private function commit(): void
    {
        if ($this->db->inTransaction()) {

            $this->db->commit();

        }
    }

    private function rollBack(): void
    {
        if ($this->db->inTransaction()) {

            $this->db->rollBack();

        }
    }

    public function acceptOrder(
        int $orderId
    ): array {

        try {

            $this->beginTransaction();

            $order =
                $this->orderModel
                ->getOrderById(
                    $orderId
                );

            if (!$order) {

                $this->rollBack();

                return $this->response(
                    false,
                    'Order not found.'
                );

            }

            if (
                $order['status'] !== 'pending'
            ) {

                $this->rollBack();

                return $this->response(
                    false,
                    'Only pending orders can be accepted.'
                );

            }

            $items =
                $this->orderModel
                ->getOrderItems(
                    $orderId
                );

            if (empty($items)) {

                $this->rollBack();

                return $this->response(
                    false,
                    'Order contains no items.'
                );

            }

            foreach ($items as $item) {

                $result =
                    $this->inventoryService
                    ->deductStock(

                        (int)$item['product_id'],

                        (int)$item['quantity']

                    );

                if (!$result['success']) {

                    $this->rollBack();

                    return $result;

                }

            }

            $updated =
            $this->orderModel
            ->updateStatus(

                $orderId,

                'accepted'

            );

            if (!$updated) {

                $this->rollBack();

                return $this->response(

                    false,

                    'Failed to accept order.'

                );

            }

            $this->commit();

            return $this->response(

                true,

                'Order accepted successfully.'

            );

        } catch (Throwable $e) {

            $this->rollBack();

            return $this->response(
                false,
                $e->getMessage()
            );

        }
    }

    public function cancelAcceptedOrder(
        int $orderId
    ): array {

        try {

            $this->beginTransaction();

            $order =
            $this->orderModel
            ->getOrderById($orderId);

            if (!$order) {

                $this->rollBack();

                return $this->response(
                    false,
                    'Order not found.'
                );

            }

            if ($order['status'] !== 'accepted') {

                $this->rollBack();

                return $this->response(
                    false,
                    'Only accepted orders can be cancelled.'
                );

            }

            $items =
            $this->orderModel
            ->getOrderItems($orderId);

            foreach ($items as $item) {

                $result =
                $this->inventoryService
                ->restoreStock(

                    (int)$item['product_id'],

                    (int)$item['quantity']

                );

                if (!$result['success']) {

                    $this->rollBack();

                    return $result;

                }

            }

            $updated =
            $this->orderModel
            ->cancelAcceptedOrder(
                $orderId
            );

            if (!$updated) {

                $this->rollBack();

                return $this->response(
                    false,
                    'Failed to cancel order.'
                );

            }

            $this->commit();

            return $this->response(
                true,
                'Order cancelled successfully.'
            );

        } catch (Throwable $e) {

            $this->rollBack();

            return $this->response(
                false,
                $e->getMessage()
            );

        }
    }
}