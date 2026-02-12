<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\FaceValidationException;
use App\Models\BiometricUser;
use App\Models\Employee;

class FaceValidator
{
    protected float $threshold = 0.75;

    public function validate(?array $inputDescriptor): array
    {
        if (empty($inputDescriptor)) {
            throw new FaceValidationException('Descriptor wajah tidak ditemukan.', ['reason' => 'descriptor_missing']);
        }

        $match = $this->findBestMatch($inputDescriptor);

        if (! $match['employee']) {
            throw FaceValidationException::notRecognized($match['score']);
        }

        return $match;
    }

    protected function findBestMatch(array $inputDescriptor): array
    {
        // Eager load employee to avoid N+1
        $biometrics = BiometricUser::with('employee')->get();

        $bestScore = -1;
        $matchedEmployee = null;

        foreach ($biometrics as $bio) {
            $storedDescriptor = $bio->descriptor;

            // Ensure format is compatible
            if (! is_array($storedDescriptor) || count($storedDescriptor) !== count($inputDescriptor)) {
                continue;
            }

            $score = $this->cosineSimilarity($inputDescriptor, $storedDescriptor);

            if ($score > $bestScore) {
                $bestScore = $score;
                $matchedEmployee = $bio->employee;
            }
        }

        return [
            'employee' => ($bestScore > $this->threshold) ? $matchedEmployee : null,
            'score' => $bestScore,
        ];
    }

    protected function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }

        $dot = 0;
        $normA = 0;
        $normB = 0;

        foreach ($a as $i => $val) {
            $dot += $val * $b[$i];
            $normA += $val ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
