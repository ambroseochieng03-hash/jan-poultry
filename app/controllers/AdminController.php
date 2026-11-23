<?php

require_once
__DIR__ .
'/../helpers/Auth.php';

require_once
__DIR__ .
'/../helpers/Security.php';

require_once
__DIR__ .
'/../services/AdminService.php';

require_once
__DIR__ .
'/../services/MailService.php';

class AdminController
{
    private AdminService $adminService;

    private MailService $mailService;

    public function __construct()
    {
        $this->adminService =
        new AdminService();

        $this->mailService =
        new MailService();
    }

    public function createAdmin(): void
    {
        Auth::requireSuperAdmin();

        header(
            'Content-Type: application/json'
        );

        if (
            $_SERVER['REQUEST_METHOD']
            !== 'POST'
        ) {

            $this->jsonError(
                'Invalid request.'
            );
        }

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

        if (
            $this->adminService
            ->adminCount() >= 5
        ) {

            $this->jsonError(
                'Maximum admin limit reached.'
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

        if (
            empty($name)
            ||
            empty($email)
            ||
            empty($phone)
        ) {

            $this->jsonError(
                'All fields required.'
            );
        }

        if (
            $this->adminService
            ->emailExists(
                $email
            )
        ) {

            $this->jsonError(
                'Email already exists.'
            );
        }

        $generatedPassword =
        $this->adminService
        ->generatePassword();

        $created =
        $this->adminService
        ->createAdmin(
            $name,
            $email,
            $phone,
            $generatedPassword
        );

        if (
            !$created
        ) {

            $this->jsonError(
                'Failed to create admin.'
            );
        }

        $html =
        $this->buildAdminEmail(
            $name,
            $email,
            $generatedPassword
        );

        $mailSent =
        $this->mailService
        ->send(
            $email,
            $name,
            'JAN Poultry Administrator Account',
            $html
        );

        $this->adminService
        ->logAction(
            Auth::userId(),
            'Created admin: ' .
            $email
        );

        $this->jsonSuccess(

            $mailSent

            ? 'Admin created successfully. Login credentials have been emailed.'

            : 'Admin created successfully but email delivery failed.'

        );
    }

    private function buildAdminEmail(
        string $name,
        string $email,
        string $password
    ): string {

        return '

        <div
        style="
        max-width:700px;
        margin:auto;
        background:#ffffff;
        font-family:Arial,sans-serif;
        ">

            <div
            style="
            background:#071d11;
            padding:30px;
            text-align:center;
            ">

                <h1
                style="
                margin:0;
                color:#fbbf0f;
                ">

                JAN POULTRY

                </h1>

                <p
                style="
                color:#ffffff;
                ">

                Administrator Account Created

                </p>

            </div>

            <div
            style="
            padding:30px;
            ">

                <p>

                Hello ' .
                htmlspecialchars($name) .
                ',

                </p>

                <p>

                A JAN Poultry administrator account has been created for you.

                </p>

                <p>

                <strong>Email:</strong>
                ' .
                htmlspecialchars($email) .
                '

                </p>

                <p>

                <strong>Password:</strong>
                ' .
                htmlspecialchars($password) .
                '

                </p>

            </div>

        </div>

        ';
    }

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
