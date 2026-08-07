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