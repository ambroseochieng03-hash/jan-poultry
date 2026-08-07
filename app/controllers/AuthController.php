<?php

require_once
__DIR__ .
'/../helpers/Auth.php';

require_once
__DIR__ .
'/../helpers/Security.php';

require_once
__DIR__ .
'/../services/AuthService.php';

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService =
        new AuthService();
    }

    /* ======================
       UPDATE PROFILE
    ======================= */
    public function updateProfile(): void
    {
        header(
            'Content-Type: application/json'
        );

        Auth::requireAdmin();

        $csrfToken =
        trim(
            $_POST['csrf_token']
            ?? ''
        );

        if (
            !Security::verifyCsrfToken(
                $csrfToken
            )
        ) {

            $this->jsonError(
                'Invalid CSRF token.'
            );

        }

        $userId =
        Auth::userId();

        $user =
        $this->authService
        ->findById(
            $userId
        );

        if (
            !$user
        ) {

            $this->jsonError(
                'User not found.'
            );

        }

        $name =
        trim(
            $_POST['name']
            ?? ''
        );

        $email =
        trim(
            $_POST['email']
            ?? ''
        );

        $phone =
        trim(
            $_POST['phone']
            ?? ''
        );

        $currentPassword =
        $_POST['current_password']
        ?? '';

        if (
            $name === ''
        ) {

            $this->jsonError(
                'Name is required.'
            );

        }

        if (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $this->jsonError(
                'Invalid email address.'
            );

        }

        $existingUser =
        $this->authService
        ->findByEmail(
            $email
        );

        if (
            $existingUser &&
            (int)$existingUser['id']
            !== (int)$userId
        ) {

            $this->jsonError(
                'Email already exists.'
            );

        }

        $emailChanged =
        strtolower(
            $email
        ) !== strtolower(
            $user['email']
        );

        if (
            $emailChanged
        ) {

            if (
                empty(
                    $currentPassword
                )
            ) {

                $this->jsonError(
                    'Current password is required.'
                );

            }

            if (
                !$this->authService
                ->verifyPassword(
                    $userId,
                    $currentPassword
                )
            ) {

                $this->jsonError(
                    'Current password is incorrect.'
                );

            }

        }

        $updated =
        $this->authService
        ->updateProfile(
            $userId,
            $name,
            $email,
            $phone
        );

        if (
            !$updated
        ) {

            $this->jsonError(
                'Failed to update profile.'
            );

        }

        $_SESSION['name'] =
        $name;

        $this->jsonSuccess(
            'Profile updated successfully.'
        );
    }

    /* ======================
       CHANGE PASSWORD
    ======================= */
    public function changePassword(): void
    {
        header(
            'Content-Type: application/json'
        );

        Auth::requireAdmin();

        $csrfToken =
        trim(
            $_POST['csrf_token']
            ?? ''
        );

        if (
            !Security::verifyCsrfToken(
                $csrfToken
            )
        ) {

            $this->jsonError(
                'Invalid CSRF token.'
            );

        }

        $userId =
        Auth::userId();

        $currentPassword =
        $_POST['current_password']
        ?? '';

        $newPassword =
        $_POST['new_password']
        ?? '';

        $confirmPassword =
        $_POST['confirm_password']
        ?? '';

        if (
            !$this->authService
            ->verifyPassword(
                $userId,
                $currentPassword
            )
        ) {

            $this->jsonError(
                'Current password is incorrect.'
            );

        }

        if (
            $newPassword !==
            $confirmPassword
        ) {

            $this->jsonError(
                'Passwords do not match.'
            );

        }

        if (
            strlen(
                $newPassword
            ) < 8
        ) {

            $this->jsonError(
                'Password must be at least 8 characters.'
            );

        }

        if (
            !preg_match(
                '/[A-Z]/',
                $newPassword
            )
        ) {

            $this->jsonError(
                'Password must contain an uppercase letter.'
            );

        }

        if (
            !preg_match(
                '/[a-z]/',
                $newPassword
            )
        ) {

            $this->jsonError(
                'Password must contain a lowercase letter.'
            );

        }

        if (
            !preg_match(
                '/[0-9]/',
                $newPassword
            )
        ) {

            $this->jsonError(
                'Password must contain a number.'
            );

        }

        if (
            !preg_match(
                '/[^A-Za-z0-9]/',
                $newPassword
            )
        ) {

            $this->jsonError(
                'Password must contain a special character.'
            );

        }

        $updated =
        $this->authService
        ->changePassword(
            $userId,
            $newPassword
        );

        if (
            !$updated
        ) {

            $this->jsonError(
                'Failed to change password.'
            );

        }

        $this->jsonSuccess(
            'Password changed successfully.'
        );
    }

    /* ======================
       JSON HELPERS
    ======================= */
    private function jsonSuccess(
        string $message
    ): void {

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);

        exit;
    }

    private function jsonError(
        string $message
    ): void {

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        exit;
    }
}