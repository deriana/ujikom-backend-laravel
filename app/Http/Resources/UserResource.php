<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'roles' => $this->whenLoaded('roles', fn () =>
                $this->roles->pluck('name')
            ),

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'nik' => $this->employee->nik,
                    'status' => $this->employee->status_label,
                    'base_salary' => $this->employee->base_salary,

                    'position' => [
                        'name' => $this->employee->position?->name,
                    ],

                    'team' => [
                        'name' => $this->employee->team?->name,
                        'division' => $this->employee->team?->division?->name,
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name,
                        'nik' => $this->employee->manager->nik,
                    ] : null,
                ];
            }),
        ];
    }
}
