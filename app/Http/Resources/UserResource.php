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
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')
            ),

            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'nik' => $this->employee->nik,
                    'status' => $this->employee->status_label,
                    'employee_status' => $this->employee->employee_status,  
                    'base_salary' => $this->employee->base_salary,

                    'position' => [
                        'uuid' => $this->employee->position?->uuid,
                        'name' => $this->employee->position?->name,
                        'base_salary' => $this->employee->position?->base_salary,
                        'allowances' => $this->employee->position?->allowances->map(function ($allowance) {
                            return [
                                'name' => $allowance->name,
                                'amount' => $allowance->amount,
                            ];
                        }),
                    ],

                    'team' => [
                        'uuid' => $this->employee->team?->uuid,
                        'name' => $this->employee->team?->name,
                        'division' => $this->employee->team?->division?->name,
                    ],

                    'manager' => $this->employee->manager ? [
                        'name' => $this->employee->manager->user?->name,
                        'nik' => $this->employee->manager->nik,
                    ] : null,

                    // tambahan fields baru
                    'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
                    'phone' => $this->employee->phone,
                    'gender' => $this->employee->gender,
                    'date_of_birth' => $this->employee->date_of_birth?->format('Y-m-d'),
                    'address' => $this->employee->address,
                    'join_date' => $this->employee->join_date?->format('Y-m-d'),
                    'resign_date' => $this->employee->resign_date?->format('Y-m-d'),
                    'contract_start' => $this->employee->contract_start?->format('Y-m-d'),
                    'contract_end' => $this->employee->contract_end?->format('Y-m-d'),
                    'employment_state' => $this->employee->employment_state,
                    'termination_date' => $this->employee->termination_date?->format('Y-m-d'),
                    'termination_reason' => $this->employee->termination_reason,
                ];
            }),
        ];
    }
}
