<?php

namespace App\Services\Attendance\Internal;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class AttendanceUploader
 *
 * Menangani pengunggahan foto kehadiran baik dari file fisik maupun string Base64.
 */
class AttendanceUploader
{
    /**
     * Mengunggah foto kehadiran ke penyimpanan publik.
     *
     * @param mixed $photo File yang diunggah (UploadedFile) atau string Base64.
     * @param string $employeeId ID karyawan untuk penamaan file.
     * @param Carbon $date Objek tanggal untuk struktur folder.
     * @return string|null URL atau path file yang berhasil diunggah, atau null jika gagal.
     */
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

    /**
     * Memproses dan menyimpan data gambar dalam format Base64.
     *
     * @param string $base64String String gambar berformat base64.
     * @param string $folderPath Path folder tujuan penyimpanan.
     * @param string $fileName Nama file tanpa ekstensi.
     * @return string|null Path internal file yang disimpan, atau null jika tipe mime tidak valid.
     */
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
            // Log::warning('Invalid attendance photo mime type', ['mime' => $mime]);

            return null;
        }

        $fullPath = $folderPath.'/'.$fileName.'.'.$ext;
        Storage::disk('public')->put($fullPath, $decoded);

        return $fullPath; // Return internal path or URL depending on requirement, usually URL or relative path
    }
}
