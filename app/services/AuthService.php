<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/RememberMe.php';
require_once __DIR__ . '/../helpers/PendingCart.php';

class AuthService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    private function clearAuthSession(): void
    {
        unset($_SESSION['admin']);
        unset($_SESSION['customer']);
        unset($_SESSION['guest']);
    }

    private function authenticateUser(array $user, bool $issueRemember = true): void
    {
        /*
        ----------------------------------------
        Start a fresh authenticated session
        ----------------------------------------
        */

        session_regenerate_id(true);

        Security::resetCsrfToken();

        $this->clearAuthSession();

        /*
        ----------------------------------------
        Store authenticated identity
        ----------------------------------------
        */

        $sessionKey = $user['role'] === 'customer'
            ? 'customer'
            : 'admin';

        $_SESSION[$sessionKey] = [

            'id'   => (int) $user['id'],

            'role' => $user['role'],

            'name' => $user['name']

        ];

        /*
        ----------------------------------------
        Trusted session token
        ----------------------------------------
        */

        $sessionToken =
            bin2hex(
                random_bytes(32)
            );

        $_SESSION[$sessionKey]['session_token'] =
            $sessionToken;

        $_SESSION[$sessionKey]['authenticated_at'] =
            time();

        $this->updateSessionToken(
            (int) $user['id'],
            $sessionToken
        );

        /* Customers: keep them signed in on this device and move anything they
           chose before signing in (the pending order) into their real cart. */
        if ($sessionKey === 'customer') {
            if ($issueRemember) {
                RememberMe::issue($this->db, (int) $user['id']);
            }
            PendingCart::merge((int) $user['id'], null);
        }
    }

    public function authenticate(array $user, bool $issueRemember = true): void
    {
        $this->authenticateUser($user, $issueRemember);
    }

    /* ======================
       REGISTER USER
    ======================= */
        public function register(
            array $data
        ): bool {

            $sql = "
                INSERT INTO users
                (
                    name,
                    email,
                    phone,
                    password_hash,
                    role
                )
                VALUES
                (
                    :name,
                    :email,
                    :phone,
                    :password_hash,
                    'customer'
                )
            ";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':password_hash' => password_hash(
                    $data['password'],
                    PASSWORD_ARGON2ID
                )
            ]);
        }

    /* ======================
       LOGIN USER
    ======================= */
    public function login(string $email, string $password): bool
    {

        $email = strtolower(trim($email));

        if ($this->isLoginLocked($email)) {

            $_SESSION['login_error'] =
                'Too many failed login attempts. Please try again later.';

            return false;
        }

        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        $user = $stmt->fetch();

        if (!$user) {

            $this->recordFailedLogin($email);

            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {

            $this->recordFailedLogin($email);

            return false;
        }

        if ($user['status'] !== 'active') {

            $_SESSION['login_error'] =
                'Login failed. Please try again later.';

            return false;
        }

        $this->authenticateUser($user);

        // Successful login clears any previous failures
        $this->clearLoginAttempts($email);

        // update last login
        $this->updateLastLogin($user['id']);

        return true;
    }

    /* ======================
       GUEST LOGIN
    ======================= */
    public function guestLogin(string $name, string $phone): void
    {
        $this->clearAuthSession();

        $_SESSION['guest'] = [

            'id' => bin2hex(random_bytes(16)),

            'name' => $name,

            'phone' => $phone,

            'authenticated_at' => time()

        ];
        PendingCart::merge(null, (string) $_SESSION['guest']['id']);
    }

    /*------------------------
    LOGOUT HANDLER
    ------------------------ */
    public function logout(): void
    {
        RememberMe::forget($this->db);
        $this->clearAuthSession();

        Session::destroy();
    }
    

    /* ======================
       LAST LOGIN
    ======================= */
    private function updateLastLogin(int $userId): void
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
    }

    /**
     * Updates the trusted session token.
     */
    private function updateSessionToken(
        int $userId,
        string $token
    ): void {

        $sql = "
            UPDATE users
            SET session_token = :token
            WHERE id = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare(
                $sql
            );

        $stmt->execute([
            ':token' => $token,
            ':id'    => $userId
        ]);
    }

    /* ======================
       FIND USER
    ======================= */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $sql = "
            SELECT id
            FROM users
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        return (bool)$stmt->fetch();
    }

    /* ======================
    FIND USER BY ID
    ====================== */
    public function findById(
        int $userId
    ): ?array {

        $sql = "
            SELECT *
            FROM users
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $userId
        ]);

        return $stmt->fetch() ?: null;
    }

    /* ======================
    VERIFY PASSWORD
    ====================== */
    public function verifyPassword(
        int $userId,
        string $password
    ): bool {

        $user =
        $this->findById(
            $userId
        );

        if (
            !$user ||
            empty(
                $user['password_hash']
            )
        ) {

            return false;

        }

        return password_verify(
            $password,
            $user['password_hash']
        );
    }

    /* ======================
    UPDATE PROFILE
    ====================== */
    public function updateProfile(
        int $userId,
        string $name,
        string $email,
        ?string $phone
    ): bool {

        $sql = "
            UPDATE users
            SET
                name = :name,
                email = :email,
                phone = :phone
            WHERE id = :id
            LIMIT 1
        ";

        $stmt =
        $this->db->prepare(
            $sql
        );

        return $stmt->execute([
            ':name'  => trim($name),
            ':email' => trim($email),
            ':phone' => $phone,
            ':id'    => $userId
        ]);
    }

    /* ======================
    CHANGE PASSWORD
    ====================== */
    public function changePassword(
        int $userId,
        string $newPassword
    ): bool {

        $hash =
        password_hash(
            $newPassword,
            PASSWORD_ARGON2ID
        );

        $sql = "
            UPDATE users
            SET password_hash = :hash
            WHERE id = :id
            LIMIT 1
        ";

        $stmt =
        $this->db->prepare(
            $sql
        );

        $ok = $stmt->execute([
            ':hash' => $hash,
            ':id'   => $userId
        ]);

        /* A password reset signs the account out everywhere. */
        RememberMe::forgetAll($this->db, (int) $userId);

        return $ok;
    }

    /* ======================
    LOGIN ATTEMPT HELPERS
    ====================== */

    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function getLoginAttempt(string $email): ?array
    {
        $sql = "
            SELECT *
            FROM login_attempts
            WHERE email = :email
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => strtolower(trim($email))
        ]);

        return $stmt->fetch() ?: null;
    }

    private function isLoginLocked(string $email): bool
    {
        $attempt = $this->getLoginAttempt($email);

        if (!$attempt) {
            return false;
        }

        if (empty($attempt['locked_until'])) {
            return false;
        }

        return strtotime($attempt['locked_until']) > time();
    }

    private function resetExpiredLock(array $attempt): void
    {
        if (
            empty($attempt['locked_until']) ||
            strtotime($attempt['locked_until']) > time()
        ) {
            return;
        }

        $sql = "
            UPDATE login_attempts
            SET
                failed_attempts = 0,
                locked_until = NULL,
                lock_level = lock_level + 1
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $attempt['id']
        ]);
    }

    private function clearLoginAttempts(string $email): void
    {
        $sql = "
            DELETE
            FROM login_attempts
            WHERE email = :email
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => strtolower(trim($email))
        ]);
    }

    private function lockAccount(
        int $attemptId,
        int $lockLevel
    ): void {

        $minutes =
            LOGIN_LOCKOUT_MINUTES * (2 ** $lockLevel);

        $lockedUntil = date(
            'Y-m-d H:i:s',
            strtotime("+{$minutes} minutes")
        );

        $sql = "
            UPDATE login_attempts
            SET
                failed_attempts = 0,
                lock_level = :lock_level,
                locked_until = :locked_until,
                last_attempt = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':lock_level'  => $lockLevel + 1,
            ':locked_until'=> $lockedUntil,
            ':id'          => $attemptId
        ]);
    }

    private function recordFailedLogin(string $email): void
    {
        $email = strtolower(trim($email));

        $attempt = $this->getLoginAttempt($email);

        /*
        ------------------------------------------
        First ever failed login for this email
        ------------------------------------------
        */

        if (!$attempt) {

            $sql = "
                INSERT INTO login_attempts
                (
                    email,
                    ip_address,
                    failed_attempts,
                    lock_level,
                    last_attempt
                )
                VALUES
                (
                    :email,
                    :ip,
                    1,
                    0,
                    NOW()
                )
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':email' => $email,
                ':ip'    => $this->getClientIp()
            ]);

            return;
        }

        /*
        ------------------------------------------
        Already locked?
        Don't modify anything.
        ------------------------------------------
        */

        if (
            !empty($attempt['locked_until']) &&
            strtotime($attempt['locked_until']) > time()
        ) {
            return;
        }

        /*
        ------------------------------------------
        Previous lock expired
        Reset for a fresh round.
        ------------------------------------------
        */

        if (
            !empty($attempt['locked_until']) &&
            strtotime($attempt['locked_until']) <= time()
        ) {

            $attempt['failed_attempts'] = 0;
            $attempt['locked_until'] = null;

            $sql = "
                UPDATE login_attempts
                SET
                    failed_attempts = 0,
                    locked_until = NULL
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);

            $stmt->execute([
                ':id' => $attempt['id']
            ]);
        }

        $failedAttempts =
            (int)$attempt['failed_attempts'] + 1;

        /*
        ------------------------------------------
        Lock after configured number of failures
        ------------------------------------------
        */

        if (
            $failedAttempts >= LOGIN_MAX_ATTEMPTS
        ) {

            $this->lockAccount(
                (int)$attempt['id'],
                (int)$attempt['lock_level']
            );

            return;
        }

        /*
        ------------------------------------------
        Save updated attempt count
        ------------------------------------------
        */

        $sql = "
            UPDATE login_attempts
            SET
                failed_attempts = :attempts,
                last_attempt = NOW(),
                ip_address = :ip
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':attempts' => $failedAttempts,
            ':ip'       => $this->getClientIp(),
            ':id'       => $attempt['id']
        ]);
    }

    public function getRemainingLockTime(string $email): ?int
    {
        $attempt = $this->getLoginAttempt(
            strtolower(trim($email))
        );

        if (
            !$attempt ||
            empty($attempt['locked_until'])
        ) {
            return null;
        }

        $remaining =
            strtotime($attempt['locked_until']) - time();

        return $remaining > 0
            ? $remaining
            : null;
    }

}