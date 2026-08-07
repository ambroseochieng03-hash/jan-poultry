<?php

require_once
__DIR__ .
'/../config/database.php';

class AdminService
{
    private PDO $db;

    public function __construct()
    {
        $this->db =
        (new Database())
        ->getConnection();
    }

    public function adminCount(): int
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

    public function emailExists(
        string $email
    ): bool {

        $stmt =
        $this->db->prepare(

            "

            SELECT id

            FROM users

            WHERE email = ?

            LIMIT 1

            "

        );

        $stmt->execute([
            $email
        ]);

        return (bool)
        $stmt->fetch();
    }

    public function generatePassword(): string
    {
        return
        'Jan@' .
        bin2hex(
            random_bytes(4)
        );
    }

    public function createAdmin(
        string $name,
        string $email,
        string $phone,
        string $password
    ): bool {

        $hash =
        password_hash(
            $password,
            PASSWORD_ARGON2ID
        );

        $stmt =
        $this->db->prepare(

            "

            INSERT INTO users
            (

                name,
                email,
                phone,
                password_hash,
                role,
                status

            )

            VALUES
            (

                ?,
                ?,
                ?,
                ?,
                'admin',
                'active'

            )

            "

        );

        return $stmt->execute([

            $name,
            $email,
            $phone,
            $hash

        ]);
    }

    public function logAction(
        int $adminId,
        string $action
    ): void {

        $stmt =
        $this->db->prepare(

            "

            INSERT INTO admin_logs
            (

                admin_id,
                action

            )

            VALUES
            (

                ?,
                ?

            )

            "

        );

        $stmt->execute([

            $adminId,
            $action

        ]);
    }
}
