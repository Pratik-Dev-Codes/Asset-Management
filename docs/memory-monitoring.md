# Memory Monitoring System

This document provides an overview of the memory monitoring system implemented in the Asset Management application.

## Features

1. **Memory Usage Monitoring**
   - Real-time memory usage tracking
   - Threshold-based alerts
   - Detailed logging

2. **Queue Worker Management**
   - Automatic worker restart on high memory usage
   - Configurable memory limits
   - Process monitoring

3. **Log Management**
   - Automatic log rotation
   - Configurable retention period
   - Cleanup of old log files

## Configuration

### Environment Variables

```env
# Memory Monitoring
MEMORY_MONITOR_ENABLED=true
MEMORY_MONITOR_LOG_ALL=false
MEMORY_MONITOR_THRESHOLD=80
MEMORY_CLEANUP_ENABLED=true
MEMORY_CLEANUP_THRESHOLD=70
MEMORY_CLEANUP_INTERVAL=60
LOG_CLEANUP_ENABLED=true
LOG_CLEANUP_DAYS=30
LOG_CLEANUP_TIME=02:00

# Queue Worker Settings
QUEUE_WORKER_MEMORY_LIMIT=128
QUEUE_WORKER_TIMEOUT=3600
QUEUE_WORKER_SLEEP=3
QUEUE_WORKER_TRIES=3
```

### Configuration Files

- `config/memory_monitor.php` - Main configuration for memory monitoring
- `config/queue.php` - Queue worker configuration
- `app/Http/Middleware/MonitorMemoryUsage.php` - Web request memory monitoring
- `app/Console/Commands/MonitorQueueWorkers.php` - Queue worker monitoring
- `app/Console/Commands/CheckMemoryUsage.php` - Memory usage checking
- `app/Console/Commands/CleanupLogs.php` - Log file cleanup

## Scheduled Tasks

The following tasks are scheduled to run automatically:

1. **Queue Worker Monitoring**
   - Command: `queue:monitor`
   - Schedule: Every 5 minutes
   - Log: `storage/logs/queue-monitor.log`

2. **Memory Usage Check**
   - Command: `memory:check`
   - Schedule: Hourly
   - Log: `storage/logs/memory-usage.log`

3. **Log Cleanup**
   - Command: `logs:cleanup`
   - Schedule: Daily at 2:00 AM
   - Log: `storage/logs/log-cleanup.log`

## Usage

### Manual Memory Check

```bash
php artisan memory:check
```

### Manual Queue Worker Monitoring

```bash
php artisan queue:monitor
```

### Manual Log Cleanup

```bash
# Dry run (show what would be deleted)
php artisan logs:cleanup --dry-run

# Actual cleanup
php artisan logs:cleanup --days=30
```

## Monitoring Dashboard

To view memory usage statistics, you can use the following tools:

1. **Laravel Telescope** (if installed)
2. **Laravel Horizon** (for queue monitoring)
3. **Server Monitoring Tools** (e.g., New Relic, Datadog)

## Troubleshooting

### High Memory Usage

1. Check the logs in `storage/logs/memory-usage.log`
2. Review recent changes that might have introduced memory leaks
3. Consider increasing the memory limit in `.env` if needed

### Queue Workers Crashing

1. Check `storage/logs/queue-monitor.log` for errors
2. Verify the queue worker configuration in `config/queue.php`
3. Check system logs for out-of-memory errors

## Best Practices

1. **Monitor Regularly**
   - Check memory usage logs daily
   - Set up alerts for critical thresholds

2. **Optimize Code**
   - Use eager loading for relationships
   - Process large datasets in chunks
   - Unset unused variables

3. **Resource Management**
   - Set appropriate memory limits
   - Monitor queue worker performance
   - Clean up old logs and temporary files

4. **Testing**
   - Test with production-like data volumes
   - Monitor memory usage during peak loads
   - Perform load testing regularly
