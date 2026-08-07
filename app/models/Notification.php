<?php

require_once
__DIR__ .
'/../config/database.php';


class Notification
{
    private PDO $db;


    public function __construct()
    {
        $database =
        new Database();

        $this->db =
        $database->getConnection();
    }



    public function create(
        ?int $userId,
        ?string $guestId,
        ?int $orderId,
        string $type,
        string $title,
        string $message
    ): bool {


        $stmt =
        $this->db->prepare(

            "
            INSERT INTO notifications
            (
                user_id,
                guest_id,
                order_id,
                type,
                title,
                message,
                expires_at
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                DATE_ADD(
                    NOW(),
                    INTERVAL 30 DAY
                )
            )
            "

        );


        return $stmt->execute([

            $userId,

            $guestId,

            $orderId,

            $type,

            $title,

            $message

        ]);

    }




    public function getUserNotifications(
        int $userId
    ): array {


        $stmt =
        $this->db->prepare(

            "
            SELECT *

            FROM notifications

            WHERE user_id = ?

            AND
            (
                expires_at IS NULL
                OR expires_at > NOW()
            )

            ORDER BY id DESC
            "

        );


        $stmt->execute([

            $userId

        ]);


        return
        $stmt->fetchAll();

    }




    public function getGuestNotifications(
        string $guestId
    ): array {


        $stmt =
        $this->db->prepare(

            "
            SELECT *

            FROM notifications

            WHERE guest_id = ?

            AND
            (
                expires_at IS NULL
                OR expires_at > NOW()
            )

            ORDER BY id DESC
            "

        );


        $stmt->execute([

            $guestId

        ]);


        return
        $stmt->fetchAll();

    }





    public function delete(
        int $notificationId,
        ?int $userId,
        ?string $guestId
    ): bool {


        $stmt =
        $this->db->prepare(

            "
            DELETE FROM notifications

            WHERE id = ?

            AND
            (
                user_id = ?

                OR

                guest_id = ?
            )

            LIMIT 1
            "

        );


        return
        $stmt->execute([

            $notificationId,

            $userId,

            $guestId

        ]);

    }





    public function deleteExpired(): bool
    {


        return
        $this->db->query(

            "
            DELETE FROM notifications

            WHERE expires_at IS NOT NULL

            AND expires_at <= NOW()

            "

        ) !== false;


    }

}