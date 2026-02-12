<?php

namespace App\Services\Attendance\Internal;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceUploader
{
    public function upload($photo, string $employeeId, Carbon $date): ?string
    {
        if (! $photo) {
            return null;
        }

        $folderPath = 'attendance/'.$date->format('Y/m/d');
        $fileName = 'photo_'.$employeeId.'_'.now()->timestamp.'_'.uniqid();

        // 1. Handle UploadedFile instance
        if ($photo instanceof UploadedFile) {
            $ext = $photo->getClientOriginalExtension() ?: 'jpg';
            $path = $photo->storeAs($folderPath, $fileName.'.'.$ext, 'public');

            return Storage::url($path);
        }

        // 2. Handle Base64 String
        if (is_string($photo) && str_contains($photo, 'base64')) {
            return $this->uploadBase64($photo, $folderPath, $fileName);
        }

        return null;
    }

    protected function uploadBase64(string $base64String, string $folderPath, string $fileName): ?string
    {
        $base64 = explode(',', $base64String)[1] ?? null;
        if (! $base64) {
            return null;
        }

        $decoded = base64_decode($base64);
        $finfo = finfo_open();
        $mime = finfo_buffer($finfo, $decoded, FILEINFO_MIME_TYPE);

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null
        };

        if (! $ext) {
            Log::warning('Invalid attendance photo mime type', ['mime' => $mime]);

            return null;
        }

        $fullPath = $folderPath.'/'.$fileName.'.'.$ext;
        Storage::disk('public')->put($fullPath, $decoded);

        return $fullPath; // Return internal path or URL depending on requirement, usually URL or relative path
    }
}
