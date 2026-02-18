<?php

namespace App\Exceptions\Attendance;

class FaceValidationException extends AttendanceException
{
    public static function missingDescriptor(): self
    {
        return new self('Face descriptor not found.', ['reason' => 'descriptor_missing']);
    }

    public static function invalidFormat(): self
    {
        return new self('Invalid descriptor format.', ['reason' => 'descriptor_invalid_format']);
    }

    public static function notRecognized(float $score): self
    {
        return new self('Face not recognized.', [
            'reason' => 'face_not_recognized',
            'similarity_score' => $score,
        ]);
    }
}
