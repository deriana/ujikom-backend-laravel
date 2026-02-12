<?php

namespace App\Exceptions\Attendance;

class FaceValidationException extends AttendanceException
{
    public static function missingDescriptor(): self
    {
        return new self('Descriptor wajah tidak ditemukan.', ['reason' => 'descriptor_missing']);
    }

    public static function invalidFormat(): self
    {
        return new self('Format descriptor tidak valid.', ['reason' => 'descriptor_invalid_format']);
    }

    public static function notRecognized(float $score): self
    {
        return new self('Wajah tidak dikenali.', [
            'reason' => 'face_not_recognized',
            'similarity_score' => $score,
        ]);
    }
}
