<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DivisionTeamEmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'division_name' => $this->name,
            'division_code' => $this->code,

            'teams' => $this->teams->map(function ($team) {
                return [
                    'uuid' => $team->uuid,
                    'team_name' => $team->name,

                    // Transformasi Anggota dengan data Employee penting
                    'members' => $team->employees->map(function ($employee) {
                        $user = $employee->user;
                        if (! $user) {
                            return null;
                        }

                        return [
                            'nik' => $employee->nik,
                            'name' => $user->name,
                            'email' => $user->email,
                            'phone' => $employee->phone,
                            // Data Jabatan & Status
                            'position' => $employee->position?->name ?? 'No Position',
                            'status' => [
                                'label' => $employee->status_label, // Dari accessor model
                                'type' => $employee->employee_status, // Enum value
                                'is_active' => $employee->isActive(),
                            ],
                            // Data Kontrak
                            'employment' => [
                                'join_date' => $employee->join_date?->format('d M Y'),
                                'years_of_service' => $employee->join_date?->diffInYears(now()),
                                'contract_due' => $employee->contract_end?->format('d M Y'),
                                'is_contract_ended' => $employee->hasContractEnded(),
                            ],
                            // Media / Avatar
                            'avatar' => $employee->getFirstMediaUrl('profile_photo') ?? null,
                        ];
                    })->filter()->values(),

                    'total_members' => $team->employees->count(),
                ];
            }),

            'stats' => [
                'total_teams' => $this->teams->count(),
                'total_employees' => $this->teams->sum(fn ($team) => $team->employees->count()),
            ],
        ];
    }
}
