
<?php

require_once
__DIR__ .
'/../models/Cart.php';

require_once
__DIR__ .
'/InventoryService.php';

class CartService
{
    private Cart $cartModel;

    private InventoryService $inventoryService;

    public function __construct()
    {
        $this->cartModel =
        new Cart();

        $this->inventoryService =
        new InventoryService();
    }

    private function response(
        bool $success,
        string $message,
        array $data = []
    ): array {

        return [

            'success' => $success,

            'message' => $message,

            'data' => $data

        ];

    }

    public function addItem(
        ?int $userId,
        ?string $guestId,
        int $productId
    ): array {

        $product =
        $this->cartModel
        ->getProduct(
            $productId
        );

        if (!$product) {

            return $this->response(
                false,
                'Product not found.'
            );

        }

        if (
            (int)$product['is_available'] !== 1
        ) {

            return $this->response(
                false,
                'This product has been disabled.'
            );

        }

        $validation =
        $this->inventoryService
        ->validateStock(

            $productId,

            1

        );

        if (
            !$validation['success']
        ) {

            return $validation;

        }

        $cartId =
        $this->cartModel
        ->getOrCreateCart(

            $userId,

            $guestId

        );

        $cartItem =
        $this->cartModel
        ->getCartItem(

            $cartId,

            $productId

        );

        if ($cartItem) {

            if (

                (int)$cartItem['quantity']

                >=

                (int)$product['stock_quantity']

            ) {

                return $this->response(

                    false,

                    'Maximum stock reached.'

                );

            }

            $updated =
            $this->cartModel
            ->incrementCartItem(
                (int)$cartItem['id']
            );

            return $updated

                ? $this->response(
                    true,
                    'Product quantity updated.'
                )

                : $this->response(
                    false,
                    'Failed to update cart.'
                );

        }

        $created =
        $this->cartModel
        ->createCartItem(

            $cartId,

            $productId

        );

        return $created

            ? $this->response(
                true,
                'Product added to cart.'
            )

            : $this->response(
                false,
                'Failed to add product to cart.'
            );

    }

    public function increaseQuantity(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): array {

        $item =
        $this->cartModel
        ->getCartItemById(

            $itemId,

            $userId,

            $guestId

        );

        if (!$item) {

            return $this->response(
                false,
                'Cart item not found.'
            );

        }

        if (
            (int)$item['is_available'] !== 1
        ) {

            return $this->response(
                false,
                'This product is currently unavailable.'
            );

        }

        $requestedQuantity =
        (int)$item['quantity'] + 1;

        $validation =
        $this->inventoryService
        ->validateStock(

            (int)$item['product_id'],

            $requestedQuantity

        );

        if (
            !$validation['success']
        ) {

            return $validation;

        }

        $updated =
        $this->cartModel
        ->incrementCartItem(
            $itemId
        );

        if (!$updated) {

            return $this->response(
                false,
                'Failed to update cart.'
            );

        }

        return $this->response(
            true,
            'Quantity updated successfully.'
        );

    }
}