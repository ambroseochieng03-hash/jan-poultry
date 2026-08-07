<?php

require_once
__DIR__ .
'/../models/Cart.php';

require_once
__DIR__ .
'/../services/CartService.php';

class CartController
{
    private Cart $cart;

    private CartService $cartService;

    public function __construct()
    {
        $this->cart =
        new Cart();

        $this->cartService =
        new CartService();
    }

    public function getItems(
        ?int $userId,
        ?string $guestId
    ): array {

        return
        $this->cart
        ->getItems(
            $userId,
            $guestId
        );
    }

    public function addItem(
        ?int $userId,
        ?string $guestId,
        int $productId
    ): array {

        return
        $this->cartService
        ->addItem(

            $userId,

            $guestId,

            $productId

        );
    }

    public function increaseQuantity(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): array {

        return
        $this->cartService
        ->increaseQuantity(

            $userId,

            $guestId,

            $itemId

        );
    }

    public function decreaseQuantity(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): bool {

        return
        $this->cart
        ->decreaseQuantity(
            $userId,
            $guestId,
            $itemId
        );
    }

    public function removeItem(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): bool {

        return
        $this->cart
        ->removeItem(
            $userId,
            $guestId,
            $itemId
        );
    }
}
