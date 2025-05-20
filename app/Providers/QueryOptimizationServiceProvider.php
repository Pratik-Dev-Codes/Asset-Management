<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class QueryOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../../config/query.php' => config_path('query.php'),
        ], 'config');

        // Only enable query logging in non-production environments or when explicitly enabled
        if (config('app.env') !== 'production' || config('query.enable_query_logging', false)) {
            if (config('query.log_slow_queries', true)) {
                $this->logSlowQueries();
            }

            if (config('query.enable_query_logging', false)) {
                $this->enableQueryLogging();
            }
        }

        // Always register these macros as they are helpful in all environments
        $this->addEagerLoadCountMacro();
        $this->addWithRelationsMacro();
    }

    /**
     * Log slow database queries.
     */
    protected function logSlowQueries(): void
    {
        $threshold = config('query.slow_query_threshold', 100); // milliseconds

        if ($threshold <= 0) {
            return; // Disabled
        }

        $logChannel = config('query.log_channel');
        $logger = $logChannel ? Log::channel($logChannel) : Log::getLogger();

        DB::whenQueryingForLongerThan(now()->addMilliseconds($threshold), function ($connection) use ($threshold, $logger) {
            $connection->listen(function ($query) use ($threshold, $logger) {
                if ($query->time > $threshold) {
                    $context = [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time.'ms',
                        'connection' => $query->connectionName,
                        'slow_query' => true,
                    ];

                    $logger->warning('Slow query detected', $context);
                }
            });
        });
    }

    /**
     * Enable query logging for all database connections.
     */
    protected function enableQueryLogging(): void
    {
        $logger = Log::getLogger();

        DB::listen(function ($query) use ($logger) {
            $context = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time.'ms',
                'connection' => $query->connectionName,
            ];

            $logger->debug('Query executed', $context);
        });
    }

    /**
     * Add a macro to eager load count of related models.
     */
    protected function addEagerLoadCountMacro(): void
    {
        Builder::macro('eagerLoadCountWithConstraints', function ($relations) {
            /** @var Model $this */
            $builder = $this;

            if (is_string($relations)) {
                $relations = func_get_args();
            }

            if (is_array($relations)) {
                foreach ($relations as $name => $constraints) {
                    if (is_numeric($name) && is_string($constraints)) {
                        $name = $constraints;
                        $constraints = null;
                    }

                    // Skip already loaded relations
                    if (is_string($name) && strpos($name, '.') === false && ! $builder->relationLoaded($name)) {
                        $constraintFn = is_callable($constraints) ? $constraints : null;
                        $builder->loadCount([$name => $constraintFn]);
                    }
                }
            }

            return $this;
        });
    }

    /**
     * Add a macro to load relations with constraints.
     */
    protected function addWithRelationsMacro(): void
    {
        Builder::macro('withRelations', function ($relations) {
            /** @var Builder $this */
            $builder = $this;

            if (is_string($relations)) {
                $relations = func_get_args();
            }

            if (is_array($relations)) {
                $with = [];

                foreach ($relations as $name => $constraints) {
                    if (is_numeric($name) && is_string($constraints)) {
                        $name = $constraints;
                        $constraints = null;
                    }

                    if (is_string($name)) {
                        if (is_callable($constraints)) {
                            $with[$name] = $constraints;
                        } else {
                            $with[] = $name;
                        }
                    }
                }

                if (! empty($with)) {
                    $builder->with($with);
                }
            }

            return $builder;
        });
    }
}
