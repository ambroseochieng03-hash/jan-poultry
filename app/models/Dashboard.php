<?php

require_once
__DIR__ .
'/../config/database.php';

class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
        (new Database())
        ->getConnection();
    }

    public function getOrdersCount(
        ?int $userId,
        ?string $guestId
    ): int {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "SELECT COUNT(*)
                FROM orders
                WHERE user_id = ?"
            );

            $stmt->execute([
                $userId
            ]);

        } else {

            $stmt =
            $this->db->prepare(
                "SELECT COUNT(*)
                FROM orders
                WHERE guest_id = ?"
            );

            $stmt->execute([
                $guestId
            ]);
        }

        return (int)
        $stmt->fetchColumn();
    }

    public function getCartItemsCount(
        ?int $userId,
        ?string $guestId
    ): int {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "

                SELECT
                COALESCE(
                    SUM(ci.quantity),
                    0
                )

                FROM cart_items ci

                INNER JOIN carts c
                ON ci.cart_id = c.id

                WHERE c.user_id = ?

                "
            );

            $stmt->execute([
                $userId
            ]);

        } else {

            $stmt =
            $this->db->prepare(
                "

                SELECT
                COALESCE(
                    SUM(ci.quantity),
                    0
                )

                FROM cart_items ci

                INNER JOIN carts c
                ON ci.cart_id = c.id

                WHERE c.guest_id = ?

                "
            );

            $stmt->execute([
                $guestId
            ]);
        }

        return (int)
        $stmt->fetchColumn();
    }

    public function getNotificationsCount(
        ?int $userId,
        ?string $guestId
    ): int {

        $stmt =
        $this->db->prepare(

            "

            SELECT COUNT(*)

            FROM notifications

            WHERE
            (
                user_id = ?
                OR guest_id = ?
            )

            AND
            (
                expires_at IS NULL
                OR expires_at > NOW()
            )

            "

        );

        $stmt->execute([

            $userId,

            $guestId

        ]);

        return (int)
        $stmt->fetchColumn();
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */

    public function getProductsCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)

            FROM products

            "

        );

        return (int)
        $stmt->fetchColumn();
    }

    public function getAdminOrdersCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)
            FROM orders
            WHERE status IN
            (
                'pending',
                'accepted',
                'in_transit'
            )

            "

        );

        return (int)
        $stmt->fetchColumn();
    }

    public function getCustomersCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)

            FROM users

            WHERE role = 'customer'

            AND status != 'deleted'

            "

        );

        return (int)
        $stmt->fetchColumn();
    }

    public function getAdminsCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)

            FROM users

            WHERE role IN
            (
                'admin',
                'super_admin'
            )

            AND status != 'deleted'

            "

        );

        return (int)
        $stmt->fetchColumn();
    }

    public function getCompletedDeliveriesCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)

            FROM orders

            WHERE status = 'completed'

            "

        );

        return (int)
        $stmt->fetchColumn();
    }

    public function getLogsCount(): int
    {
        $stmt =
        $this->db->query(

            "

            SELECT COUNT(*)

            FROM admin_logs

            "

        );

        return (int)
        $stmt->fetchColumn();
    }
}