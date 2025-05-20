<?php

namespace App\Models;

use App\Exports\ReportExport;
use App\Models\User;
use App\Models\ReportFile;
use App\Models\Transaction;
use App\Notifications\{
    ReportGenerated,
    ReportGenerationFailed
};
use App\Traits\HasAuthorization;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\{
    Model,
    SoftDeletes,
    Factories\HasFactory,
    Relations\BelongsTo,
    Relations\HasMany,
    Builder,
    Collection
};
use Illuminate\Support\Facades\{
    Auth,
    Cache,
    Config,
    DB,
    Event,
    Log,
    Mail,
    Queue,
    Redis,
    Storage,
    Validator
};
use Illuminate\Support\Str;
use Illuminate\Validation\{
    Rule,
    ValidationException
};
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Throwable;

/**
 * Class Report
 *
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property array|null $filters
 * @property array $columns
 * @property array|null $sorting
 * @property array|null $grouping
 * @property bool $is_public
 * @property int $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection|ReportFile[] $files
 * @property string $report_type
 * @property string $status
 * @property bool $is_scheduled
 * @property string|null $schedule_frequency
 * @property string|null $recipients
 * @property-read int|null $files_count
 * @property-read string|null $download_url
 * @property-read ReportFile|null $latestFile
 * @method static \Illuminate\Database\Eloquent\Builder|Report accessibleBy($userId)
 * @method static \Database\Factories\ReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|Report newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Report newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Report onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Report query()
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereColumns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereFilters($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereIsScheduled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereRecipients($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereReportType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereScheduleFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Report withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Report withoutTrashed()
 * @mixin \Eloquent
 */
class Report extends Model
{
    use HasFactory, SoftDeletes, HasAuthorization;

    /**
     * Report types and their allowed columns.
     *
     * @var array
     */
    public const TYPES = [
        'asset' => [
            'id', 'name', 'description', 'status', 'purchase_date', 'purchase_cost',
            'warranty_months', 'depreciation', 'supplier_id', 'location_id', 'assigned_to', 'created_at', 'updated_at'
        ],
        'user' => [
            'id', 'name', 'email', 'username', 'employee_num', 'manager_id', 'department_id',
            'location_id', 'phone', 'jobtitle', 'created_at', 'updated_at'
        ],
        'accessory' => [
            'id', 'name', 'category_id', 'supplier_id', 'location_id', 'purchase_date',
            'purchase_cost', 'order_number', 'min_amt', 'qty', 'created_at', 'updated_at'
        ]
    ];

    /**
     * Validation rules for report attributes.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|string|in:asset,user,accessory',
        'filters' => 'nullable|array',
        'columns' => 'required|array|min:1',
        'columns.*' => 'required|string|distinct',
        'sorting' => 'nullable|array',
        'grouping' => 'nullable|array',
        'is_public' => 'boolean',
        'created_by' => 'required|exists:users,id',
        'schedule_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
    ];

    /**
     * Custom validation messages.
     *
     * @var array
     */
    public static $messages = [
        'columns.required' => 'At least one column must be selected for the report.',
        'columns.*.in' => 'The selected column :input is not valid for this report type.',
        'type.in' => 'The selected report type is invalid. Must be one of: asset, user, accessory.',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'filters',
        'columns',
        'sorting',
        'grouping',
        'is_public',
        'created_by',
        'schedule_frequency',
        'last_generated_at',
        'next_run_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'sorting' => 'array',
        'grouping' => 'array',
        'is_public' => 'boolean',
        'last_generated_at' => 'datetime',
        'next_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_scheduled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_by',
        'deleted_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'download_url',
        'is_scheduled',
        'status',
        'next_run',
    ];

    /**
     * Check if the report is scheduled.
     *
     * @return bool
     */
    public function getIsScheduledAttribute(): bool
    {
        return !empty($this->schedule_frequency);
    }

    /**
     * Validate the model's attributes.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    public function validate(): void
    {
        $validator = Validator::make(
            $this->attributesToArray(),
            static::$rules,
            static::$messages
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Validate columns against report type
        $this->validateColumns();
        
        // Validate filters if present
        if (!empty($this->filters)) {
            $this->validateFilters();
        }
    }

    /**
     * Validate report columns against the allowed columns for the report type.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    protected function validateColumns(): void
    {
        $allowedColumns = self::TYPES[$this->type] ?? [];
        
        $invalidColumns = array_diff($this->columns, $allowedColumns);
        
        if (!empty($invalidColumns)) {
            throw ValidationException::withMessages([
                'columns' => [
                    'The following columns are not valid for the selected report type: ' . 
                    implode(', ', $invalidColumns)
                ]
            ]);
        }
    }

    /**
     * Validate report filters.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @return void
     */
    protected function validateFilters(): void
    {
        $rules = [
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string|in:=,!=,>,<,>=,<=,like,not like,in,not in,between,not between',
            'filters.*.value' => 'required',
        ];

        $validator = Validator::make(
            ['filters' => $this->filters],
            $rules,
            [
                'filters.*.field.required' => 'Filter field is required',
                'filters.*.operator.in' => 'Invalid filter operator',
                'filters.*.value.required' => 'Filter value is required',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Generate a cache key for the report.
     *
     * @param string $suffix
     * @return string
     */
    public function getReportCacheKey(string $suffix = ''): string
    {
        return "report_{$this->id}" . ($suffix ? "_$suffix" : '');
    }

    /**
     * Clear all cached data for this report.
     *
     * @return bool
     */
    public function clearReportCache(): bool
    {
        try {
            $cacheKey = $this->getReportCacheKey('*');
            $keys = Redis::connection()->keys($cacheKey);
            
            if (!empty($keys)) {
                Redis::connection()->del($keys);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear report cache', [
                'report_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the report data with caching.
     *
     * @param bool $forceRefresh
     * @return array
     */
    public function getData(bool $forceRefresh = false): array
    {
        $cacheKey = $this->getCacheKey('data');
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, now()->addHour(), function () {
            try {
                // Implement your data retrieval logic here
                return [];
            } catch (\Exception $e) {
                Log::error('Failed to generate report data', [
                    'report_id' => $this->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = $model->created_by ?: auth()->id();
        });

        static::saving(function ($model) {
            $model->validateReportData();
        });

        static::saved(function ($model) {
            $model->clearCache();
        });

        static::deleted(function ($model) {
            $model->clearCache();
        });
    }

    /**
     * Get the user that created the report.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the files associated with the report.
     */
    public function files(): HasMany
    {
        return $this->hasMany(ReportFile::class);
    }

    /**
     * Get the latest file associated with the report.
     */
    public function latestFile()
    {
        return $this->hasOne(ReportFile::class)->latest();
    }

    /**
     * Get the URL to download the latest export.
     *
     * @return string|null
     */
    public function getDownloadUrlAttribute(): ?string
    {
        $file = $this->latestFile;
        return $file ? Storage::url($file->file_path) : null;
    }

    /**
     * Validate the report data before saving.
     *
     * @throws ValidationException
     */
    public function validateReportData(): void
    {
        $validator = Validator::make(
            $this->attributesToArray(),
            $this->getValidationRules(),
            $this->getValidationMessages()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get the validation rules for the report.
     *
     * @return array
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', 'string', Rule::in(['asset', 'user', 'transaction', 'custom'])],
            'filters' => 'nullable|array',
            'columns' => 'required|array|min:1',
            'columns.*' => 'required|string|distinct',
            'sorting' => 'nullable|array',
            'sorting.column' => 'required_with:sorting|string',
            'sorting.direction' => 'required_with:sorting|in:asc,desc',
            'grouping' => 'nullable|array',
            'is_public' => 'boolean',
            'created_by' => 'required|exists:users,id',
            'schedule_frequency' => 'nullable|in:daily,weekly,monthly,quarterly,yearly',
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array
     */
    protected function getValidationMessages(): array
    {
        return [
            'name.required' => 'The report name is required.',
            'type.required' => 'The report type is required.',
            'type.in' => 'The selected report type is invalid.',
            'columns.required' => 'At least one column must be selected for the report.',
            'columns.min' => 'At least one column must be selected for the report.',
            'created_by.exists' => 'The selected creator is invalid.',
        ];
    }

    /**
     * Scope a query to only include public reports or reports created by the given user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleBy($query, $userId)
    {
        return $query->where('is_public', true)
            ->orWhere('created_by', $userId);
    }

    /**
     * Generate the report data based on the report configuration.
     *
     * @return array
     * @throws \Exception
     */
    public function generateData(): array
    {
        try {
            $query = $this->buildBaseQuery();
            $query = $this->applyFilters($query);
            $query = $this->applySorting($query);
            $query = $this->applyGrouping($query);

            return $query->get()->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to generate report data', [
                'report_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Build the base query based on the report type.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function buildBaseQuery()
    {
        $model = $this->getModelForType();
        return $model::query();
    }

    /**
     * Get the model class for the report type.
     *
     * @return string
     * @throws \Exception
     */
    protected function getModelForType(): string
    {
        $models = [
            'asset' => Asset::class,
            'user' => User::class,
            'transaction' => Transaction::class,
        ];

        if (!isset($models[$this->type])) {
            throw new \Exception("Unsupported report type: {$this->type}");
        }

        return $models[$this->type];
    }

    /**
     * Apply filters to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applyFilters($query)
    {
        if (empty($this->filters)) {
            return $query;
        }

        foreach ($this->filters as $filter) {
            $query = $this->applyFilter($query, $filter);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $filter
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applyFilter($query, array $filter)
    {
        $column = $filter['column'] ?? null;
        $operator = $filter['operator'] ?? '=';
        $value = $filter['value'] ?? null;

        if (!$column || !$this->isValidColumn($column)) {
            return $query;
        }

        switch (strtolower($operator)) {
            case 'in':
                return $query->whereIn($column, (array)$value);
            case 'not_in':
                return $query->whereNotIn($column, (array)$value);
            case 'between':
                return $query->whereBetween($column, (array)$value);
            case 'not_between':
                return $query->whereNotBetween($column, (array)$value);
            case 'null':
                return $query->whereNull($column);
            case 'not_null':
                return $query->whereNotNull($column);
            case 'like':
                return $query->where($column, 'like', "%{$value}%");
            case 'not_like':
                return $query->where($column, 'not like', "%{$value}%");
            default:
                return $query->where($column, $operator, $value);
        }
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applySorting($query)
    {
        if (empty($this->sorting) || empty($this->sorting['column'])) {
            return $query;
        }

        $column = $this->sorting['column'];
        $direction = $this->sorting['direction'] ?? 'asc';

        if ($this->isValidColumn($column)) {
            return $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Apply grouping to the query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Query\Builder
     */
    protected function applyGrouping($query)
    {
        if (empty($this->grouping) || empty($this->grouping['columns'])) {
            return $query;
        }

        $columns = array_filter((array)$this->grouping['columns'], [$this, 'isValidColumn']);
        
        if (empty($columns)) {
            return $query;
        }

        return $query->groupBy($columns);
    }

    /**
     * Check if a column is valid for the current report type.
     *
     * @param  string  $column
     * @return bool
     */
    protected function isValidColumn(string $column): bool
    {
        $model = $this->getModelForType();
        $model = new $model;
        
        return in_array($column, $model->getFillable()) || 
               in_array($column, $model->getDates()) ||
               $column === $model->getKeyName();
    }

    /**
     * Generate a cache key for the report.
     *
     * @param  string  $suffix
     * @return string
     */
    protected function getCacheKey(string $suffix = ''): string
    {
        return "report_{$this->id}_" . md5(json_encode($this->only([
            'filters', 'columns', 'sorting', 'grouping'
        ]))) . ($suffix ? "_{$suffix}" : '');
    }

    /**
     * Clear the cache for this report.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget($this->getCacheKey('data'));
    }

    /**
     * Export the report to a file.
     *
     * @param  string  $format
     * @param  int  $userId
     * @return string The file path of the exported report
     * @throws \Exception
     */
    public function export(string $format, int $userId = null): ReportFile
    {
        $formats = ['xlsx', 'csv', 'pdf'];
        
        if (!in_array($format, $formats)) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        try {
            $data = $this->generateData();
            $fileName = $this->generateFileName($format);
            $filePath = "reports/{$this->id}/{$fileName}";
            $fullPath = storage_path("app/public/{$filePath}");

            // Ensure the directory exists
            Storage::makeDirectory("public/reports/{$this->id}");

            switch ($format) {
                case 'xlsx':
                case 'csv':
                    ExcelFacade::store(
                        new ReportExport($data, $this->columns, $this->name),
                        $filePath,
                        'public',
                        constant("Maatwebsite\\Excel\\Excel::{$format}")
                    );
                    break;
                case 'pdf':
                    $pdf = PDF::loadView('exports.report-pdf', [
                        'data' => $data,
                        'columns' => $this->columns,
                        'report' => $this,
                    ]);
                    $pdf->save($fullPath);
                    break;
            }

            // Create a record of the exported file
            $file = new ReportFile([
                'report_id' => $this->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_type' => $format,
                'file_size' => Storage::size("public/{$filePath}"),
                'generated_by' => $userId,
            ]);

            $this->files()->save($file);
            $this->update(['last_generated_at' => now()]);

            return $file;

        } catch (\Exception $e) {
            Log::error('Failed to export report', [
                'report_id' => $this->id,
                'format' => $format,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to export report. Please try again later.');
        }
    }

    /**
     * Generate a file name for the export.
     *
     * @param  string  $extension
     * @return string
     */
    protected function generateFileName(string $extension): string
    {
        $name = Str::slug($this->name);
        $timestamp = now()->format('Y-m-d_His');
        return "{$name}_{$timestamp}.{$extension}";
    }

    /**
     * Schedule the report to be generated at the specified frequency.
     *
     * @param  string  $frequency
     * @param  bool  $isActive
     * @return bool
     */
    public function schedule(string $frequency, bool $isActive = true)
    {
        $this->is_scheduled = true;
        $this->is_active = $isActive;
        $this->schedule_frequency = $frequency;
        $this->next_run_at = $this->calculateNextRunDate();
        $this->save();

        return true;
    }

    /**
     * Pause the scheduled report.
     *
     * @return bool
     */
    public function pause()
    {
        if (!$this->is_scheduled) {
            return false;
        }
        
        $this->is_active = false;
        $this->save();
        
        return true;
    }

    /**
     * Resume the paused report.
     *
     * @return bool
     */
    public function resume()
    {
        if (!$this->is_scheduled) {
            return false;
        }
        
        $this->is_active = true;
        $this->next_run_at = $this->calculateNextRunDate();
        $this->save();
        
        return true;
    }

    /**
     * Unschedule the report.
     *
     * @return bool
     */
    public function unschedule()
    {
        if (!$this->is_scheduled) {
            return true;
        }
        
        $this->is_scheduled = false;
        $this->is_active = false;
        $this->next_run_at = null;
        $this->save();
        
        return true;
    }

    /**
     * Get the status of the report.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if (!$this->is_scheduled) {
            return 'not_scheduled';
        }
        
        if (!$this->is_active) {
            return 'paused';
        }
        
        return 'active';
    }

    /**
     * Get the next run time for the scheduled report.
     *
     * @return string|null
     */
    public function getNextRunAttribute()
    {
        if (!$this->is_scheduled || !$this->is_active) {
            return null;
        }
        
        return $this->calculateNextRunDate()->format('Y-m-d H:i:s');
    }

    /**
     * Calculate the next run date based on the frequency.
     *
     * @return Carbon
     */
    protected function calculateNextRunDate(): Carbon
    {
        $now = now();
        
        switch ($this->schedule_frequency) {
            case 'daily':
                return $now->addDay();
            case 'weekly':
                return $now->addWeek();
            case 'monthly':
                return $now->addMonth();
            case 'quarterly':
                return $now->addQuarter();
            case 'yearly':
                return $now->addYear();
            default:
                return $now->addDay();
        }
    }

    /**
     * Process the scheduled reports that are due.
     *
     * @return int Number of processed reports
     */
    public static function processScheduledReports(): int
    {
        $count = 0;
        $reports = self::where('schedule_frequency', '!=', null)
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($reports as $report) {
            try {
                DB::beginTransaction();
                
                // Export the report (this will also update last_generated_at)
                $report->export('xlsx', $report->created_by);
                
                // Schedule the next run
                $report->next_run_at = $report->calculateNextRunDate();
                $report->save();
                
                $count++;
                DB::commit();
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to process scheduled report', [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $count;
    }
}
