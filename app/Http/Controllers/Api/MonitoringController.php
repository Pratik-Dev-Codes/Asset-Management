<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    /**
     * Get system health status.
     */
    public function health(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getHealthStatus();

        return response()->json($status);
    }

    /**
     * Get queue status.
     */
    public function queueStatus(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getQueueStatus();

        return response()->json($status);
    }

    /**
     * Get scheduled tasks status.
     */
    public function scheduledTasksStatus(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getScheduledTasksStatus();

        return response()->json($status);
    }

    /**
     * Get system information.
     */
    public function systemInfo(Request $request): JsonResponse
    {
        $info = \App\Facades\Monitor::getSystemStatus();

        return response()->json($info);
    }

    /**
     * Trigger a manual check of disk space.
     */
    public function checkDiskSpace(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::checkDiskSpace();

        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }

    /**
     * Trigger a manual check of queue health.
     */
    public function checkQueueHealth(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::checkQueueHealth();

        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }

    /**
     * Trigger a manual check of scheduled tasks.
     */
    public function checkScheduledTasks(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::checkScheduledTasks();

        return response()->json([
            'status' => 'success',
            'data' => $status,
        ]);
    }
}
