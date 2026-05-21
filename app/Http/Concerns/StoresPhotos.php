<?php

namespace App\Http\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait StoresPhotos
{
    protected static function storePhoto(UploadedFile $file, string $folder, int $maxDimension = 1280, ?string $path = null): string
    {
        if ($path !== null) {
            $filename = $path;
        } else {
            $clientName = $file->getClientOriginalName();
            if (str_contains($clientName, 'sites/') && !str_contains($clientName, '..')) {
                $filename = $clientName;
            } else {
                $filename = $folder . '/' . Str::uuid() . '.jpg';
            }
        }

        // Ensure parent directory exists
        $dir = dirname($filename);
        if ($dir && $dir !== '.') {
            Storage::disk('public')->makeDirectory($dir);
        }

        // Try image compression if GD or Imagick is available
        $stored = false;
        if (extension_loaded('gd') || extension_loaded('imagick')) {
            try {
                $driver = extension_loaded('gd') ? 'gd' : 'imagick';
                $manager = \Intervention\Image\ImageManager::{$driver}();
                $image = $manager->read($file);
                $image->scaleDown(width: $maxDimension, height: $maxDimension);
                $jpeg = $image->toJpeg(quality: 75)->toString();
                Storage::disk('public')->put($filename, $jpeg);
                $stored = true;
            } catch (\Throwable $e) {
                // fall through to raw store
            }
        }

        // Fallback: store raw file as-is (no resize)
        if (!$stored) {
            $fullPath = storage_path('app/public/' . $filename);
            $dirPath  = dirname($fullPath);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0775, true);
            }
            file_put_contents($fullPath, file_get_contents($file->getRealPath()));
        }

        return $filename;
    }
}
