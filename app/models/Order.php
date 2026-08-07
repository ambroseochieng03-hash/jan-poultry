<?php

require_once
__DIR__ .
'/../config/database.php';

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
        (new Database())
        ->getConnection();
    }

    public function getAll(): array
    {
        $sql = "

        SELECT

            o.*,

            u.name AS customer_name

        FROM orders o

        LEFT JOIN users u

            ON o.user_id = u.id

        ORDER BY o.id DESC

        ";

        $stmt =
        $this->db->query($sql);

        return
        $stmt->fetchAll();
    }

    public function createOrder(
        ?int $userId,
        ?string $guestId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $deliveryAddress,
        float $total
    ): int {

        $stmt =
        $this->db->prepare(
            "

            INSERT INTO orders
            (
                user_id,
                guest_id,

                customer_name,
                customer_email,
                customer_phone,

                delivery_address,

                total_price
            )

            VALUES
            (
                ?,
                ?,

                ?,
                ?,
                ?,

                ?,

                ?
            )

            "
        );

        $stmt->execute([

            $userId,

            $guestId,

            $customerName,

            $customerEmail,

            $customerPhone,

            $deliveryAddress,

            $total

        ]);

        return (int)
        $this->db->lastInsertId();
    }

    public function addOrderItem(
        int $orderId,
        int $productId,
        int $quantity,
        float $price
    ): bool {

        $stmt =
        $this->db->prepare(
            "INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                price_snapshot
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?
            )"
        );

        return $stmt->execute([

            $orderId,

            $productId,

            $quantity,

            $price
        ]);
    }

    public function getUserOrders(
        ?int $userId,
        ?string $guestId
    ): array {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "

                SELECT *

                FROM orders

                WHERE user_id = ?

                ORDER BY id DESC

                "
            );

            $stmt->execute([
                $userId
            ]);

        } else {

            $stmt =
            $this->db->prepare(
                "

                SELECT *

                FROM orders

                WHERE guest_id = ?

                ORDER BY id DESC

                "
            );

            $stmt->execute([
                $guestId
            ]);
        }

        return
        $stmt->fetchAll();
    }

    public function getOrderItems(
        int $orderId
    ): array {

        $stmt =
        $this->db->prepare(
            "

            SELECT

                oi.product_id,
                oi.quantity,
                oi.price_snapshot,

                p.name,
                p.image,
                p.product_type

            FROM order_items oi

            JOIN products p

                ON oi.product_id = p.id

            WHERE oi.order_id = ?

            "
        );

        $stmt->execute([
            $orderId
        ]);

        return
        $stmt->fetchAll();
    }

    public function getAllOrders(): array
    {
        $stmt =
        $this->db->query(

            "

            SELECT
                o.*,
                u.name,
                u.email,
                u.phone
            FROM orders o
            LEFT JOIN users u
                ON o.user_id = u.id
            WHERE o.status IN
            (
                'pending',
                'accepted',
                'in_transit'
            )
            ORDER BY o.id DESC

            "

        );

        return
        $stmt->fetchAll();
    }

    public function updateStatus(
        int $orderId,
        string $status
    ): bool {

        $sql = "

        UPDATE orders

        SET status = :status

        ";

        if ($status === 'accepted') {

            $sql .= ",
            accepted_at = NOW()
            ";

        }

        if ($status === 'in_transit') {

            $sql .= ",
            in_transit_at = NOW()
            ";

        }

        if ($status === 'completed') {

            $sql .= ",
            completed_at = NOW()
            ";

        }

        $sql .= "
        WHERE id = :id
        ";

        $stmt =
        $this->db->prepare($sql);

        $stmt->execute([

            ':status' => $status,

            ':id' => $orderId

        ]);

        return $stmt->rowCount() > 0;
    }

    public function cancelAcceptedOrder(
        int $orderId
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            UPDATE orders

            SET

                status = 'cancelled',

                cancelled_at = NOW()

            WHERE id = ?

            AND status = 'accepted'

            "

        );

        $stmt->execute([
            $orderId
        ]);

        return $stmt->rowCount() > 0;

    }

    public function getUserDetails(
        int $userId
    ): ?array {

        $stmt =
        $this->db->prepare(
            "

            SELECT

                name,
                email,
                phone

            FROM users

            WHERE id = ?

            LIMIT 1

            "
        );

        $stmt->execute([
            $userId
        ]);

        $user =
        $stmt->fetch();

        return $user ?: null;
    }

    public function cancelOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): bool {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "

                UPDATE orders

                SET

                    status = 'cancelled',

                    cancelled_at = NOW()

                WHERE id = ?

                AND user_id = ?

                AND status IN
                (
                    'pending',
                    'accepted'
                )

                "
            );

            return $stmt->execute([

                $orderId,

                $userId

            ]);
        }

        $stmt =
        $this->db->prepare(
            "

            UPDATE orders

            SET

                status = 'cancelled',

                cancelled_at = NOW()

            WHERE id = ?

            AND guest_id = ?

            AND status IN
            (
                'pending',
                'accepted'
            )

            "
        );

        return $stmt->execute([

            $orderId,

            $guestId

        ]);
    }

    public function deleteOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): bool {

        if ($userId) {

            $stmt =
            $this->db->prepare(
                "

                DELETE FROM orders

                WHERE id = ?

                AND user_id = ?

                AND status IN
                (
                    'completed',
                    'rejected',
                    'cancelled'
                )

                "
            );

            return $stmt->execute([

                $orderId,

                $userId

            ]);
        }

        $stmt =
        $this->db->prepare(
            "

            DELETE FROM orders

            WHERE id = ?

            AND guest_id = ?

            AND status IN
            (
                'completed',
                'rejected',
                'cancelled'
            )

            "
        );

        return $stmt->execute([

            $orderId,

            $guestId

        ]);
    }

    public function getSingleOrder(
        int $orderId,
        ?int $userId,
        ?string $guestId
    ): ?array {


        if ($userId) {


            $stmt =
            $this->db->prepare(
                "
                SELECT *

                FROM orders

                WHERE id = ?

                AND user_id = ?

                LIMIT 1
                "
            );


            $stmt->execute([
                $orderId,
                $userId
            ]);


        } else {


            $stmt =
            $this->db->prepare(
                "
                SELECT *

                FROM orders

                WHERE id = ?

                AND guest_id = ?

                LIMIT 1
                "
            );


            $stmt->execute([
                $orderId,
                $guestId
            ]);

        }


        $order =
        $stmt->fetch();


        return $order ?: null;

    }

    public function getAdminOrder(
        int $orderId
    ): ?array {

        $stmt =
        $this->db->prepare(
            "

            SELECT

                o.*,

                u.name AS user_name,

                u.email AS user_email,

                u.phone AS user_phone

            FROM orders o

            LEFT JOIN users u

                ON o.user_id = u.id

            WHERE o.id = ?

            LIMIT 1

            "
        );


        $stmt->execute([
            $orderId
        ]);


        $order =
        $stmt->fetch();


        return $order ?: null;

    }

    public function getOrderById(
        int $orderId
    ): ?array {

        $stmt =
        $this->db->prepare(

            "

            SELECT *

            FROM orders

            WHERE id = ?

            LIMIT 1

            "

        );

        $stmt->execute([
            $orderId
        ]);

        $order =
        $stmt->fetch();

        return $order ?: null;
    }

    public function getCompletedOrdersCount(): int
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

    public function getTotalRevenue(): float
    {
        $stmt =
        $this->db->query(

            "

            SELECT
            COALESCE(
                SUM(total_price),
                0
            )

            FROM orders

            WHERE status = 'completed'

            "

        );

        return (float)
        $stmt->fetchColumn();
    }

    public function getTodayRevenue(): float
    {
        $stmt =
        $this->db->query(

            "

            SELECT
            COALESCE(
                SUM(total_price),
                0
            )

            FROM orders

            WHERE status = 'completed'

            AND DATE(completed_at)
                = CURDATE()

            "

        );

        return (float)
        $stmt->fetchColumn();
    }

    public function getMonthRevenue(): float
    {
        $stmt =
        $this->db->query(

            "

            SELECT
            COALESCE(
                SUM(total_price),
                0
            )

            FROM orders

            WHERE status = 'completed'

            AND YEAR(completed_at)
                = YEAR(NOW())

            AND MONTH(completed_at)
                = MONTH(NOW())

            "

        );

        return (float)
        $stmt->fetchColumn();
    }

    public function getCompletedOrders(): array
    {
        $stmt =
        $this->db->query(

            "

            SELECT *

            FROM orders

            WHERE status = 'completed'

            ORDER BY completed_at DESC

            "

        );

        return
        $stmt->fetchAll();
    }

    public function count(): int
    {
        return (int)
        $this->db
        ->query(
            'SELECT COUNT(*) FROM orders'
        )
        ->fetchColumn();
    }
}