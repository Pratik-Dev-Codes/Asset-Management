<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void checkDiskSpace()
 * @method static void checkQueueHealth()
 * @method static void checkScheduledTasks()
 * @method static array getHealthStatus()
 * @method static array getQueueStatus()
 * @method static array getScheduledTasksStatus()
 * @method static array getSystemStatus()
 *
 * @see \App\Services\MonitorService
 */
class Monitor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'monitor';
    }
}
