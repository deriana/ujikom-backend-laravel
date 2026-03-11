<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateAssessmentRequest
 *
 * Request class untuk menangani validasi pembuatan data penilaian (Assessment) karyawan.
 */
class CreateAssessmentRequest extends FormRequest
{
    /**
     * Menentukan apakah pengguna memiliki izin untuk membuat request ini.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mendapatkan aturan validasi yang berlaku untuk request ini.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'evaluatee_nik' => 'required|exists:employees,nik',
            'period'        => 'required|string', // Karena dari frontend biasanya format YYYY-MM
            'note'          => 'nullable|string',

            // Validasi untuk array assessment_details
            'assessment_details'                 => 'required|array|min:1',
            'assessment_details.*.category_uuid' => 'required|exists:assessment_categories,uuid',
            'assessment_details.*.score'         => 'required|integer|min:1|max:5',
            'assessment_details.*.bonus_salary'  => 'nullable|numeric',
        ];
    }

    /**
     * Mendefinisikan atribut kustom untuk pesan kesalahan validasi agar lebih mudah dibaca.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'assessment_details.*.score' => 'score',
            'assessment_details.*.category_uuid' => 'category',
        ];
    }
}
