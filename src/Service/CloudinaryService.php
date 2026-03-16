<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $_ENV['CLOUDINARY_CLOUD_NAME'],
                'api_key'    => $_ENV['CLOUDINARY_API_KEY'],
                'api_secret' => $_ENV['CLOUDINARY_API_SECRET'],
            ],
            'url' => [
                'secure' => true
            ]
        ]);
    }

    /**
     * Upload a file to Cloudinary
     *
     * @param UploadedFile $file
     * @return string URL of uploaded file
     * @throws \Exception
     */
    public function upload(UploadedFile $file): string
    {
        // Check if the file is valid
        if (!$file->isValid()) {
            throw new \Exception('Uploaded file is invalid.');
        }

        // Get real path
        $path = $file->getRealPath();
        if (!$path || !is_readable($path)) {
            throw new \Exception('Cannot read uploaded file.');
        }

        // Upload
        $result = $this->cloudinary->uploadApi()->upload(
            $path,
            ['folder' => 'my_project'] // optional folder
        );

        if (!isset($result['secure_url'])) {
            throw new \Exception('Cloudinary upload failed.');
        }

        return $result['secure_url'];
    }
}
