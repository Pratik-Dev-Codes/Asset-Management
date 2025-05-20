@extends('layouts.app')

@section('title', $user->name . ' - Assigned Assets')

@push('styles')
<style>
    .asset-thumbnail {
        width: 50px;
        height: 50px;
        border-radius: 4px;
        object-fit: cover;
        margin-right: 1rem;
    }
    
    .asset-info {
        flex: 1;
    }
    
    .asset-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .asset-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }
    
    .table th {
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #6c757d;
        letter-spacing: 0.5px;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
    }
    
    .empty-state {
        padding: 3rem 0;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }
    
    .pagination {
        margin-bottom: 0;
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
                    Assigned Assets
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
            <p class="text-muted">Assets currently assigned to {{ $user->name }}</p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    @if($assets->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
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
                                    @foreach($assets as $asset)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @if($asset->image_path)
                                                        <img src="{{ asset('storage/' . $asset->image_path) }}" 
                                                             alt="{{ $asset->name }}" 
                                                             class="asset-thumbnail">
                                                    @else
                                                        <div class="asset-thumbnail bg-light d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-box text-muted"></i>
                                                        </div>
                                                    @endif
                                                    <div class="asset-info">
                                                        <div class="asset-title">
                                                            <a href="{{ route('assets.show', $asset->id) }}" class="text-decoration-none">
                                                                {{ $asset->name }}
                                                            </a>
                                                        </div>
                                                        <div class="asset-meta">
                                                            {{ $asset->asset_tag }}
                                                            @if($asset->serial_number)
                                                                · {{ $asset->serial_number }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @if($asset->category)
                                                    <span class="badge bg-light text-dark">
                                                        {{ $asset->category->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="status-badge bg-{{ $asset->status_badge }}">
                                                    {{ ucfirst($asset->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($asset->assigned_date)
                                                    {{ $asset->assigned_date->format('M d, Y') }}
                                                    <div class="text-muted small">
                                                        {{ $asset->assigned_date->diffForHumans() }}
                                                    </div>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="action-buttons">
                                                    <a href="{{ route('assets.show', $asset->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       data-bs-toggle="tooltip" 
                                                       title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @can('update', $asset)
                                                        <a href="{{ route('assets.edit', $asset->id) }}" 
                                                           class="btn btn-sm btn-outline-secondary" 
                                                           data-bs-toggle="tooltip" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                    @can('checkout', $asset)
                                                        <button type="button" 
                                                                class="btn btn-sm btn-outline-warning checkout-asset" 
                                                                data-asset-id="{{ $asset->id }}" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Checkout">
                                                            <i class="fas fa-exchange-alt"></i>
                                                        </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $assets->firstItem() }} to {{ $assets->lastItem() }} of {{ $assets->total() }} assets
                                </div>
                                {{ $assets->links() }}
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h5>No Assets Assigned</h5>
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
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="checkoutForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Checkout Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to checkout this asset to another user?</p>
                    
                    <div class="mb-3">
                        <label for="userId" class="form-label">Assign To</label>
                        <select class="form-select" id="userId" name="assigned_to" required>
                            <option value="">Select User</option>
                            @foreach($users as $u)
                                @if($u->id !== $user->id)
                                    <option value="{{ $u->id }}">
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="checkoutNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="checkoutNotes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sendEmail" name="send_email" value="1" checked>
                        <label class="form-check-label" for="sendEmail">
                            Send email notification to the user
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Checkout Asset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle checkout button click
        const checkoutButtons = document.querySelectorAll('.checkout-asset');
        const checkoutForm = document.getElementById('checkoutForm');
        
        checkoutButtons.forEach(button => {
            button.addEventListener('click', function() {
                const assetId = this.getAttribute('data-asset-id');
                checkoutForm.action = `/assets/${assetId}/checkout`;
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                modal.show();
            });
        });
    });
</script>
@endpush
