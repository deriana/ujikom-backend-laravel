<?php

namespace App\Exceptions\Attendance;

/**
 * Class FaceValidationException
 *
 * Exception khusus untuk menangani kesalahan validasi wajah (Face Recognition) pada absensi.
 */
class FaceValidationException extends AttendanceException
{
    /**
     * Exception ketika deskriptor wajah tidak ditemukan dalam request.
     *
     * @return self
     */
    public static function missingDescriptor(): self
    {
        return new self('Face descriptor not found.', ['reason' => 'descriptor_missing']);
    }

    /**
     * Exception ketika format deskriptor wajah tidak valid atau tidak sesuai standar.
     *
     * @return self
     */
    public static function invalidFormat(): self
    {
        return new self('Invalid descriptor format.', ['reason' => 'descriptor_invalid_format']);
    }

    /**
     * Exception ketika wajah tidak dikenali atau skor kemiripan di bawah ambang batas.
     *
     * @param float $score Skor kemiripan yang dihasilkan
     * @return self
     */
    public static function notRecognized(float $score): self
    {
        return new self('Face not recognized.', [
            'reason' => 'face_not_recognized',
            'similarity_score' => $score,
        ]);
    }
}
