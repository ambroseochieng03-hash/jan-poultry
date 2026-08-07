<?php

require_once __DIR__ . '/../models/Product.php';

class InventoryService
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
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

    public function getProductStatus(
        int $productId
    ): array {

        $product =
            $this->productModel
            ->getProductForInventory($productId);

        if (!$product) {

            return $this->response(
                false,
                'Product not found.'
            );

        }

        if (!(bool)$product['is_available']) {

            return $this->response(
                true,
                'Product is disabled.',
                [
                    'status' => 'disabled',
                    'product' => $product
                ]
            );

        }

        if ((int)$product['stock_quantity'] <= 0) {

            return $this->response(
                true,
                'Product is unavailable.',
                [
                    'status' => 'unavailable',
                    'product' => $product
                ]
            );

        }

        return $this->response(
            true,
            'Product is available.',
            [
                'status' => 'available',
                'product' => $product
            ]
        );
    }

    public function validateStock(
        int $productId,
        int $requestedQuantity
    ): array {

        if ($requestedQuantity <= 0) {

            return $this->response(
                false,
                'Invalid quantity requested.'
            );

        }

        $status =
            $this->getProductStatus(
                $productId
            );

        if (!$status['success']) {

            return $status;

        }

        $product =
            $status['data']['product'];

        switch ($status['data']['status']) {

            case 'disabled':

                return $this->response(
                    false,
                    'This product has been disabled.'
                );

            case 'unavailable':

                return $this->response(
                    false,
                    'This product is currently unavailable.'
                );

        }

        $availableStock =
            (int)$product['stock_quantity'];

        if (
            $requestedQuantity >
            $availableStock
        ) {

            return $this->response(
                false,
                'Only '
                . $availableStock .
                ' item(s) currently available.',
                [
                    'available_stock' => $availableStock
                ]
            );

        }

        return $this->response(
            true,
            'Stock validated successfully.',
            [
                'product' => $product,
                'available_stock' => $availableStock
            ]
        );
    }

    public function deductStock(
        int $productId,
        int $quantity
    ): array {

        if ($quantity <= 0) {

            return $this->response(
                false,
                'Invalid quantity.'
            );

        }

        $product =
            $this->productModel
            ->lockProduct($productId);

        if (!$product) {

            return $this->response(
                false,
                'Product not found.'
            );

        }

        if (!(bool)$product['is_available']) {

            return $this->response(
                false,
                'Product has been disabled.'
            );

        }

        $availableStock =
            (int)$product['stock_quantity'];

        if ($availableStock <= 0) {

            return [

                'success' => false,

                'title' =>
                    'Out of Stock',

                'message' =>

                        '"' .
                        $product['name'] .
                        '" is completely out of stock.

                    The customer requested '

                        . $quantity .

                        ' item(s), but there are none remaining.

                    Go to:
                    Products → Edit "' .

                        $product['name'] .

                        '"

                    Increase the stock before accepting this order.',

                'icon' => '⚠️',

                'autoClose' => 4000

            ];

        }

        if ($quantity > $availableStock) {

            $needed =
                $quantity -
                $availableStock;

            return [

                'success' => false,

                'title' =>
                    'Insufficient Stock',

                'message' =>

                        'The customer ordered '

                        . $quantity .

                        ' × '

                        . $product['name'] .

                        '.

                    Only '

                        . $availableStock .

                        ' remain in stock.

                    Increase the stock by at least '

                        . $needed .

                        ' more item(s).

                    Go to:
                    Products → Edit "' .

                        $product['name'] .

                        '" and increase the stock quantity.',

                'icon' => '⚠️',

                'autoClose' => 4000

            ];

        }

        $updated =
            $this->productModel
            ->deductStock(
                $productId,
                $quantity
            );

        if (!$updated) {

            return $this->response(
                false,
                'Failed to deduct stock.'
            );

        }

        return $this->response(
            true,
            'Stock deducted successfully.',
            [
                'remaining_stock' =>
                    $availableStock - $quantity
            ]
        );
    }

    public function restoreStock(
        int $productId,
        int $quantity
    ): array {

        if ($quantity <= 0) {

            return $this->response(
                false,
                'Invalid quantity.'
            );

        }

        $product =
            $this->productModel
            ->lockProduct($productId);

        if (!$product) {

            return $this->response(
                false,
                'Product not found.'
            );

        }

        $updated =
            $this->productModel
            ->restoreStock(
                $productId,
                $quantity
            );

        if (!$updated) {

            return $this->response(
                false,
                'Failed to restore stock.'
            );

        }

        return $this->response(
            true,
            'Stock restored successfully.',
            [
                'current_stock' =>
                    ((int)$product['stock_quantity']) + $quantity
            ]
        );
    }
}