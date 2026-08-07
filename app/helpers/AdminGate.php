<?php

class AdminGate
{
    private const GATE_FILE =
        __DIR__ . '/../../storage/admin-gate/gate.json';


    public static function requireGate(): void
    {
        if (!file_exists(self::GATE_FILE)) {

            self::deny();

        }


        $content =
            file_get_contents(self::GATE_FILE);


        $data =
            json_decode(
                $content,
                true
            );


        if (
            !is_array($data) ||

            !isset(
                $data['token'],
                $data['expires']
            )
        ) {

            self::consume();

            self::deny();

        }


        if (

            !is_string($data['token']) ||

            strlen($data['token']) !== 64

        ) {

            self::consume();

            self::deny();

        }


        if (
            time() > $data['expires']
        ) {

            self::consume();

            self::deny();

        }


        /*
        |--------------------------------------------------------------------------
        | Single-use gate
        |--------------------------------------------------------------------------
        |
        | Once someone reaches the admin login,
        | the knock is consumed.
        |
        */

        self::consume();
    }



    private static function consume(): void
    {
        if (file_exists(self::GATE_FILE)) {

            unlink(self::GATE_FILE);

        }
    }



    private static function deny(): void
    {
        http_response_code(404);

        exit;
    }
}