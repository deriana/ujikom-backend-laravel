<?php

namespace App\Services\Attendance\Validators;

use App\Exceptions\Attendance\FaceValidationException;
use App\Models\BiometricUser;
use App\Models\Employee;

/**
 * Class FaceValidator
 *
 * Menangani validasi pengenalan wajah menggunakan perbandingan vektor (cosine similarity).
 */
class FaceValidator
{
    /**
     * Skor kemiripan minimum yang diperlukan agar kecocokan wajah dianggap valid (0.0 hingga 1.0).
     */
    protected float $threshold = 0.90; /**< Ambang batas skor kemiripan */

    /**
     * Selisih minimum antara skor terbaik pertama dan kedua untuk mencegah ambiguitas.
     */
    protected float $minGap = 0.03; /**< Selisih minimum antar kandidat */

    /**
     * Memvalidasi deskriptor wajah input terhadap semua data biometrik yang terdaftar.
     *
     * @param array|null $inputDescriptor Vektor fitur wajah dari input.
     * @return array Data karyawan dan skor kemiripan jika ditemukan.
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
     * Memverifikasi apakah deskriptor wajah input cocok dengan biometrik terdaftar milik karyawan tertentu.
     *
     * @param Employee $employee Objek karyawan yang akan diverifikasi.
     * @param array|null $inputDescriptor Vektor fitur wajah dari input.
     * @return array Data karyawan dan skor kemiripan.
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
     * Memastikan bahwa deskriptor yang diberikan tidak kosong.
     *
     * @param array|null $inputDescriptor Vektor fitur wajah.
     *
     * @throws FaceValidationException
     */
    protected function ensureDescriptorExists(?array $inputDescriptor): void
    {
        if (empty($inputDescriptor)) {
            throw new FaceValidationException('Face descriptor not found.', ['reason' => 'descriptor_missing']);
        }
    }

    /**
     * Mencari kecocokan terbaik dari semua deskriptor biometrik yang tersimpan.
     *
     * @param array $inputDescriptor Vektor fitur wajah dari input.
     * @return array Hasil pencocokan terbaik (karyawan dan skor).
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
     * Menghitung cosine similarity antara dua vektor numerik.
     *
     * @param array $a Vektor pertama.
     * @param array $b Vektor kedua.
     * @return float Nilai kemiripan antara 0.0 hingga 1.0.
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
