@extends('layouts.app')

@section('title', $user->name)

@push('styles')
<style>
    .profile-header {
        position: relative;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        overflow: hidden;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 1rem;
    }
    
    .profile-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .profile-username {
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 1rem;
    }
    
    .profile-stats {
        display: flex;
        justify-content: center;
        gap: 2rem;
        margin-top: 1.5rem;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        display: block;
        line-height: 1.2;
    }
    
    .stat-label {
        font-size: 0.875rem;
        opacity: 0.8;
    }
    
    .card {
        margin-bottom: 1.5rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-weight: 600;
        padding: 1rem 1.25rem;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .info-item {
        display: flex;
        margin-bottom: 0.75rem;
    }
    
    .info-label {
        font-weight: 600;
        width: 120px;
        color: #6c757d;
    }
    
    .info-value {
        flex: 1;
    }
    
    .badge-role {
        font-size: 0.75rem;
        padding: 0.35em 0.65em;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        display: inline-block;
    }
    
    .permission-group {
        margin-bottom: 1.5rem;
    }
    
    .permission-group-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        padding-bottom: 0.25rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .permission-item {
        display: inline-block;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.25rem;
        padding: 0.25rem 0.5rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
        color: #495057;
    }
    
    .activity-timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .activity-timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
        padding-left: 1.5rem;
    }
    
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    
    .timeline-dot {
        position: absolute;
        left: -1.5rem;
        top: 0.25rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background-color: #6c757d;
    }
    
    .timeline-item.primary .timeline-dot {
        background-color: #0d6efd;
    }
    
    .timeline-item.success .timeline-dot {
        background-color: #198754;
    }
    
    .timeline-item.warning .timeline-dot {
        background-color: #ffc107;
    }
    
    .timeline-item.danger .timeline-dot {
        background-color: #dc3545;
    }
    
    .timeline-time {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .timeline-content {
        font-size: 0.875rem;
    }
    
    .asset-thumbnail {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
        margin-right: 0.75rem;
    }
    
    .asset-info {
        flex: 1;
    }
    
    .asset-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .asset-meta {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    @media (max-width: 767.98px) {
        .profile-stats {
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .stat-item {
            flex: 0 0 calc(50% - 0.5rem);
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="profile-header text-center">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="profile-avatar">
                <h2 class="profile-name">{{ $user->name }}</h2>
                <div class="profile-username">@<span id="username">{{ $user->username }}</span></div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-value">{{ $user->assets_count ?? 0 }}</span>
                        <span class="stat-label">Assets</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">{{ $user->assigned_assets_count ?? 0 }}</span>
                        <span class="stat-label">Assigned</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">{{ $user->maintenance_requests_count ?? 0 }}</span>
                        <span class="stat-label">Maintenance</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">{{ $user->activity_logs_count ?? 0 }}</span>
                        <span class="stat-label">Activities</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-4">
            <!-- About Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>About</span>
                    @can('update', $user)
                        <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            @if($user->email_verified_at)
                                <span class="badge bg-success ms-2" data-bs-toggle="tooltip" title="Email Verified">
                                    <i class="fas fa-check"></i>
                                </span>
                            @else
                                <span class="badge bg-warning ms-2" data-bs-toggle="tooltip" title="Email Not Verified">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    @if($user->phone)
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                <a href="tel:{{ $user->phone }}">{{ $user->phone }}</a>
                            </div>
                        </div>
                    @endif
                    
                    @if($user->department)
                        <div class="info-item">
                            <div class="info-label">Department</div>
                            <div class="info-value">
                                {{ $user->department->name }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            @if($user->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Member Since</div>
                        <div class="info-value">
                            {{ $user->created_at->format('M d, Y') }}
                            <small class="text-muted">({{ $user->created_at->diffForHumans() }})</small>
                        </div>
                    </div>
                    
                    @if($user->last_login_at)
                        <div class="info-item">
                            <div class="info-label">Last Login</div>
                            <div class="info-value">
                                {{ $user->last_login_at->format('M d, Y h:i A') }}
                                <small class="text-muted">({{ $user->last_login_at->diffForHumans() }})</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Contact Information -->
            @if($user->address || $user->city || $user->state || $user->postal_code || $user->country)
                <div class="card">
                    <div class="card-header">
                        Contact Information
                    </div>
                    <div class="card-body">
                        @if($user->address)
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value">
                                    {{ $user->address }}
                                </div>
                            </div>
                        @endif
                        
                        <div class="info-item">
                            <div class="info-label">Location</div>
                            <div class="info-value">
                                @if($user->city && $user->state)
                                    {{ $user->city }}, {{ $user->state }}
                                    @if($user->postal_code)
                                        {{ $user->postal_code }}
                                    @endif
                                    <br>
                                @endif
                                {{ $user->country ? \App\Helpers\CountryHelper::getCountryName($user->country) : '' }}
                            </div>
                        </div>
                        
                        @if($user->bio)
                            <div class="info-item">
                                <div class="info-label">Bio</div>
                                <div class="info-value">
                                    {{ $user->bio }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Roles & Permissions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Roles & Permissions</span>
                    @can('manage roles')
                        <a href="{{ route('users.edit', [$user->id, 'tab' => 'permissions']) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    <h6 class="mb-2">Roles</h6>
                    <div class="mb-3">
                        @forelse($user->roles as $role)
                            <span class="badge bg-primary badge-role">
                                <i class="fas fa-user-shield me-1"></i> {{ $role->name }}
                            </span>
                        @empty
                            <span class="text-muted">No roles assigned</span>
                        @endforelse
                    </div>
                    
                    <h6 class="mb-2">Direct Permissions</h6>
                    @php
                        $groupedPermissions = $user->getDirectPermissions()->groupBy('group');
                    @endphp
                    
                    @if($groupedPermissions->isNotEmpty())
                        @foreach($groupedPermissions as $group => $permissions)
                            <div class="permission-group">
                                <div class="permission-group-title">{{ ucfirst($group) }}</div>
                                <div>
                                    @foreach($permissions as $permission)
                                        <span class="permission-item">
                                            <i class="fas fa-key me-1"></i> {{ $permission->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-muted">No direct permissions assigned</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-8">
            <!-- Assigned Assets -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Assigned Assets</span>
                    @can('viewAny', App\Models\Asset::class)
                        <a href="{{ route('assets.index', ['assigned_to' => $user->id]) }}" class="btn btn-sm btn-outline-primary">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    @endcan
                </div>
                <div class="card-body">
                    @if($assignedAssets->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Asset</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Assigned Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($assignedAssets as $asset)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($asset->image_path)
                                                        <img src="{{ asset('storage/' . $asset->image_path) }}" alt="{{ $asset->name }}" class="asset-thumbnail">
                                                    @else
                                                        <div class="asset-thumbnail bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <div class="asset-info">
                                                        <div class="asset-title">
                                                            <a href="{{ route('assets.show', $asset->id) }}">{{ $asset->name }}</a>
                                                        </div>
                                                        <div class="asset-meta">
                                                            {{ $asset->serial_number ?? 'No Serial' }} · {{ $asset->asset_tag }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $asset->category->name ?? 'N/A' }}</td>
                                            <td>
                                                <span class="badge bg-{{ $asset->status_badge }}">
                                                    {{ ucfirst($asset->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($asset->assigned_to === $user->id && $asset->assigned_date)
                                                    {{ $asset->assigned_date->format('M d, Y') }}
                                                    <div class="text-muted small">{{ $asset->assigned_date->diffForHumans() }}</div>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-outline-primary" 
                                                       data-bs-toggle="tooltip" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('update', $asset)
                                                        <a href="{{ route('assets.edit', $asset->id) }}" class="btn btn-outline-secondary" 
                                                           data-bs-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-box-open fa-3x text-muted"></i>
                            </div>
                            <h5>No assets assigned</h5>
                            <p class="text-muted">This user doesn't have any assets assigned yet.</p>
                            @can('create', App\Models\Asset::class)
                                <a href="{{ route('assets.create', ['assigned_to' => $user->id]) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Assign Asset
                                </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    Recent Activity
                </div>
                <div class="card-body">
                    @if($activities->isNotEmpty())
                        <div class="activity-timeline">
                            @foreach($activities as $activity)
                                <div class="timeline-item {{ $activity->type }}">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-time">
                                        {{ $activity->created_at->diffForHumans() }}
                                    </div>
                                    <div class="timeline-content">
                                        {!! $activity->description !!}
                                        @if($activity->properties->has('attributes'))
                                            <div class="small text-muted mt-1">
                                                @foreach($activity->properties['attributes'] as $key => $value)
                                                    @if(is_string($value) || is_numeric($value))
                                                        <div><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('activity-log.index', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-primary">
                                View All Activity <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-history fa-3x text-muted"></i>
                            </div>
                            <h5>No recent activity</h5>
                            <p class="text-muted">This user doesn't have any recorded activity yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
@can('delete', $user)
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                    <p class="text-danger"><strong>Warning:</strong> This will permanently delete all data associated with this user.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> This user has {{ $user->assets_count ?? 0 }} assets assigned. 
                        Please reassign or delete these assets before deleting the user.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" id="confirmDelete" 
                                {{ $user->assets_count > 0 ? 'disabled' : '' }}>
                            <i class="fas fa-trash me-1"></i> Delete User
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle delete button state based on assets
        const deleteButton = document.getElementById('confirmDelete');
        if (deleteButton && {{ $user->assets_count ?? 0 }} > 0) {
            deleteButton.setAttribute('title', 'Cannot delete user with assigned assets');
            deleteButton.setAttribute('data-bs-toggle', 'tooltip');
            new bootstrap.Tooltip(deleteButton);
        }
    });
</script>
@endpush
