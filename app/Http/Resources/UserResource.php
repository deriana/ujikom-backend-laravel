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
                fn () => $this->roles->pluck('name')
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
                    'join_date' => $this->employee->join_date?->format('Y-m-d'),
                    'resign_date' => $this->employee->resign_date?->format('Y-m-d'),
                    'contract_start' => $this->employee->contract_start?->format('Y-m-d'),
                    'contract_end' => $this->employee->contract_end?->format('Y-m-d'),
                    'base_salary' => $this->employee->base_salary,
                    'date_of_birth' => $this->employee->date_of_birth?->format('Y-m-d'),
                    'address' => $this->employee->address,
                    'employment_state' => $this->employee->employment_state,
                    'has_face_descriptor' => $this->employee->has_face_descriptor,

                    'position' => [
                        'name' => $this->employee->position?->name,
                        'base_salary' => $this->employee->position?->base_salary,
                        'allowances' => $this->employee->position?->allowances->map(function ($allowance) {
                            return [
                                'name' => $allowance->name,
                                'type' => $allowance->type,
                                'amount' => $allowance->pivot->amount ?? $allowance->amount,
                            ];
                        }),
                    ],
                    'team' => [
                        'name' => $this->employee->team?->name,
                        'division' => $this->employee->team?->division?->name,
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name,
                        'nik' => $this->employee->manager->nik,
                    ] : null,

                    'phone' => $this->employee->phone,
                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
                    'gender' => $this->employee->gender,
                ];
            }),
        ];
    }
}
