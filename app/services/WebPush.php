<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

/**
 * Web Push without extra libraries: RFC 8291 (aes128gcm encryption) and
 * RFC 8292 (VAPID), using PHP's openssl and curl only.
 */
final class WebPush
{
    /** Push services we are willing to contact. Stops a user from making the server call arbitrary URLs. */
    private const HOST_SUFFIXES = [
        '.googleapis.com',            // Chrome, Edge, Samsung, Opera (FCM)
        '.push.services.mozilla.com', // Firefox
        '.push.apple.com',            // Safari and iPhone home-screen apps
        '.notify.windows.com',        // legacy Edge
    ];

    public static function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function unb64u(string $text): string
    {
        $pad = (4 - strlen($text) % 4) % 4;
        $raw = base64_decode(strtr($text, '-_', '+/') . str_repeat('=', $pad), true);
        return $raw === false ? '' : $raw;
    }

    public static function allowedEndpoint(string $url): bool
    {
        $p = parse_url($url);
        if (!$p || ($p['scheme'] ?? '') !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['port']) || strlen($url) > 1000) {
            return false;
        }
        $host = strtolower((string) $p['host']);
        foreach (self::HOST_SUFFIXES as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }
        return false;
    }

    public static function publicKey(): string
    {
        Env::load();
        return (string) Env::get('VAPID_PUBLIC_KEY', '');
    }

    /** @return array{key:\OpenSSLAsymmetricKey,public:string,private:string} */
    public static function generateKeyPair(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($key === false) {
            throw new RuntimeException('Could not create an EC key');
        }
        $ec = openssl_pkey_get_details($key)['ec'];
        $x = str_pad($ec['x'], 32, "\0", STR_PAD_LEFT);
        $y = str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
        $d = str_pad($ec['d'], 32, "\0", STR_PAD_LEFT);
        return ['key' => $key, 'public' => "\x04" . $x . $y, 'private' => $d];
    }

    private static function pem(string $der, string $label): string
    {
        return "-----BEGIN {$label}-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END {$label}-----\n";
    }

    /** Raw 65-byte public key to an OpenSSL key object. */
    public static function publicKeyFromRaw(string $raw): \OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_get_public(self::pem(hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw, 'PUBLIC KEY'));
    }

    /** Raw 32-byte private value plus its 65-byte public key to an OpenSSL key object. */
    public static function privateKeyFromRaw(string $d, string $public): \OpenSSLAsymmetricKey|false
    {
        $der = hex2bin('30770201010420') . $d . hex2bin('a00a06082a8648ce3d030107a144034200') . $public;
        return openssl_pkey_get_private(self::pem($der, 'EC PRIVATE KEY'));
    }

    /** RFC 8291 body: salt, record size, key id, then the encrypted record. */
    public static function encrypt(string $payload, string $uaPublic, string $authSecret): string
    {
        $eph  = self::generateKeyPair();
        $peer = self::publicKeyFromRaw($uaPublic);
        if ($peer === false) {
            throw new RuntimeException('Bad subscriber key');
        }
        $secret = openssl_pkey_derive($peer, $eph['key'], 32);
        if (!is_string($secret)) {
            throw new RuntimeException('Key agreement failed');
        }

        $ikm   = hash_hkdf('sha256', $secret, 32, "WebPush: info\0" . $uaPublic . $eph['public'], $authSecret);
        $salt  = random_bytes(16);
        $cek   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $tag = '';
        $ct  = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($ct === false) {
            throw new RuntimeException('Encryption failed');
        }

        return $salt . pack('N', 4096) . chr(65) . $eph['public'] . $ct . $tag;
    }

    /** ECDSA DER signature to the raw 64-byte form JWT wants. */
    public static function derToRaw(string $der): string
    {
        $off = 2;
        if ((ord($der[1]) & 0x80) !== 0) {
            $off += ord($der[1]) & 0x7f;
        }
        $rl = ord($der[$off + 1]);
        $r  = substr($der, $off + 2, $rl);
        $off += 2 + $rl;
        $sl = ord($der[$off + 1]);
        $s  = substr($der, $off + 2, $sl);

        return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT) . str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
    }

    public static function vapidHeader(string $endpoint, string $publicB64, string $privateB64, string $subject): string
    {
        $pub = self::unb64u($publicB64);
        $key = self::privateKeyFromRaw(self::unb64u($privateB64), $pub);
        if ($key === false) {
            throw new RuntimeException('VAPID key is not usable');
        }
        $u   = parse_url($endpoint);
        $aud = $u['scheme'] . '://' . $u['host'];

        $head   = self::b64u((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64u((string) json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => $subject]));
        $der    = '';
        if (!openssl_sign($head . '.' . $claims, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign');
        }

        return 'vapid t=' . $head . '.' . $claims . '.' . self::b64u(self::derToRaw($der)) . ', k=' . self::b64u($pub);
    }

    /**
     * @param array{endpoint:string,p256dh:string,auth:string} $sub
     * @return int HTTP status from the push service (201 = accepted, 404/410 = gone, 0 = network error)
     */
    public static function send(array $sub, string $payload, int $ttl = 86400): int
    {
        Env::load();
        $pub  = (string) Env::get('VAPID_PUBLIC_KEY', '');
        $priv = (string) Env::get('VAPID_PRIVATE_KEY', '');
        $subj = (string) Env::get('VAPID_SUBJECT', 'mailto:admin@janpoultry.luxempire.co.ke');
        if ($pub === '' || $priv === '' || !self::allowedEndpoint($sub['endpoint'])) {
            return 0;
        }

        try {
            $body = self::encrypt($payload, self::unb64u($sub['p256dh']), self::unb64u($sub['auth']));
            $auth = self::vapidHeader($sub['endpoint'], $pub, $priv, $subj);
        } catch (Throwable $e) {
            error_log('WebPush prepare failed: ' . $e->getMessage());
            return 0;
        }

        $ch = curl_init($sub['endpoint']);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $auth,
                'Content-Encoding: aes128gcm',
                'Content-Type: application/octet-stream',
                'TTL: ' . $ttl,
                'Urgency: normal',
            ],
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return $status;
    }
}
