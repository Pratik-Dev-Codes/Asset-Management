<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    /**
     * Get system health status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function health(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getHealthStatus();
        
        return response()->json($status);
    }
    
    /**
     * Get queue status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queueStatus(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getQueueStatus();
        
        return response()->json($status);
    }
    
    /**
     * Get scheduled tasks status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function scheduledTasksStatus(Request $request): JsonResponse
    {
        $status = \App\Facades\Monitor::getScheduledTasksStatus();
        
        return response()->json($status);
    }
    
    /**
     * Get system information.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function systemInfo(Request $request): JsonResponse
    {
        $info = \App\Facades\Monitor::getSystemStatus();
        
        return response()->json($info);
    }
    
    /**
     * Trigger a manual check of disk space.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
