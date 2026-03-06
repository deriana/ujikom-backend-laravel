<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\FaceValidationException;
use App\Models\BiometricUser;
use App\Models\Employee;

class FaceValidator
{
    /**
     * Minimum similarity score required to consider a face match valid (0.0 to 1.0).
     */
    protected float $threshold = 0.90;

    /**
     * Minimum gap between the top match and the second-best match to prevent ambiguity.
     */
    protected float $minGap = 0.03;

    /**
     * Validate an input face descriptor against all registered biometric data.
     *
     * @throws FaceValidationException
     */
    public function validate(?array $inputDescriptor): array
    {
        if (empty($inputDescriptor)) {
            throw new FaceValidationException(
                'Face descriptor not found.',
                ['reason' => 'descriptor_missing']
            );
        }

        $match = $this->findBestMatch($inputDescriptor);

        if (! $match['employee']) {
            throw FaceValidationException::notRecognized($match['score']);
        }

        return $match;
    }

    /**
     * Verify if the input face descriptor matches a specific employee's registered biometrics.
     *
     * @throws FaceValidationException
     */
    public function verifyMatch(Employee $employee, ?array $inputDescriptor): array
    {
        $this->ensureDescriptorExists($inputDescriptor);

        $biometrics = BiometricUser::where('employee_id', $employee->id)->get();

        $bestScore = 0;
        foreach ($biometrics as $bio) {
            $score = $this->cosineSimilarity($inputDescriptor, $bio->descriptor);
            if ($score > $bestScore) {
                $bestScore = $score;
            }
        }

        if ($bestScore < $this->threshold) {
            throw new FaceValidationException(
                'Face match failed. Similarity: '.round($bestScore * 100, 2).'%',
                ['reason' => 'insufficient_similarity', 'score' => $bestScore]
            );
        }

        return ['employee' => $employee, 'score' => $bestScore];
    }

    /**
     * Ensure the provided descriptor is not empty.
     */
    protected function ensureDescriptorExists(?array $inputDescriptor): void
    {
        if (empty($inputDescriptor)) {
            throw new FaceValidationException('Face descriptor not found.', ['reason' => 'descriptor_missing']);
        }
    }

    /**
     * Iterate through all stored biometric descriptors to find the best matching employee
     * based on cosine similarity and defined thresholds.
     */
    protected function findBestMatch(array $inputDescriptor): array
    {
        $descriptors = BiometricUser::with('employee')->get();

        $scores = [];

        foreach ($descriptors as $desc) {
            $stored = $desc->descriptor;

            if (! is_array($stored) || count($stored) !== count($inputDescriptor)) {
                continue;
            }

            $score = $this->cosineSimilarity($inputDescriptor, $stored);
            $employeeId = $desc->employee_id;

            // Store the highest score per employee
            if (! isset($scores[$employeeId]) || $score > $scores[$employeeId]['score']) {
                $scores[$employeeId] = [
                    'employee' => $desc->employee,
                    'score' => $score,
                ];
            }
        }

        if (empty($scores)) {
            return ['employee' => null, 'score' => 0];
        }

        // Sort by highest score
        usort($scores, fn ($a, $b) => $b['score'] <=> $a['score']);

        $top1 = $scores[0];
        $top2 = $scores[1] ?? null;

        if (
            $top1['score'] >= $this->threshold &&
            (! $top2 || ($top1['score'] - $top2['score']) > $this->minGap)
        ) {
            return $top1;
        }

        return [
            'employee' => null,
            'score' => $top1['score'],
        ];
    }

    /**
     * Calculate the cosine similarity between two numerical vectors.
     */
    protected function cosineSimilarity(array $a, array $b): float
    {
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
