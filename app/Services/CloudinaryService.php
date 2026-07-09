<?php

namespace App\Services;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Upload one or multiple images to Cloudinary and return secure URL(s).
     *
     * @param UploadedFile|UploadedFile[]|array $files
     * @param string $folder
     * @return array|string|null
     */
    public static function uploadImage($files, string $folder = 'cochons_dafrik'): array|string|null
    {
        // Auto-configure from CLOUDINARY_URL env variable
        Configuration::instance();

        if (is_array($files)) {
            $urls = [];
            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $url = self::uploadSingleFile($file, $folder);
                    if ($url) {
                        $urls[] = $url;
                    }
                }
            }
            return $urls;
        }

        if ($files instanceof UploadedFile) {
            return self::uploadSingleFile($files, $folder);
        }

        return null;
    }

    /**
     * Upload a single file and return its secure path.
     */
    private static function uploadSingleFile(UploadedFile $file, string $folder): ?string
    {
        try {
            $uploadApi = new UploadApi();
            $response = $uploadApi->upload($file->getRealPath(), [
                'folder' => $folder,
            ]);

            return $response['secure_url'] ?? null;
        } catch (\Exception $e) {
            Log::error('Cloudinary Upload Error: ' . $e->getMessage());
            return null;
        }
    }
}
