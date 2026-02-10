<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'employee' => [
                'nik' => $this->employee->nik ?? null,
                'name' => $this->employee->user->name ?? null,
                'email' => $this->employee->user->email ?? null,
                'profile_photo' => $this->employee->getFirstMediaUrl('profile_photo') ?: null,
            ],
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status,
            'clock_in' => $this->clock_in?->format('Y-m-d H:i:s'),
            'clock_out' => $this->clock_out?->format('Y-m-d H:i:s'),
            'late_minutes' => $this->late_minutes,
            'early_leave_minutes' => $this->early_leave_minutes,
            'work_minutes' => $this->work_minutes,
            'overtime_minutes' => $this->overtime_minutes,
            'clock_in_photo' => $this->clock_in_photo ? asset($this->clock_in_photo) : null,
            'clock_out_photo' => $this->clock_out_photo ? asset($this->clock_out_photo) : null,
            'location_in' => [
                'latitude' => $this->latitude_in,
                'longitude' => $this->longitude_in,
            ],
            'location_out' => [
                'latitude' => $this->latitude_out,
                'longitude' => $this->longitude_out,
            ],
        ];
    }
}
