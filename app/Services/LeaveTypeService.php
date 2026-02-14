<?php

namespace App\Services;

use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class LeaveTypeService
{
    public function index()
    {
        return LeaveType::with(['creator'])
            ->latest()
            ->get();
    }

    public function store(array $data): LeaveType
    {
        return DB::transaction(function () use ($data) {
            return LeaveType::create([
                'name' => $data['name'],
                'is_active' => $data['is_active'] ?? false,
                'default_days' => $data['default_days'] ?? null,
                'gender' => $data['gender'] ?? null,
                'requires_family_status' => $data['requires_family_status'] ?? false,
            ]);
        });
    }

    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        return DB::transaction(function () use ($leaveType, $data) {

            $leaveType->update([
                'name' => $data['name'] ?? $leaveType->name,
                'is_active' => $data['is_active'] ?? $leaveType->is_active,
                'default_days' => $data['default_days'] ?? $leaveType->default_days,
                'gender' => $data['gender'] ?? $leaveType->gender,
                'requires_family_status' => $data['requires_family_status'] ?? $leaveType->requires_family_status,
            ]);

            return $leaveType;
        });
    }

    public function delete(LeaveType $leaveType): bool
    {
        return DB::transaction(function () use ($leaveType) {
            return (bool) $leaveType->delete();
        });
    }
}
