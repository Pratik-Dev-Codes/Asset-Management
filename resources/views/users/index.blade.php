@extends('layouts.app')

@section('title', 'User Management')

@push('styles')
<link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .role-badge {
        font-size: 0.8rem;
    }
    .bulk-actions {
        background-color: #f8f9fa;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0.25rem;
        border: 1px solid #dee2e6;
        display: none;
    }
    .bulk-actions.show {
        display: block;
    }
    .select2-container {
        width: 200px !important;
        display: inline-block !important;
        margin-right: 10px;
    }
    .bulk-action-buttons .btn {
        margin-right: 5px;
    }
    .select-all-checkbox {
        margin-right: 5px;
    }
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25em 0.6em;
    }
    .loading-spinner {
        display: none;
        margin-left: 5px;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">User Management</h5>
                    @can('create', App\Models\User::class)
                        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add User
                        </a>
                    @endcan
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    <!-- Bulk Actions Panel -->
                    <div id="bulkActions" class="bulk-actions">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="bulk-selection-info">
                                <span id="selectedCount">0</span> users selected
                            </div>
                            <div class="bulk-action-buttons">
                                <select id="bulkAction" class="form-select form-select-sm d-inline-block w-auto me-2">
                                    <option value="">Choose action...</option>
                                    <option value="activate">Activate</option>
                                    <option value="deactivate">Deactivate</option>
                                    <option value="change_role">Change Role</option>
                                    <option value="delete">Delete</option>
                                </select>
                                
                                <div id="roleSelectContainer" class="d-inline-block" style="display: none;">
                                    <select id="roleSelect" class="form-select form-select-sm" style="width: 150px;">
                                        @foreach(\Spatie\Permission\Models\Role::all() as $role)
                                            <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <button id="applyBulkAction" class="btn btn-primary btn-sm">
                                    Apply
                                    <span class="spinner-border spinner-border-sm loading-spinner" role="status" aria-hidden="true"></span>
                                </button>
                                <button id="cancelBulkAction" class="btn btn-outline-secondary btn-sm ms-2">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <form id="bulkActionForm" action="{{ route('users.bulk-action') }}" method="POST">
                        @csrf
                        <table id="usersTable" class="table table-striped table-hover" style="width:100%">
                            <thead>
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input select-all-checkbox" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr data-id="{{ $user->id }}">
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input user-checkbox" type="checkbox" name="selected_users[]" value="{{ $user->id }}">
                                        </div>
                                    </td>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($user->profile_photo_path)
                                                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" 
                                                     alt="{{ $user->name }}" 
                                                     class="user-avatar me-2"
                                                     loading="lazy">
                                            @else
                                                <div class="user-avatar bg-secondary text-white d-flex align-items-center justify-content-center me-2">
                                                    {{ substr($user->name, 0, 1) }}
                                                </div>
                                            @endif
                                            <div>
                                                <div>{{ $user->name }}</div>
                                                <small class="text-muted">@ {{ $user->username }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-primary role-badge">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge status-badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-info"
                                               data-bs-toggle="tooltip" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @can('edit', $user)
                                                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary"
                                                   data-bs-toggle="tooltip" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $user)
                                                <button type="button" class="btn btn-danger delete-user"
                                                        data-id="{{ $user->id }}" data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete User Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm User Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <p class="text-danger">Note: This will permanently remove all user data and cannot be recovered.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteUserForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#usersTable').DataTable({
            order: [[1, 'asc']],
            pageLength: 25,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search users...",
                lengthMenu: "Show _MENU_ users per page",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "No users found",
                infoFiltered: "(filtered from _MAX_ total users)",
                zeroRecords: "No matching users found"
            },
            columnDefs: [
                { orderable: false, targets: [0, 6] }, // Disable sorting on checkbox and actions column
                { searchable: false, targets: [0, 5, 6] } // Disable search on checkbox, status, and actions column
            ]
        });

        // Initialize Select2 for role dropdown
        $('#roleSelect').select2({
            placeholder: 'Select a role',
            width: '100%',
            dropdownParent: $('#bulkActions')
        });

        // Toggle bulk actions panel when checkboxes are checked
        $(document).on('change', '.user-checkbox, #selectAll', function() {
            updateBulkActions();
        });

        // Select all checkboxes
        $('#selectAll').on('click', function() {
            $('.user-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkActions();
        });

        // Update bulk actions UI
        function updateBulkActions() {
            const selectedCount = $('.user-checkbox:checked').length;
            const $bulkActions = $('#bulkActions');
            
            if (selectedCount > 0) {
                $bulkActions.addClass('show');
                $('#selectedCount').text(selectedCount);
            } else {
                $bulkActions.removeClass('show');
                $('#bulkAction').val('').trigger('change');
            }
        }

        // Toggle role select based on bulk action
        $('#bulkAction').on('change', function() {
            if ($(this).val() === 'change_role') {
                $('#roleSelectContainer').show();
            } else {
                $('#roleSelectContainer').hide();
            }
        });

        // Cancel bulk action
        $('#cancelBulkAction').on('click', function(e) {
            e.preventDefault();
            $('.user-checkbox, #selectAll').prop('checked', false);
            $('#bulkActions').removeClass('show');
            $('#bulkAction').val('').trigger('change');
        });

        // Apply bulk action
        $('#applyBulkAction').on('click', function(e) {
            e.preventDefault();
            
            const action = $('#bulkAction').val();
            const selectedUsers = [];
            
            $('.user-checkbox:checked').each(function() {
                selectedUsers.push($(this).val());
            });
            
            if (selectedUsers.length === 0) {
                alert('Please select at least one user.');
                return;
            }
            
            if (!action) {
                alert('Please select an action to perform.');
                return;
            }
            
            if (action === 'change_role' && !$('#roleSelect').val()) {
                alert('Please select a role.');
                return;
            }
            
            // Show loading state
            const $submitBtn = $(this);
            const $spinner = $submitBtn.find('.loading-spinner');
            const originalText = $submitBtn.html();
            
            $submitBtn.prop('disabled', true);
            $spinner.show();
            
            // Prepare form data
            const formData = new FormData($('#bulkActionForm')[0]);
            formData.append('action', action);
            
            if (action === 'change_role') {
                formData.append('role', $('#roleSelect').val());
            }
            
            // Submit form via AJAX
            $.ajax({
                url: '{{ route("users.bulk-action") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Reload the page to see changes
                    location.reload();
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $spinner.hide();
                    $submitBtn.html(originalText);
                }
            });
        });

        // Delete user confirmation
        $(document).on('click', '.delete-user', function() {
            var userId = $(this).data('id');
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                $.ajax({
                    url: '/users/' + userId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting the user.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    }
                });
            }
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Keyboard shortcut: / to focus search
        $(document).keyup(function(e) {
            if (e.key === '/' && !$(e.target).is('input, textarea, [contenteditable]')) {
                e.preventDefault();
                $('.dataTables_filter input').focus();
            }
            
            // Esc key to cancel bulk selection
            if (e.key === 'Escape' && $('#bulkActions').hasClass('show')) {
                $('.user-checkbox, #selectAll').prop('checked', false);
                $('#bulkActions').removeClass('show');
                $('#bulkAction').val('').trigger('change');
            }
        });

        // Add a class to the search input for better styling
        $('.dataTables_filter input')
            .addClass('form-control form-control-sm')
            .attr('placeholder', 'Search users...')
            .css('width', '250px');
            
        // Lazy load images
        if ('loading' in HTMLImageElement.prototype) {
            // Native lazy loading is supported
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                img.src = img.dataset.src;
            });
        } else {
            // Fallback for browsers without native lazy loading
            let lazyImages = [].slice.call(document.querySelectorAll('img[loading="lazy"]'));
            
            if ('IntersectionObserver' in window) {
                const lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImageObserver.unobserve(lazyImage);
                        }
                    });
                });
                
                lazyImages.forEach(function(lazyImage) {
                    lazyImageObserver.observe(lazyImage);
                });
            } else {
                // Fallback for very old browsers
                let active = false;
                
                const lazyLoad = function() {
                    if (active === false) {
                        active = true;
                        
                        setTimeout(function() {
                            lazyImages.forEach(function(lazyImage) {
                                if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && 
                                     lazyImage.getBoundingClientRect().bottom >= 0) && 
                                    getComputedStyle(lazyImage).display !== 'none') {
                                    lazyImage.src = lazyImage.dataset.src;
                                    lazyImages = lazyImages.filter(function(image) {
                                        return image !== lazyImage;
                                    });
                                    
                                    if (lazyImages.length === 0) {
                                        document.removeEventListener('scroll', lazyLoad);
                                        window.removeEventListener('resize', lazyLoad);
                                        window.removeEventListener('orientationchange', lazyLoad);
                                    }
                                }
                            });
                            
                            active = false;
                        }, 200);
                    }
                };
                
                document.addEventListener('scroll', lazyLoad);
                window.addEventListener('resize', lazyLoad);
                window.addEventListener('orientationchange', lazyLoad);
            }
        }

        // Add a class to the search input for better styling
        $('.dataTables_filter input')
            .addClass('form-control form-control-sm')
            .attr('placeholder', 'Search users...')
            .css('display', 'inline-block');
            
        // Handle keyboard shortcuts
        $(document).keydown(function(e) {
            // Focus on search input when pressing /
            if (e.key === '/' && !$(e.target).is('input, textarea, select, [contenteditable]')) {
                e.preventDefault();
                $('.dataTables_filter input').focus();
            }
            // Close modals with Escape key
            if (e.key === 'Escape') {
                const modal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
                if (modal) {
                    modal.hide();
                }
                // Also cancel bulk selection if active
                if ($('#bulkActions').hasClass('show')) {
                    $('.user-checkbox, #selectAll').prop('checked', false);
                    $('#bulkActions').removeClass('show');
                    $('#bulkAction').val('').trigger('change');
                }
            }
        });
    }); // End of document.ready
</script>
@endpush
