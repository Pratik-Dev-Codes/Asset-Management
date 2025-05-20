<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 
 *
 * @property int $id
 * @property int $report_id
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size File size in bytes
 * @property array|null $generation_parameters
 * @property Carbon|null $expires_at
 * @property int $generated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\User $generatedBy
 * @property-read string|null $download_url
 * @property-read string $formatted_file_size
 * @property-read bool $is_expired
 * @property-read \App\Models\Report $report
 * @method static \Database\Factories\ReportFileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile notExpired()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile ofType($type)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereFileType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereGeneratedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereGenerationParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|ReportFile withoutTrashed()
 * @mixin \Eloquent
 */
class ReportFile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'generation_parameters',
        'expires_at',
        'generated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'generation_parameters' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'download_url',
        'formatted_file_size',
        'is_expired',
    ];

    /**
     * Get the report that owns the report file.
     */
    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    /**
     * Get the user who generated the report file.
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the download URL for the report file.
     *
     * @return string|null
     */
    public function getDownloadUrlAttribute()
    {
        if (! $this->file_path) {
            return null;
        }

        return route('reports.download', $this);
    }

    /**
     * Get the formatted file size.
     *
     * @return string
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Check if the report file has expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute()
    {
        if (! $this->expires_at) {
            return false;
        }
        
        return $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include non-expired files.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope a query to only include files of a specific type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('file_type', $type);
    }

    /**
     * Set the expiration time for the report file.
     *
     * @param  \DateTimeInterface|int  $days
     * @return $this
     */
    public function expiresAfterDays($days = 7)
    {
        $this->expires_at = now()->addDays($days);
        return $this;
    }
}
