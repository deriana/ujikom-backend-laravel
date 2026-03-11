<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct(private SettingService $settingService) {}

    public function get(): JsonResponse
    {
        $settings = $this->settingService->getMany([
            'general',
            'attendance',
            'geo_fencing',
        ]);

        // $this->authorize('viewAny', Setting::class);

        return $this->successResponse(
            SettingResource::collection($settings),
            'Settings fetched successfully'
        );
    }

    public function getGeneral(): JsonResponse
    {
        $settings = $this->settingService->getMany([
            'general',
        ]);

        return $this->successResponse(
            SettingResource::collection($settings),
            'Settings fetched successfully'
        );
    }

    public function updateGeneral(Request $request): JsonResponse
    {
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'site_name' => 'required|string|max:100',
            'footer' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'favicon' => 'nullable|image|mimes:png,ico|max:1024',
        ]);

        $setting = $this->settingService->get('general');

        $currentValues = $setting->values ?? [];

        $setting->update([
            'values' => [
                ...$currentValues,
                'site_name' => $validated['site_name'],
                'footer' => $validated['footer'],
            ],
        ]);

        if ($request->hasFile('logo')) {
            $setting->clearMediaCollection('logo');
            $setting->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        if ($request->hasFile('favicon')) {
            $setting->clearMediaCollection('favicon');
            $setting->addMediaFromRequest('favicon')->toMediaCollection('favicon');
        }

        return $this->successResponse(
            new SettingResource($setting->fresh()),
            'General setting updated successfully'
        );
    }

    public function updateAttendance(Request $request): JsonResponse
    {
        $setting = $this->settingService->get('attendance');
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'late_tolerance_minutes' => 'required|integer|min:0|max:180',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i|after:work_start_time',
        ]);

        $updated = $this->settingService->update('attendance', $validated);

        return $this->successResponse(
            new SettingResource($updated),
            'Attendance setting updated successfully'
        );
    }

    public function geoFencing(): JsonResponse
    {
        $setting = $this->settingService->get('geo_fencing');
        $this->authorize('view', $setting);

        return $this->successResponse(
            new SettingResource($setting),
            'Geo fencing setting fetched successfully'
        );
    }

    public function updateGeoFencing(Request $request): JsonResponse
    {
        $setting = $this->settingService->get('geo_fencing');
        $this->authorize('update', Setting::class);

        $validated = $request->validate([
            'office_latitude' => 'required|numeric|between:-90,90',
            'office_longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:10|max:2000',
        ]);

        $updated = $this->settingService->update('geo_fencing', $validated);

        return $this->successResponse(
            new SettingResource($updated),
            'Geo fencing setting updated successfully'
        );
    }
}
