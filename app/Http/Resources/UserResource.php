<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'is_active' => $this->is_active,
            'system_reserve' => $this->system_reserve,

            'roles' => $this->whenLoaded(
                'roles',
                fn() => $this->roles->pluck('name')
            ),

            'can' => [
                'update' => $this->is_active && $user?->can('update', $this->resource),
                'terminate' => $this->is_active && $user?->can('terminate', $this->resource),
                'change_password' => $user?->can('changePassword', $this->resource),
            ],

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'nik' => $this->employee->nik,
                    'status' => $this->employee->status_label,

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

                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
                    'gender' => $this->employee->gender,
                ];
            }),
        ];
    }
}
