<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointLeaderboardDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this di sini adalah array yang kita return dari getLeaderboardDetail()
        return [
            'employee' => $this['employee'], // Data ringkasan employee
            'transactions' => PointDetailResource::collection($this['transactions']),
        ];
    }
}
