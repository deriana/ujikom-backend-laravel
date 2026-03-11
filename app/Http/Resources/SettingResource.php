<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $values = $this->values ?? [];

        if ($this->key === 'general') {
            $values['logo'] = $this->getFirstMediaUrl('logo') ?: ($values['logo'] ?? null);
            $values['favicon'] = $this->getFirstMediaUrl('favicon') ?: ($values['favicon'] ?? null);
        }

        return [
            'key' => $this->key,
            'values' => $values,
        ];
    }
}
