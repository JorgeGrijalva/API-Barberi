<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Gallery;
use App\Models\Settings;
use Illuminate\Http\UploadedFile;
use Throwable;

class FileHelper
{
    public const imageExtensions = [
        'png',
        'jpg',
        'jpeg',
        'webp',
        'svg',
        'jfif',
        'avif',
        'gif',
    ];

    /**
     * Upload file function
     * @param UploadedFile $file
     * @param string $path
     * @return array
     */
    public static function uploadFile(UploadedFile $file, string $path): array
    {
        try {
            $isAws = Settings::where('key', 'aws')->first();

            $options = [];

            if (data_get($isAws, 'value')) {
                $options = ['disk' => 's3'];
            }

            $id  = auth('sanctum')->id() ?? '0001';

            $ext  = $file->getClientOriginalExtension();

            $dir  = $ext;

            if (in_array($file->getClientOriginalExtension(), self::imageExtensions)) {

                $dir  = 'images';

                $ext = strtolower(
                    preg_replace('#.+\.([a-z]+)$#i', '$1',
                        str_replace(self::imageExtensions, '.webp', $file->getClientOriginalName())
                    )
                );

            }

            $time = time() . mt_rand(1000, 9999);
            $fileName = "$id-$time.$ext";

            $url = $file->storeAs("public/$dir/$path", $fileName, $options);

            return [
                'status' => true,
                'code'   => ResponseError::NO_ERROR,
                'data'   => config('app.img_host') . (!data_get($isAws, 'value') ? str_replace('public/', 'storage/', $url) : $url)
            ];
        } catch (Throwable $e) {

            $message = $e->getMessage();

            if ($message === "Class \"finfo\" not found") {
                $message = 'You need on php file info extension';
            }

            return [
                'status'  => false,
                'code'    => ResponseError::ERROR_400,
                'message' => $message
            ];
        }
    }

    /**
     * Delete file function
     * @param $path
     * @return mixed
     */
    public static function deleteFile($path): mixed
    {
        return Gallery::where('path', $path)->delete();
    }

}
