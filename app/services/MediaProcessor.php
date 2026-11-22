<?php

declare(strict_types=1);

/** The file itself is bad (or the job is): retrying will never help. */
final class PermanentMediaError extends RuntimeException
{
}

/*
|--------------------------------------------------------------------------
| Turns an uploaded image/video into web-ready files.
|   image -> WebP (max 1600px wide) + 480px thumbnail     (GD, FFmpeg for huge ones)
|   video -> H.264/AAC MP4 (max 1280px wide) + poster      (FFmpeg, 1 thread)
| Runs only inside the worker.
|--------------------------------------------------------------------------
*/
final class MediaProcessor
{
    private const GD_MAX_PIXELS     = 16_000_000;
    private const ABSOLUTE_MAX_PIXELS = 120_000_000;
    private const IMAGE_WIDTH       = 1600;
    private const THUMB_WIDTH       = 480;
    private const POSTER_WIDTH      = 640;
    private const MAX_VIDEO_SECONDS = 180;
    private const MAX_VIDEO_SIDE    = 4096;
    private const FFMPEG_TIMEOUT    = 270;

    public static function incomingDir(): string
    {
        return rtrim((string) Env::get('MEDIA_INCOMING_DIR', '/var/lib/janpoultry/incoming'), '/');
    }

    /**
     * NATS here has no password, so a job is untrusted input: only files inside the
     * incoming folder, with names we generated, are ever touched.
     *
     * @return array{base:string,kind:string,input:string,final:string}
     */
    public static function validateJob(mixed $data): array
    {
        if (!is_array($data)) {
            throw new PermanentMediaError('Malformed job.');
        }

        $base  = (string) ($data['base'] ?? '');
        $kind  = (string) ($data['kind'] ?? '');
        $input = (string) ($data['input'] ?? '');

        if (preg_match('/^product_[a-f0-9]{32}$/', $base) !== 1) {
            throw new PermanentMediaError('Invalid media name.');
        }

        if (!in_array($kind, ['image', 'video'], true)) {
            throw new PermanentMediaError('Invalid media kind.');
        }

        $dir  = realpath(self::incomingDir());
        $real = $input === '' ? false : realpath($input);

        if (
            $dir === false || $real === false || !is_file($real)
            || !str_starts_with($real, $dir . DIRECTORY_SEPARATOR)
            || !str_starts_with(basename($real), $base . '.')
        ) {
            throw new PermanentMediaError('Input file is missing or outside the incoming folder.');
        }

        return [
            'base'  => $base,
            'kind'  => $kind,
            'input' => $real,
            'final' => $base . ($kind === 'image' ? '.webp' : '.mp4'),
        ];
    }

    /**
     * @param array{base:string,kind:string,input:string,final:string} $job
     * @return array<string,array{path:string,type:string}>  object name => local file
     */
    public function process(array $job, string $workDir): array
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($job['input']);

        return $job['kind'] === 'image'
            ? $this->image($job, $workDir, (string) $mime)
            : $this->video($job, $workDir, (string) $mime);
    }

    /* ------------------------------------------------------------------ images */

    private function image(array $job, string $workDir, string $mime): array
    {
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new PermanentMediaError('Unsupported image type.');
        }

        $size = @getimagesize($job['input']);

        if ($size === false) {
            throw new PermanentMediaError('The image is corrupt or unreadable.');
        }

        [$width, $height] = $size;

        if ($width < 1 || $height < 1 || $width * $height > self::ABSOLUTE_MAX_PIXELS) {
            throw new PermanentMediaError('The image dimensions are not allowed.');
        }

        $main  = $workDir . '/' . $job['base'] . '.webp';
        $thumb = $workDir . '/' . $job['base'] . '_thumb.webp';

        if ($width * $height > self::GD_MAX_PIXELS) {

            // Too big for GD's memory: let FFmpeg decode, rotate and scale it.
            $this->ffmpegImage($job['input'], $main, self::IMAGE_WIDTH, 82);
            $this->ffmpegImage($job['input'], $thumb, self::THUMB_WIDTH, 78);

        } else {

            $this->gdImage($job['input'], $mime, $main, $thumb);
        }

        return [
            basename($main)  => ['path' => $main,  'type' => 'image/webp'],
            basename($thumb) => ['path' => $thumb, 'type' => 'image/webp'],
        ];
    }

    private function gdImage(string $input, string $mime, string $main, string $thumb): void
    {
        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($input),
            'image/png'  => @imagecreatefrompng($input),
            default      => @imagecreatefromwebp($input),
        };

        if ($source === false) {
            throw new PermanentMediaError('The image could not be decoded.');
        }

        try {

            if ($mime === 'image/jpeg') {
                $source = $this->applyOrientation($source, self::jpegOrientation($input));
            }

            foreach ([[self::IMAGE_WIDTH, 82, $main], [self::THUMB_WIDTH, 78, $thumb]] as [$maxWidth, $quality, $target]) {

                $resized = $this->resize($source, $maxWidth);

                try {

                    if (!imagewebp($resized, $target, $quality)) {
                        throw new RuntimeException('Could not write WebP file.');
                    }

                } finally {
                    imagedestroy($resized);
                }
            }

        } finally {
            imagedestroy($source);
        }
    }

    private function resize(GdImage $source, int $maxWidth): GdImage
    {
        $width  = imagesx($source);
        $height = imagesy($source);

        $newWidth  = min($width, $maxWidth);
        $newHeight = max(1, (int) round($height * ($newWidth / $width)));

        $target = imagecreatetruecolor($newWidth, $newHeight);

        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $target;
    }

    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        $replace = static function (GdImage $old, GdImage|false $new): GdImage {

            if ($new === false) {
                return $old;
            }

            imagedestroy($old);

            return $new;
        };

        switch ($orientation) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                return $image;
            case 3:
                return $replace($image, imagerotate($image, 180, 0));
            case 4:
                imageflip($image, IMG_FLIP_VERTICAL);
                return $image;
            case 5:
                $image = $replace($image, imagerotate($image, 90, 0));
                imageflip($image, IMG_FLIP_VERTICAL);
                return $image;
            case 6:
                return $replace($image, imagerotate($image, -90, 0));
            case 7:
                $image = $replace($image, imagerotate($image, -90, 0));
                imageflip($image, IMG_FLIP_VERTICAL);
                return $image;
            case 8:
                return $replace($image, imagerotate($image, 90, 0));
            default:
                return $image;
        }
    }

    /** EXIF orientation (1-8) read straight from the JPEG header; no ext-exif needed. */
    public static function jpegOrientation(string $path): int
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return 1;
        }

        $data = (string) fread($handle, 65536);
        fclose($handle);

        $length = strlen($data);

        if ($length < 12 || !str_starts_with($data, "\xFF\xD8")) {
            return 1;
        }

        $position = 2;

        while ($position + 4 <= $length && $data[$position] === "\xFF") {

            $marker = ord($data[$position + 1]);
            $size   = (ord($data[$position + 2]) << 8) | ord($data[$position + 3]);

            if ($marker === 0xDA) {
                break;
            }

            if ($marker === 0xE1 && substr($data, $position + 4, 6) === "Exif\0\0") {

                $tiff = $position + 10;

                if ($tiff + 8 > $length) {
                    return 1;
                }

                $little = substr($data, $tiff, 2) === 'II';

                $u16 = static fn (int $o): int => $o + 2 <= $length
                    ? (int) unpack($little ? 'v' : 'n', substr($data, $o, 2))[1] : 0;

                $u32 = static fn (int $o): int => $o + 4 <= $length
                    ? (int) unpack($little ? 'V' : 'N', substr($data, $o, 4))[1] : 0;

                $ifd   = $tiff + $u32($tiff + 4);
                $count = min($u16($ifd), 64);

                for ($i = 0; $i < $count; $i++) {

                    $entry = $ifd + 2 + $i * 12;

                    if ($u16($entry) === 0x0112) {
                        return max(1, min(8, $u16($entry + 8)));
                    }
                }

                return 1;
            }

            $position += 2 + $size;
        }

        return 1;
    }

    private function ffmpegImage(string $input, string $output, int $width, int $quality): void
    {
        $this->run([
            $this->ffmpeg(), '-nostdin', '-hide_banner', '-loglevel', 'error',
            '-protocol_whitelist', 'file', '-threads', '1',
            '-i', $input,
            '-vf', "scale='min($width,iw)':-2",
            '-frames:v', '1', '-c:v', 'libwebp', '-quality', (string) $quality,
            '-y', $output,
        ], 120);
    }

    /* ------------------------------------------------------------------ videos */

    private function video(array $job, string $workDir, string $mime): array
    {
        if (!in_array($mime, ['video/mp4', 'video/quicktime', 'video/webm'], true)) {
            throw new PermanentMediaError('Unsupported video type.');
        }

        $probe = json_decode($this->run([
            $this->ffprobe(), '-v', 'error', '-protocol_whitelist', 'file',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height:format=duration',
            '-of', 'json', $job['input'],
        ], 30), true);

        $stream = $probe['streams'][0] ?? null;

        if (!is_array($stream)) {
            throw new PermanentMediaError('The file has no video stream.');
        }

        $duration = (float) ($probe['format']['duration'] ?? 0);

        if ($duration <= 0 || $duration > self::MAX_VIDEO_SECONDS) {
            throw new PermanentMediaError('Videos can be at most ' . self::MAX_VIDEO_SECONDS . ' seconds long.');
        }

        if (
            (int) ($stream['width'] ?? 0) > self::MAX_VIDEO_SIDE
            || (int) ($stream['height'] ?? 0) > self::MAX_VIDEO_SIDE
        ) {
            throw new PermanentMediaError('The video resolution is too high.');
        }

        $main   = $workDir . '/' . $job['base'] . '.mp4';
        $poster = $workDir . '/' . $job['base'] . '_poster.webp';

        $this->run([
            $this->ffmpeg(), '-nostdin', '-hide_banner', '-loglevel', 'error',
            '-protocol_whitelist', 'file', '-threads', '1', '-filter_threads', '1',
            '-i', $job['input'],
            '-map', '0:v:0', '-map', '0:a:0?',
            '-vf', "scale='min(1280,iw)':-2",
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '27', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', '96k', '-ac', '2',
            '-movflags', '+faststart', '-y', $main,
        ], self::FFMPEG_TIMEOUT);

        $this->run([
            $this->ffmpeg(), '-nostdin', '-hide_banner', '-loglevel', 'error', '-threads', '1',
            '-ss', (string) min(1.0, $duration / 2), '-i', $main,
            '-frames:v', '1', '-vf', 'scale=' . self::POSTER_WIDTH . ':-2',
            '-c:v', 'libwebp', '-quality', '75', '-y', $poster,
        ], 60);

        return [
            basename($main)   => ['path' => $main,   'type' => 'video/mp4'],
            basename($poster) => ['path' => $poster, 'type' => 'image/webp'],
        ];
    }

    /* ------------------------------------------------------------------ process runner */

    private function ffmpeg(): string
    {
        return (string) Env::get('FFMPEG_BIN', '/usr/bin/ffmpeg');
    }

    private function ffprobe(): string
    {
        return (string) Env::get('FFPROBE_BIN', '/usr/bin/ffprobe');
    }

    /** Runs a command without a shell, with a hard time limit. Returns stdout. */
    private function run(array $command, int $timeout): string
    {
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['PATH' => '/usr/local/bin:/usr/bin:/bin', 'LC_ALL' => 'C']
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Could not start ' . basename($command[0]));
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout   = '';
        $stderr   = '';
        $deadline = microtime(true) + $timeout;
        $timedOut = false;
        $exit     = null;

        while (true) {

            $status = proc_get_status($process);

            $stdout .= (string) stream_get_contents($pipes[1]);
            $stderr .= substr((string) stream_get_contents($pipes[2]), 0, 4000);

            if (!$status['running']) {
                $exit = $status['exitcode'];
                break;
            }

            if (microtime(true) > $deadline) {
                $timedOut = true;
                proc_terminate($process, 9);
                break;
            }

            usleep(100_000);
        }

        $stdout .= (string) stream_get_contents($pipes[1]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $closed = proc_close($process);
        $exit ??= $closed;

        if ($timedOut) {
            throw new RuntimeException(basename($command[0]) . ' timed out after ' . $timeout . 's');
        }

        if ($exit !== 0) {
            throw new PermanentMediaError(
                'Media processing failed: ' . substr(trim($stderr), 0, 300)
            );
        }

        return $stdout;
    }
}
