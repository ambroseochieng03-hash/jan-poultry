<?php

declare(strict_types=1);

class UploadService
{
    private string $uploadPath;

    private int $maxImageSize = 31457280; // 30MB

    private int $maxVideoSize = 1073741824; // 1GB


    public function __construct()
    {
        $this->uploadPath =
            __DIR__
            . '/../../public/uploads/products/';
    }


    public function uploadMedia(
        array $file
    ): string {

        // Production: validate, hand the file to the media worker, return its final name.
        if (Media::driver() === 'r2') {
            return $this->stageForQueue($file);
        }


        $this->validateUpload($file);

        $mime =
        $this->detectMime(
            $file['tmp_name']
        );


        if (
            str_starts_with(
                $mime,
                'image/'
            )
        ) {

            return
            $this->processImage(
                $file['tmp_name'],
                $mime
            );

        }


        if (
            str_starts_with(
                $mime,
                'video/'
            )
        ) {

            return
            $this->processVideo(
                $file['tmp_name']
            );

        }


        throw new Exception(
            'Unsupported media type.'
        );
    }



/**
 * Queue mode: nothing heavy happens inside the web request. The file is checked,
 * moved to the private incoming folder, a job is published to JetStream, and the
 * FINAL file name (already known) is returned so the product can reference it.
 */
private function stageForQueue(array $file): string
{
    $this->validateUpload($file);

    $mime = $this->detectMime($file['tmp_name']);
    $size = (int) filesize($file['tmp_name']);

    $images = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $videos = ['video/mp4' => 'mp4', 'video/quicktime' => 'mov', 'video/webm' => 'webm'];

    if (isset($images[$mime])) {

        $kind = 'image';
        $ext  = $images[$mime];
        $max  = 15 * 1024 * 1024;

    } elseif (isset($videos[$mime])) {

        $kind = 'video';
        $ext  = $videos[$mime];
        $max  = 20 * 1024 * 1024;

    } else {

        throw new Exception('Unsupported file. Please upload a JPG, PNG or WebP image, or an MP4 video.');
    }

    if ($size < 1 || $size > $max) {
        throw new Exception('The file is too large (max ' . ($max / 1024 / 1024) . ' MB for ' . $kind . 's).');
    }

    $dir = rtrim((string) Env::get('MEDIA_INCOMING_DIR', '/var/lib/janpoultry/incoming'), '/');

    if (!is_dir($dir) || !is_writable($dir)) {
        throw new Exception('Media storage is not available right now. Please try again later.');
    }

    $base  = 'product_' . bin2hex(random_bytes(16));
    $final = $base . ($kind === 'image' ? '.webp' : '.mp4');
    $dest  = $dir . '/' . $base . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Upload failed.');
    }

    chmod($dest, 0640);

    try {

        require_once __DIR__ . '/MediaQueue.php';

        MediaQueue::publish([
            'v'     => 1,
            'kind'  => $kind,
            'base'  => $base,
            'input' => $dest,
            'ts'    => time(),
        ]);

    } catch (Throwable $e) {

        @unlink($dest);

        if (function_exists('jan_log')) {
            jan_log('ERROR', 'Media job could not be queued: ' . $e->getMessage());
        }

        throw new Exception('The media service is busy. Please try again in a moment.');
    }

    return $final;
}

private function validateUpload(
        array $file
    ): void {


        if (
            $file['error']
            !== UPLOAD_ERR_OK
        ) {

            throw new Exception(
                'Upload failed.'
            );

        }


        if (
            !is_uploaded_file(
                $file['tmp_name']
            )
        ) {

            throw new Exception(
                'Invalid upload.'
            );

        }

    }




    private function detectMime(
        string $file
    ): string {

        $finfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );

        return
        $finfo->file($file);

    }




    private function processImage(
        string $tmpFile,
        string $mime
    ): string {


        if (
            filesize($tmpFile)
            >
            $this->maxImageSize
        ) {

            throw new Exception(
                'Image too large.'
            );

        }



        $allowed =
        [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];



        if (
            !in_array(
                $mime,
                $allowed,
                true
            )
        ) {

            throw new Exception(
                'Invalid image format.'
            );

        }



        $filename =
        'product_'
        .
        bin2hex(
            random_bytes(16)
        )
        .
        '.webp';



        $destination =
        $this->uploadPath
        .
        $filename;



        if (
            !is_dir(
                $this->uploadPath
            )
        ) {

            mkdir(
                $this->uploadPath,
                0755,
                true
            );

        }



        $imageInfo =
        getimagesize(
            $tmpFile
        );


        if (
            !$imageInfo
        ) {

            throw new Exception(
                'Invalid image file.'
            );

        }



        switch($mime){

            case 'image/jpeg':

                $image =
                imagecreatefromjpeg(
                    $tmpFile
                );

            break;


            case 'image/png':

                $image =
                imagecreatefrompng(
                    $tmpFile
                );

            break;


            case 'image/webp':

                $image =
                imagecreatefromwebp(
                    $tmpFile
                );

            break;


            default:

                throw new Exception(
                    'Unsupported image.'
                );
        }



        imagewebp(
            $image,
            $destination,
            85
        );

        return $filename;

    }




    private function processVideo(
        string $tmpFile
    ): string {

        set_time_limit(900); //Gives 15 minutes to compress heavy videos        


        if (
            filesize($tmpFile)
            >
            $this->maxVideoSize
        ) {

            throw new Exception(
                'Video exceeds 1GB limit.'
            );

        }


        $filename =
        'product_'
        .
        bin2hex(
            random_bytes(16)
        )
        .
        '.mp4';


        $destination =
        $this->uploadPath
        .
        $filename;


        if (
            !is_dir(
                $this->uploadPath
            )
        ) {

            mkdir(
                $this->uploadPath,
                0755,
                true
            );

        }


        $command =
        sprintf(
            'ffmpeg -i %s -vf "scale=min(1920\\,iw):-2" -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
            escapeshellarg($tmpFile),
            escapeshellarg($destination)
        );


        exec(
            $command,
            $output,
            $status
        );


        if (
            $status !== 0
            ||
            !file_exists(
                $destination
            )
        ) {
            
            if (file_exists($destination)) {
                @unlink($destination);
            }

            throw new Exception(
                'Video processing failed.'
            );

        }


        return $filename;

    }

}