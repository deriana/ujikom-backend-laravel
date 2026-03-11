<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SettingResource
 *
 * Resource class untuk mentransformasi model Setting menjadi format JSON,
 * termasuk penanganan khusus untuk file media seperti logo dan favicon.
 */
class SettingResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @param  Request  $request
     * @return array<string, mixed> Representasi data pengaturan berdasarkan key dan nilai-nilainya.
     */
    public function toArray(Request $request): array
    {
        $values = $this->values ?? [];

        if ($this->key === 'general') {
            $values['logo'] = $this->getFirstMediaUrl('logo') ?: ($values['logo'] ?? null);
            $values['favicon'] = $this->getFirstMediaUrl('favicon') ?: ($values['favicon'] ?? null);
        }

        return [
            'key' => $this->key, /**< Kunci unik kategori pengaturan (misal: general, attendance) */
            'values' => $values, /**< Kumpulan nilai konfigurasi dalam format key-value pair */
        ];
    }
}
