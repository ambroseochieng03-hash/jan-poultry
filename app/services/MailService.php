<?php

require_once
__DIR__ .
'/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config =
        require
        __DIR__ .
        '/../config/mail.php';
    }

    public function send(
    string $toEmail,
    string $toName,
    string $subject,
    string $body,
    ?string $attachmentPath = null
    ): bool {

    try {

        $mail =
        new PHPMailer(true);

        $mail->isSMTP();

        $mail->Host =
        $this->config['host'];

        $mail->SMTPAuth =
        true;

        $mail->Username =
        $this->config['username'];

        $mail->Password =
        $this->config['password'];

        $mail->SMTPSecure =
        $this->config['encryption'];

        $mail->Port =
        $this->config['port'];

        $mail->setFrom(

            $this->config['from_email'],

            $this->config['from_name']

        );

        $mail->addAddress(
            $toEmail,
            $toName
        );

        if (
            $attachmentPath &&
            file_exists($attachmentPath)
        ) {

            $mail->addAttachment(
                $attachmentPath
            );

        }

        $mail->isHTML(true);

        $mail->Subject =
        $subject;

        $mail->Body =
        $body;

        return
        $mail->send();

    } catch (
        Exception $e
    ) {

        error_log(
            $e->getMessage()
        );

        return false;
    }


    }

}
