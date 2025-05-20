<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class SystemSettingController extends BaseApiController
{
    /**
     * Get all system settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $settings = Cache::remember('system_settings', 3600, function () {
                return SystemSetting::all()->pluck('value', 'key')->toArray();
            });

            return $this->success(
                $settings,
                'System settings retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch system settings: ' . $e->getMessage());
            return $this->error('Failed to retrieve system settings', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get system settings by group.
     *
     * @param string $group
     * @return JsonResponse
     */
    public function getByGroup(string $group): JsonResponse
    {
        try {
            $settings = Cache::remember("system_settings_{$group}", 3600, function () use ($group) {
                return SystemSetting::where('group', $group)
                    ->get()
                    ->pluck('value', 'key')
                    ->toArray();
            });

            return $this->success(
                $settings,
                'System settings retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch system settings: ' . $e->getMessage());
            return $this->error('Failed to retrieve system settings', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specific system setting.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        try {
            $setting = Cache::remember("system_setting_{$key}", 3600, function () use ($key) {
                return SystemSetting::where('key', $key)->first();
            });

            if (!$setting) {
                return $this->error('Setting not found', Response::HTTP_NOT_FOUND);
            }

            return $this->success(
                ['value' => $setting->value],
                'System setting retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch system setting: ' . $e->getMessage());
            return $this->error('Failed to retrieve system setting', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update system settings.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $settings = $request->validate([
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required',
            ])['settings'];

            $updated = [];
            
            foreach ($settings as $setting) {
                $result = SystemSetting::updateOrCreate(
                    ['key' => $setting['key']],
                    ['value' => $setting['value']]
                );
                $updated[] = $result;
                
                // Clear cache for this setting
                Cache::forget("system_setting_{$setting['key']}");
            }

            // Clear all settings cache
            Cache::forget('system_settings');
            
            // Clear group caches
            $groups = array_unique(array_map(function($item) {
                return explode('.', $item['key'])[0];
            }, $settings));
            
            foreach ($groups as $group) {
                Cache::forget("system_settings_{$group}");
            }

            return $this->success(
                $updated,
                'System settings updated successfully'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            Log::error('Failed to update system settings: ' . $e->getMessage());
            return $this->error('Failed to update system settings', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get system information.
     *
     * @return JsonResponse
     */
    public function systemInfo(): JsonResponse
    {
        try {
            $info = [
                'app' => [
                    'name' => config('app.name'),
                    'env' => config('app.env'),
                    'debug' => config('app.debug'),
                    'url' => config('app.url'),
                    'version' => config('app.version', '1.0.0'),
                ],
                'php' => [
                    'version' => PHP_VERSION,
                    'os' => PHP_OS,
                    'server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
                ],
                'database' => [
                    'connection' => config('database.default'),
                    'version' => \DB::select('select version() as version')[0]->version ?? null,
                ],
                'laravel' => [
                    'version' => app()->version(),
                    'timezone' => config('app.timezone'),
                    'locale' => config('app.locale'),
                ],
            ];

            return $this->success(
                $info,
                'System information retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Failed to fetch system information: ' . $e->getMessage());
            return $this->error('Failed to retrieve system information', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
