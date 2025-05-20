<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Notifications\DatabaseNotification;
use PragmaRX\Countries\Package\Countries;
use App\Models\AssetAttachment;
use App\Models\MaintenanceRequest;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'dark_mode',
        'phone_work',
        'phone_mobile',
        'department_id',
        'position',
        'designation',
        'employee_id',
        'location_id',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'bio',
        'notes',
        'avatar_path',
        'is_active',
        'receive_email_notifications',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'receive_email_notifications' => 'boolean',
        'dark_mode' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar_url',
        'full_address',
        'status_badge',
        'status_text',
        'country_name',
        'is_admin'
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['roles', 'permissions', 'notifications'];

    /**
     * Get the user's full address.
     *
     * @return string|null
     */
    public function getFullAddressAttribute()
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        
        $cityState = [];
        if ($this->city) $cityState[] = $this->city;
        if ($this->state) $cityState[] = $this->state;
        if ($this->postal_code) $cityState[] = $this->postal_code;
        
        if (!empty($cityState)) {
            $parts[] = implode(', ', $cityState);
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return !empty($parts) ? implode("\n", $parts) : null;
    }

    /**
     * Get the URL to the user's avatar.
     *
     * @return string
     */
    /**
     * Get the user's avatar URL.
     *
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path) {
            return asset('storage/' . $this->avatar_path);
        }
        
        // Generate initials avatar as fallback
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));
        
        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Get the user's department.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user's location.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get all of the assets assigned to the user.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'assigned_to');
    }

    /**
     * Get the attachments for the user.
     */
    public function attachments()
    {
        return $this->hasMany(AssetAttachment::class);
    }

    /**
     * Check if the user is an admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    /**
     * Get the is_admin attribute.
     *
     * @return bool
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Get all of the maintenance requests for the user.
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'requested_by');
    }

    /**
     * Get all of the activity logs for the user.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'causer_id');
    }

    /**
     * Get the user's preferred locale.
     *
     * @return string
     */
    public function preferredLocale()
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user's status badge.
     *
     * @return string
     */
    public function getStatusBadgeAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get the user's status text.
     *
     * @return string
     */
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the user's country name.
     *
     * @return string|null
     */
    public function getCountryNameAttribute()
    {
        if (!$this->country) {
            return null;
        }
        
        // Use a simple lookup array for common country codes
        $countries = [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'IN' => 'India',
            // Add more countries as needed
        ];
        
        return $countries[$this->country] ?? $this->country;
    }
    
    /**
     * Toggle the user's dark mode preference
     *
     * @return bool
     */
    public function toggleDarkMode()
    {
        $this->dark_mode = !$this->dark_mode;
        return $this->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'receive_email_notifications' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }
}
