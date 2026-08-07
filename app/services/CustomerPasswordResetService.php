<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../config/database.php';

require_once
__DIR__ .
'/../config/app.php';

require_once
__DIR__ .
'/MailService.php';

class CustomerPasswordResetService
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
    | Find Customer By Email
    |--------------------------------------------------------------------------
    */

    private function findByEmail(
        string $email
    ): ?array {

        $sql = "

            SELECT *

            FROM users

            WHERE email = :email

            AND role = 'customer'

            LIMIT 1

        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([

            ':email' =>
                strtolower(
                    trim($email)
                )

        ]);

        return
            $stmt->fetch()
            ?: null;

    }

    /*
    |--------------------------------------------------------------------------
    | Send Password Reset Link
    |--------------------------------------------------------------------------
    */

    public function sendPasswordResetLink(
        string $email
    ): bool {

        $email =
            strtolower(
                trim($email)
            );

        $user =
            $this->findByEmail(
                $email
            );

        /*
        ------------------------------------------------
        Never reveal whether the email exists.
        ------------------------------------------------
        */

        if (!$user) {

            return true;

        }

        /*
        ------------------------------------------------
        Only active customer accounts.
        ------------------------------------------------
        */

        if (
            $user['status']
            !==
            'active'
        ) {

            return true;

        }

        /*
        ------------------------------------------------
        Remove previous reset requests.
        ------------------------------------------------
        */

        $stmt =
            $this->db->prepare(

                "

                DELETE FROM password_resets

                WHERE user_id = :user_id

                "

            );

        $stmt->execute([

            ':user_id' =>
                $user['id']

        ]);

        /*
        ------------------------------------------------
        Generate secure reset token.
        ------------------------------------------------
        */

        $rawToken =
            bin2hex(
                random_bytes(32)
            );

        $tokenHash =
            hash(
                'sha256',
                $rawToken
            );

        $expiresAt =
            date(

                'Y-m-d H:i:s',

                strtotime(
                    '+8 minutes'
                )

            );

        /*
        ------------------------------------------------
        Store only the SHA-256 hash.
        ------------------------------------------------
        */

        $stmt =
            $this->db->prepare(

                "

                INSERT INTO password_resets
                (

                    user_id,
                    token_hash,
                    expires_at

                )

                VALUES
                (

                    :user_id,
                    :token_hash,
                    :expires_at

                )

                "

            );

        $stmt->execute([

            ':user_id' =>
                $user['id'],

            ':token_hash' =>
                $tokenHash,

            ':expires_at' =>
                $expiresAt

        ]);

        $resetLink =

            APP_URL

            .

            '/auth/reset-password.php?token='

            .

            urlencode(
                $rawToken
            );

        $mail =
            new MailService();

        $subject =
            'JAN Poultry Password Reset';
        
        $body = "

        <div
            style='
                max-width:650px;
                margin:40px auto;
                background:#ffffff;
                border-radius:14px;
                overflow:hidden;
                border:1px solid #e6e6e6;
                font-family:Segoe UI,Arial,sans-serif;
            '>

            <div
                style='
                    background:#071426;
                    padding:35px;
                    text-align:center;
                '>

                <h1
                    style='
                        margin:0;
                        color:#d4af37;
                        letter-spacing:1px;
                    '>

                    JAN POULTRY

                </h1>

                <p
                    style='
                        margin-top:10px;
                        color:#d8d8d8;
                        font-size:15px;
                    '>

                    Customer Password Recovery

                </p>

            </div>

            <div
                style='
                    padding:40px;
                    color:#333;
                    line-height:1.8;
                '>

                <h2
                    style='
                        margin-top:0;
                        color:#071426;
                    '>

                    Hello, {$user['name']} 👋

                </h2>

                <p>

                    We received a request to reset the password for your
                    <strong>JAN Poultry</strong> customer account.

                </p>

                <p>

                    If you requested this password reset, simply click the button below.

                </p>

                <div
                    style='
                        text-align:center;
                        margin:40px 0;
                    '>

                    <a

                        href='{$resetLink}'

                        style='
                            display:inline-block;
                            background:#d4af37;
                            color:#071426;
                            text-decoration:none;
                            font-weight:700;
                            padding:16px 34px;
                            border-radius:8px;
                            font-size:16px;
                        '

                    >

                        Reset My Password

                    </a>

                </div>

                <p>

                    This secure link expires in
                    <strong>8 minutes</strong>.

                </p>

                <p>

                    For your security:

                </p>

                <ul>

                    <li>This link can only be used once.</li>

                    <li>It expires automatically after 8 minutes.</li>

                    <li>After changing your password, you'll sign in using the new password.</li>

                </ul>

                <div
                    style='
                        background:#fff8e5;
                        border-left:4px solid #d4af37;
                        padding:16px;
                        margin-top:30px;
                        border-radius:6px;
                    '>

                    <strong>Didn't request this?</strong>

                    <br><br>

                    No problem. Simply ignore this email and your account will remain secure.
                    No changes will be made unless the button above is used.

                </div>

                <p
                    style='
                        margin-top:35px;
                    '>

                    Thank you for choosing
                    <strong>JAN Poultry</strong>.

                </p>

            </div>

            <div
                style='
                    background:#f6f6f6;
                    padding:25px;
                    text-align:center;
                    font-size:13px;
                    color:#777;
                '>

                © " . date('Y') . " JAN Poultry

                <br>

                Fresh poultry. Trusted service.

                <br><br>

                This is an automated security email.
                Please do not reply.

            </div>

        </div>

        ";

        $mail->send(

            $user['email'],

            $user['name'],

            $subject,

            $body

        );

        return true;    

    }

    /*
    |--------------------------------------------------------------------------
    | Validate Password Reset Token
    |--------------------------------------------------------------------------
    */

    public function validatePasswordResetToken(
        string $rawToken
    ): array {

        $tokenHash = hash(
            'sha256',
            trim($rawToken)
        );

        $stmt = $this->db->prepare(

            "

            SELECT

                password_resets.id,
                password_resets.user_id,
                password_resets.expires_at,
                password_resets.used,

                users.id AS user_id,
                users.name,
                users.email,
                users.role,
                users.status

            FROM password_resets

            INNER JOIN users

                ON users.id =
                password_resets.user_id

            WHERE

                password_resets.token_hash = ?

            LIMIT 1

            "

        );

        $stmt->execute([
            $tokenHash
        ]);

        $reset = $stmt->fetch();

        /*
        ---------------------------------------
        Invalid token
        ---------------------------------------
        */

        if (!$reset) {

            return [

                'success' => false,

                'reason' => 'invalid'

            ];

        }

        /*
        ---------------------------------------
        Already used
        ---------------------------------------
        */

        if ((int) $reset['used'] === 1) {

            return [

                'success' => false,

                'reason' => 'used'

            ];

        }

        /*
        ---------------------------------------
        Expired
        ---------------------------------------
        */

        if (

            strtotime(
                $reset['expires_at']
            ) <= time()

        ) {

            return [

                'success' => false,

                'reason' => 'expired'

            ];

        }

        /*
        ---------------------------------------
        Inactive customer
        ---------------------------------------
        */

        if (

            $reset['status']
            !==
            'active'

        ) {

            return [

                'success' => false,

                'reason' => 'inactive'

            ];

        }

        /*
        ---------------------------------------
        Customer accounts only
        ---------------------------------------
        */

        if (

            $reset['role']
            !==
            'customer'

        ) {

            return [

                'success' => false,

                'reason' => 'unauthorized'

            ];

        }

        return [

            'success' => true,

            'reset' => $reset

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Mark Password Reset Token As Used
    |--------------------------------------------------------------------------
    */

    public function markPasswordResetUsed(
        int $resetId
    ): bool {

        $stmt =
            $this->db->prepare(

                "

                UPDATE password_resets

                SET used = 1

                WHERE id = :id

                LIMIT 1

                "

            );

        return $stmt->execute([

            ':id' => $resetId

        ]);

    }

}