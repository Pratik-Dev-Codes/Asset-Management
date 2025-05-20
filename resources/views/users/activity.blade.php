@extends('layouts.app')

@section('title', $user->name . ' - Activity Log')

@push('styles')
<style>
    .activity-item {
        position: relative;
        padding-left: 2.5rem;
        padding-bottom: 1.5rem;
        border-left: 2px solid #e9ecef;
    }
    
    .activity-item:last-child {
        border-left-color: transparent;
    }
    
    .activity-dot {
        position: absolute;
        left: -0.5rem;
        top: 0.25rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background-color: #6c757d;
    }
    
    .activity-item.primary .activity-dot {
        background-color: #0d6efd;
    }
    
    .activity-item.success .activity-dot {
        background-color: #198754;
    }
    
    .activity-item.warning .activity-dot {
        background-color: #ffc107;
    }
    
    .activity-item.danger .activity-dot {
        background-color: #dc3545;
    }
    
    .activity-time {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.25rem;
    }
    
    .activity-content {
        font-size: 0.9rem;
    }
    
    .activity-details {
        margin-top: 0.5rem;
        padding: 0.75rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        font-size: 0.85rem;
    }
    
    .badge-login {
        background-color: #198754;
        color: white;
    }
    
    .badge-asset {
        background-color: #0d6efd;
        color: white;
    }
    
    .badge-profile {
        background-color: #6f42c1;
        color: white;
    }
    
    .badge-system {
        background-color: #6c757d;
        color: white;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <a href="{{ route('users.profile', $user->id) }}" class="text-decoration-none text-dark">
                        <i class="fas fa-arrow-left me-2"></i>
                    </a>
                    Activity Log
                </h2>
                <div>
                    <a href="{{ route('users.export.pdf', $user->id) }}" class="btn btn-outline-danger me-2">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </a>
                    <a href="{{ route('users.export.csv', $user->id) }}" class="btn btn-outline-success">
                        <i class="fas fa-file-csv me-1"></i> CSV
                    </a>
                </div>
            </div>
            <p class="text-muted">Activity history for {{ $user->name }}</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    @if($activities->count() > 0)
                        <div class="activity-list">
                            @foreach($activities as $activity)
                                <div class="activity-item {{ $activity->type }}">
                                    <div class="activity-dot"></div>
                                    <div class="activity-time">
                                        {{ $activity->created_at->format('M d, Y h:i A') }}
                                        <span class="badge bg-{{ $activity->type }} ms-2">
                                            {{ ucfirst($activity->type) }}
                                        </span>
                                    </div>
                                    <div class="activity-content">
                                        {!! $activity->description !!}
                                        
                                        @if($activity->properties && count($activity->properties) > 0)
                                            <button class="btn btn-sm btn-outline-secondary mt-2" type="button" 
                                                    data-bs-toggle="collapse" data-bs-target="#activityDetails{{ $activity->id }}" 
                                                    aria-expanded="false" aria-controls="activityDetails{{ $activity->id }}">
                                                <i class="fas fa-info-circle me-1"></i> Show Details
                                            </button>
                                            
                                            <div class="collapse mt-2" id="activityDetails{{ $activity->id }}">
                                                <div class="activity-details">
                                                    <pre class="mb-0">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-4">
                            {{ $activities->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-history fa-3x text-muted"></i>
                            </div>
                            <h5>No activity found</h5>
                            <p class="text-muted">There is no activity to display for this user.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add color coding based on activity type
        document.querySelectorAll('.activity-item').forEach(function(item) {
            const type = item.classList.contains('primary') ? 'primary' : 
                         item.classList.contains('success') ? 'success' :
                         item.classList.contains('warning') ? 'warning' :
                         item.classList.contains('danger') ? 'danger' : 'secondary';
            
            const dot = item.querySelector('.activity-dot');
            if (dot) {
                dot.style.backgroundColor = getComputedStyle(document.documentElement)
                    .getPropertyValue('--bs-' + type) || '#6c757d';
            }
        });
    });
</script>
@endpush
