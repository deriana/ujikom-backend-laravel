<?php

namespace App\Services;

use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

class LeaveTypeService
{
    /**
     * Get all leave types with their creator information.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        // 1. Retrieve all leave types with eager loaded creator relationship
        return LeaveType::with(['creator'])
            ->latest()
            ->get();
    }

    /**
     * Store a new leave type record.
     *
     * @param array $data
     * @return LeaveType
     */
    public function store(array $data): LeaveType
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the leave type record in the database
            return LeaveType::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'default_days' => $data['default_days'] ?? null,
                'gender' => $data['gender'] ?? 'all',
                'requires_family_status' => $data['requires_family_status'] ?? false,
                'is_unlimited' => $data['is_unlimited'] ?? false,
            ]);
        });
    }

    /**
     * Update an existing leave type record.
     *
     * @param LeaveType $leaveType
     * @param array $data
     * @return LeaveType
     */
    public function update(LeaveType $leaveType, array $data): LeaveType
    {
        return DB::transaction(function () use ($leaveType, $data) {
            // 1. Update the leave type attributes with provided data or keep existing values
            $leaveType->update([
                'name' => $data['name'] ?? $leaveType->name,
                'description' => $data['description'] ?? $leaveType->description,
                'is_active' => $data['is_active'] ?? $leaveType->is_active,
                'default_days' => array_key_exists('default_days', $data) ? $data['default_days'] : $leaveType->default_days,
                'gender' => $data['gender'] ?? $leaveType->gender,
                'requires_family_status' => $data['requires_family_status'] ?? $leaveType->requires_family_status,
                'is_unlimited' => $data['is_unlimited'] ?? $leaveType->is_unlimited,
            ]);

            return $leaveType;
        });
    }

    /**
     * Delete a leave type record.
     *
     * @param LeaveType $leaveType
     * @return bool
     */
    public function delete(LeaveType $leaveType): bool
    {
        return DB::transaction(function () use ($leaveType) {
            // 1. Perform the deletion of the leave type record
            return (bool) $leaveType->delete();
        });
    }
}
