<?php

require_once
__DIR__ .
'/../config/database.php';

class Cart
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
        (new Database())
        ->getConnection();
    }

    /*
    |--------------------------------------------------------------------------
    | FIND OR CREATE CART
    |--------------------------------------------------------------------------
    */

    public function getOrCreateCart(
        ?int $userId,
        ?string $guestId
    ): int {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "SELECT id
                 FROM carts
                 WHERE user_id = ?"
            );

            $stmt->execute([
                $userId
            ]);

        } else {

            $stmt =
            $this->db->prepare(
                "SELECT id
                 FROM carts
                 WHERE guest_id = ?"
            );

            $stmt->execute([
                $guestId
            ]);
        }

        $cart = $stmt->fetch();

        if ($cart) {

            return (int)$cart['id'];
        }

        $insert =
        $this->db->prepare(
            "INSERT INTO carts
            (
                user_id,
                guest_id
            )
            VALUES
            (
                :user_id,
                :guest_id
            )"
        );

        $insert->execute([

            ':user_id' =>
                $userId,

            ':guest_id' =>
                $guestId
        ]);

        return (int)
        $this->db->lastInsertId();
    }

    /*
    |--------------------------------------------------------------------------
    | ADD TO CART
    |--------------------------------------------------------------------------
    */

    public function addItem(
        ?int $userId,
        ?string $guestId,
        int $productId
    ): bool {

        $productCheck =
        $this->db->prepare(
            "SELECT
                is_available,
                stock_quantity
            FROM products
            WHERE id = ?"
        );

        $productCheck->execute([
            $productId
        ]);

        $product =
        $productCheck->fetch();


        if (
            !$product ||
            (int)$product['is_available'] !== 1
        ) {

            return false;
        }

        $cartId =
        $this->getOrCreateCart(
            $userId,
            $guestId
        );

        $current =
        $this->db->prepare(
            "SELECT quantity
            FROM cart_items
            WHERE cart_id = ?
            AND product_id = ?"
        );

        $current->execute([

            $cartId,

            $productId

        ]);

        $currentItem =
        $current->fetch();

        $check =
        $this->db->prepare(
            "SELECT id
             FROM cart_items
             WHERE cart_id = ?
             AND product_id = ?"
        );

        $check->execute([
            $cartId,
            $productId
        ]);

        $existing =
        $check->fetch();

        if ($existing) {


            if (
                (int)$currentItem['quantity']
                >=
                (int)$product['stock_quantity']
            ) {

                return false;
            }


            $update =
            $this->db->prepare(
                "UPDATE cart_items
                SET quantity =
                    quantity + 1
                WHERE id = ?"
            );


            return $update->execute([

                $existing['id']

            ]);
        }

        $insert =
        $this->db->prepare(
            "INSERT INTO cart_items
            (
                cart_id,
                product_id,
                quantity
            )
            VALUES
            (
                ?,
                ?,
                1
            )"
        );

        return $insert->execute([
            $cartId,
            $productId
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | GET CART ITEMS
    |--------------------------------------------------------------------------
    */

    public function getItems(
        ?int $userId,
        ?string $guestId
    ): array {

        if ($userId) {

            $cartStmt =
            $this->db->prepare(
                "SELECT id
                 FROM carts
                 WHERE user_id = ?"
            );

            $cartStmt->execute([
                $userId
            ]);

        } else {

            $cartStmt =
            $this->db->prepare(
                "SELECT id
                 FROM carts
                 WHERE guest_id = ?"
            );

            $cartStmt->execute([
                $guestId
            ]);
        }

        $cart =
        $cartStmt->fetch();

        if (!$cart) {

            return [];
        }

        $stmt =
        $this->db->prepare(
            "

            SELECT

                ci.id,
                ci.cart_id,
                ci.product_id,
                ci.quantity,

                p.name,
                p.price,
                p.image,
                p.product_type,
                p.breed,
                p.age_weeks,
                p.weight_kg,
                p.is_available,
                p.stock_quantity

            FROM cart_items ci

            JOIN products p
                ON ci.product_id = p.id

            WHERE ci.cart_id = ?

            "
        );

        $stmt->execute([
            $cart['id']
        ]);

        return
        $stmt->fetchAll();
    }

    public function increaseQuantity(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): string {

        $check =
        $this->db->prepare(
            "

            SELECT

                ci.quantity,

                p.stock_quantity,

                p.is_available

            FROM cart_items ci

            INNER JOIN products p
                ON ci.product_id = p.id

            WHERE ci.id = ?

            LIMIT 1

            "
        );

        $check->execute([
            $itemId
        ]);

        $item =
        $check->fetch();

        if (!$item) {

            return 'NOT_FOUND';
        }

        if (
            (int)$item['is_available'] !== 1
        ) {

            return 'UNAVAILABLE';
        }

        if (
            (int)$item['quantity']
            >=
            (int)$item['stock_quantity']
        ) {

            return 'MAX_STOCK';
        }

        $update =
        $this->db->prepare(
            "UPDATE cart_items ci

            INNER JOIN carts c
            ON ci.cart_id = c.id

            SET ci.quantity =
                ci.quantity + 1

            WHERE ci.id = ?

            AND (
                c.user_id = ?
                OR c.guest_id = ?
            )"
        );

        $success =
        $update->execute([

            $itemId,

            $userId,

            $guestId

        ]);


        return $success
            ? 'SUCCESS'
            : 'ERROR';
            }

    public function decreaseQuantity(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): bool {

        $check =
        $this->db->prepare(
            "SELECT ci.quantity

            FROM cart_items ci

            INNER JOIN carts c
            ON ci.cart_id = c.id

            WHERE ci.id = ?

            AND (
                c.user_id = ?
                OR c.guest_id = ?
            )"
        );


        $check->execute([

            $itemId,

            $userId,

            $guestId

        ]);


        $item =
        $check->fetch();


        if (!$item) {

            return false;
        }


        if (
            $item['quantity'] <= 1
        ) {

            return $this->removeItem(
                $userId,
                $guestId,
                $itemId
            );
        }


        $stmt =
        $this->db->prepare(
            "UPDATE cart_items ci

            INNER JOIN carts c
            ON ci.cart_id = c.id

            SET ci.quantity =
                ci.quantity - 1

            WHERE ci.id = ?

            AND (
                c.user_id = ?
                OR c.guest_id = ?
            )"
        );


        return $stmt->execute([

            $itemId,

            $userId,

            $guestId

        ]);
    }

    public function removeItem(
        ?int $userId,
        ?string $guestId,
        int $itemId
    ): bool {

        $stmt =
        $this->db->prepare(
            "DELETE ci

            FROM cart_items ci

            INNER JOIN carts c
            ON ci.cart_id = c.id

            WHERE ci.id = ?

            AND (
                c.user_id = ?
                OR c.guest_id = ?
            )"
        );


        return $stmt->execute([

            $itemId,

            $userId,

            $guestId

        ]);
    }

    public function getCartId(
        ?int $userId,
        ?string $guestId
    ): ?int {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "SELECT id
                FROM carts
                WHERE user_id = ?"
            );

            $stmt->execute([
                $userId
            ]);

        } else {

            $stmt =
            $this->db->prepare(
                "SELECT id
                FROM carts
                WHERE guest_id = ?"
            );

            $stmt->execute([
                $guestId
            ]);
        }

        $cart =
        $stmt->fetch();

        return
        $cart
            ? (int)$cart['id']
            : null;
    }

    public function getProduct(
        int $productId
    ): ?array {

        $stmt =
        $this->db->prepare(

            "

            SELECT

                id,
                is_available,
                stock_quantity

            FROM products

            WHERE id = ?

            LIMIT 1

            "

        );

        $stmt->execute([
            $productId
        ]);

        $product =
        $stmt->fetch();

        return $product ?: null;

    }

    public function getCartItem(
        int $cartId,
        int $productId
    ): ?array {

        $stmt =
        $this->db->prepare(

            "

            SELECT *

            FROM cart_items

            WHERE cart_id = ?

            AND product_id = ?

            LIMIT 1

            "

        );

        $stmt->execute([

            $cartId,

            $productId

        ]);

        $item =
        $stmt->fetch();

        return $item ?: null;

    }

    public function getCartItemById(
        int $itemId,
        ?int $userId,
        ?string $guestId
    ): ?array {

        $stmt =
        $this->db->prepare(

            "

            SELECT

                ci.id,
                ci.quantity,
                ci.product_id,

                p.stock_quantity,
                p.is_available

            FROM cart_items ci

            INNER JOIN carts c

                ON ci.cart_id = c.id

            INNER JOIN products p

                ON ci.product_id = p.id

            WHERE ci.id = ?

            AND (

                c.user_id = ?

                OR c.guest_id = ?

            )

            LIMIT 1

            "

        );

        $stmt->execute([

            $itemId,

            $userId,

            $guestId

        ]);

        $item =
        $stmt->fetch();

        return $item ?: null;

    }

    public function incrementCartItem(
        int $itemId
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            UPDATE cart_items

            SET quantity = quantity + 1

            WHERE id = ?

            "

        );

        return $stmt->execute([
            $itemId
        ]);

    }

    public function createCartItem(
        int $cartId,
        int $productId
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            INSERT INTO cart_items
            (
                cart_id,
                product_id,
                quantity
            )

            VALUES
            (
                ?,
                ?,
                1
            )

            "

        );

        return $stmt->execute([

            $cartId,

            $productId

        ]);

    }
}