<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Minimal Cloudflare R2 (S3-compatible) client: PUT / HEAD / DELETE.
| AWS Signature V4, region "auto", path-style URLs, cURL only (no SDK, so the
| worker stays small on a shared VM).
|--------------------------------------------------------------------------
*/
final class R2Client
{
    private string $scheme;
    private string $host;
    private string $hostHeader;

    public function __construct(
        string $endpoint,
        private readonly string $bucket,
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey
    ) {
        $parts = parse_url(rtrim($endpoint, '/'));

        if (!is_array($parts) || empty($parts['host'])) {
            throw new RuntimeException('R2_ENDPOINT is not a valid URL.');
        }

        $this->scheme     = $parts['scheme'] ?? 'https';
        $this->host       = $parts['host'];
        $this->hostHeader = $this->host . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    public static function fromEnv(): self
    {
        $endpoint = (string) Env::get('R2_ENDPOINT', '');
        $bucket   = (string) Env::get('R2_BUCKET', '');
        $key      = (string) Env::get('R2_ACCESS_KEY_ID', '');
        $secret   = (string) Env::get('R2_SECRET_ACCESS_KEY', '');

        if ($endpoint === '' || $bucket === '' || $key === '' || $secret === '') {
            throw new RuntimeException('R2 is not configured (R2_ENDPOINT, R2_BUCKET, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY).');
        }

        return new self($endpoint, $bucket, $key, $secret);
    }

    public function putFile(
        string $key,
        string $path,
        string $contentType,
        string $cacheControl = 'public, max-age=31536000, immutable'
    ): void {
        $size = filesize($path);
        $hash = hash_file('sha256', $path);

        if ($size === false || $hash === false) {
            throw new RuntimeException('Cannot read file for upload.');
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Cannot open file for upload.');
        }

        try {

            $this->send('PUT', $key, $hash, [
                CURLOPT_UPLOAD     => true,
                CURLOPT_INFILE     => $handle,
                CURLOPT_INFILESIZE => $size,
            ], [
                'Content-Type: ' . $contentType,
                'Cache-Control: ' . $cacheControl,
            ], [200, 201, 204]);

        } finally {
            fclose($handle);
        }
    }

    public function exists(string $key): bool
    {
        $status = $this->send('HEAD', $key, hash('sha256', ''), [CURLOPT_NOBODY => true], [], [200, 404]);

        return $status === 200;
    }

    public function delete(string $key): void
    {
        $this->send('DELETE', $key, hash('sha256', ''), [], [], [200, 204, 404]);
    }

    /**
     * SigV4 headers for one request (public so it can be unit-tested against botocore).
     *
     * @return array<string,string>
     */
    public function sign(string $method, string $key, string $payloadHash, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $amzDate = $now->format('Ymd\THis\Z');
        $date    = $now->format('Ymd');

        $uri = '/' . rawurlencode($this->bucket) . '/'
             . implode('/', array_map('rawurlencode', explode('/', $key)));

        $headers = [
            'host'                 => $this->hostHeader,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
        ];

        ksort($headers);

        $canonicalHeaders = '';

        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }

        $signedHeaders = implode(';', array_keys($headers));

        $canonicalRequest = $method . "\n" . $uri . "\n\n"
                          . $canonicalHeaders . "\n"
                          . $signedHeaders . "\n" . $payloadHash;

        $scope = $date . '/auto/s3/aws4_request';

        $stringToSign = "AWS4-HMAC-SHA256\n" . $amzDate . "\n" . $scope . "\n"
                      . hash('sha256', $canonicalRequest);

        $signingKey = hash_hmac('sha256', 'aws4_request',
            hash_hmac('sha256', 's3',
                hash_hmac('sha256', 'auto',
                    hash_hmac('sha256', $date, 'AWS4' . $this->secretAccessKey, true),
                    true),
                true),
            true);

        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return [
            'Authorization'        => 'AWS4-HMAC-SHA256 Credential=' . $this->accessKeyId . '/' . $scope
                                    . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date'           => $amzDate,
            'host'                 => $this->hostHeader,
        ];
    }

    /** @param int[] $okStatuses */
    private function send(
        string $method,
        string $key,
        string $payloadHash,
        array $curlOptions,
        array $extraHeaders,
        array $okStatuses
    ): int {
        $signed = $this->sign($method, $key, $payloadHash);

        $headers = [
            'Authorization: ' . $signed['Authorization'],
            'x-amz-content-sha256: ' . $signed['x-amz-content-sha256'],
            'x-amz-date: ' . $signed['x-amz-date'],
            'Expect:',
        ];

        $url = $this->scheme . '://' . $this->hostHeader . '/' . rawurlencode($this->bucket) . '/'
             . implode('/', array_map('rawurlencode', explode('/', $key)));

        $ch = curl_init($url);

        curl_setopt_array($ch, $curlOptions + [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => array_merge($headers, $extraHeaders),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 240,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $body   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error  = curl_error($ch);

        curl_close($ch);

        if ($body === false || !in_array($status, $okStatuses, true)) {
            throw new RuntimeException(sprintf(
                'R2 %s %s failed (HTTP %d) %s',
                $method,
                $key,
                $status,
                $error !== '' ? $error : substr(trim(strip_tags((string) $body)), 0, 200)
            ));
        }

        return $status;
    }
}
