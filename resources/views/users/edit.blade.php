@extends('layouts.app')

@section('title', 'Edit User: ' . $user->name)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .avatar-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto 1rem;
        display: block;
        border: 3px solid #dee2e6;
    }
    .password-strength {
        height: 5px;
        margin-top: 0.5rem;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
    }
    .password-requirements {
        margin-top: 0.5rem;
        font-size: 0.875rem;
    }
    .requirement {
        display: flex;
        align-items: center;
        margin-bottom: 0.25rem;
        color: #6c757d;
    }
    .requirement.valid {
        color: #198754;
    }
    .requirement.valid i {
        margin-right: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit User: {{ $user->name }}</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="darkModeToggle">
                        <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
                    </div>
                </div>

                <div class="card-body">
                    <form id="userForm" action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <img id="avatarPreview" src="{{ $user->avatar_url }}" alt="Avatar Preview" class="avatar-preview">
                                    <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*">
                                    <button type="button" id="changeAvatar" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera me-1"></i> Change Photo
                                    </button>
                                    @if($user->avatar_path)
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_avatar" name="remove_avatar">
                                            <label class="form-check-label" for="remove_avatar">
                                                Remove current photo
                                            </label>
                                        </div>
                                    @endif
                                    @error('avatar')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Account Status</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">Active Account</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified" 
                                                   value="1" {{ old('email_verified', $user->email_verified_at) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="email_verified">Email Verified</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="change_password" 
                                                   name="change_password" value="1" {{ old('change_password') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="change_password">Change Password</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">User Statistics</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Member Since:</span>
                                            <span>{{ $user->created_at->format('M d, Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Last Login:</span>
                                            <span>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Last IP:</span>
                                            <span>{{ $user->last_login_ip ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Basic Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                                       id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                                @error('first_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                                       id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                                @error('last_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">@</span>
                                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                                       id="username" name="username" value="{{ old('username', $user->username) }}" required>
                                            </div>
                                            <small class="text-muted">Only letters, numbers, and underscores allowed</small>
                                            @error('username')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div id="passwordFields" class="d-none">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="password" class="form-label">New Password</label>
                                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                           id="password" name="password">
                                                    <div class="password-strength">
                                                        <div class="password-strength-bar" id="passwordStrength"></div>
                                                    </div>
                                                    <div class="password-requirements">
                                                        <div class="requirement" id="length">
                                                            <i class="fas fa-circle-xmark text-danger me-1"></i>
                                                            At least 8 characters
                                                        </div>
                                                        <div class="requirement" id="uppercase">
                                                            <i class="fas fa-circle-xmark text-danger me-1"></i>
                                                            At least 1 uppercase letter
                                                        </div>
                                                        <div class="requirement" id="lowercase">
                                                            <i class="fas fa-circle-xmark text-danger me-1"></i>
                                                            At least 1 lowercase letter
                                                        </div>
                                                        <div class="requirement" id="number">
                                                            <i class="fas fa-circle-xmark text-danger me-1"></i>
                                                            At least 1 number
                                                        </div>
                                                        <div class="requirement" id="special">
                                                            <i class="fas fa-circle-xmark text-danger me-1"></i>
                                                            At least 1 special character
                                                        </div>
                                                    </div>
                                                    @error('password')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                                    <input type="password" class="form-control" 
                                                           id="password_confirmation" name="password_confirmation">
                                                    <div class="form-text">
                                                        <span id="passwordMatch" class="text-danger d-none">
                                                            <i class="fas fa-times-circle"></i> Passwords do not match
                                                        </span>
                                                        <span id="passwordMatchSuccess" class="text-success d-none">
                                                            <i class="fas fa-check-circle"></i> Passwords match
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Role & Permissions</h6>
                                        @can('manage roles')
                                            <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-cog me-1"></i> Manage Roles
                                            </a>
                                        @endcan
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="roles" class="form-label">User Roles <span class="text-danger">*</span></label>
                                            <select class="form-select select2 @error('roles') is-invalid @enderror" 
                                                    id="roles" name="roles[]" multiple required>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" 
                                                        {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('roles')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="permissions-container">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label mb-0">Direct Permissions</label>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="selectAllPermissions">
                                                    <label class="form-check-label" for="selectAllPermissions">Select All</label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                @foreach($permissions->groupBy('group') as $group => $groupPermissions)
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0">{{ ucfirst($group) }}</h6>
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input group-select" type="checkbox" 
                                                                           data-group="{{ $group }}">
                                                                </div>
                                                            </div>
                                                            <div class="card-body p-2">
                                                                @foreach($groupPermissions as $permission)
                                                                    <div class="form-check">
                                                                        <input class="form-check-input permission-checkbox" type="checkbox" 
                                                                               id="permission_{{ $permission->id }}" 
                                                                               name="permissions[]" 
                                                                               value="{{ $permission->id }}"
                                                                               {{ in_array($permission->id, old('permissions', $user->permissions->pluck('id')->toArray())) ? 'checked' : '' }}>
                                                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                                                            {{ $permission->name }}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">Additional Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                                       id="phone" name="phone" value="{{ old('phone', $user->profile->phone ?? '') }}">
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="department_id" class="form-label">Department</label>
                                                <select class="form-select @error('department_id') is-invalid @enderror" 
                                                        id="department_id" name="department_id">
                                                    <option value="">Select Department</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                            {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('department_id')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                                      id="address" name="address" rows="2">{{ old('address', $user->profile->address ?? '') }}</textarea>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                                       id="city" name="city" value="{{ old('city', $user->profile->city ?? '') }}">
                                                @error('city')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="state" class="form-label">State/Province</label>
                                                <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                                       id="state" name="state" value="{{ old('state', $user->profile->state ?? '') }}">
                                                @error('state')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="postal_code" class="form-label">Postal Code</label>
                                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                                       id="postal_code" name="postal_code" value="{{ old('postal_code', $user->profile->postal_code ?? '') }}">
                                                @error('postal_code')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="country" class="form-label">Country</label>
                                            <select class="form-select @error('country') is-invalid @enderror" 
                                                    id="country" name="country">
                                                <option value="">Select Country</option>
                                                @foreach(config('countries') as $code => $name)
                                                    <option value="{{ $code }}" 
                                                        {{ old('country', $user->profile->country ?? '') == $code ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('country')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="bio" class="form-label">Bio</label>
                                            <textarea class="form-control @error('bio') is-invalid @enderror" 
                                                      id="bio" name="bio" rows="3">{{ old('bio', $user->profile->bio ?? '') }}</textarea>
                                            @error('bio')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Users
                            </a>
                            <div>
                                <a href="{{ route('users.show', $user->id) }}" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> This will permanently delete all data associated with this user.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete User
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Select roles',
            allowClear: true,
            width: '100%'
        });
        
        // Toggle password fields
        $('#change_password').change(function() {
            if ($(this).is(':checked')) {
                $('#passwordFields').removeClass('d-none').find('input').attr('required', true);
            } else {
                $('#passwordFields').addClass('d-none').find('input').attr('required', false);
            }
        });
        
        // Avatar preview
        const avatarInput = document.getElementById('avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const changeAvatarBtn = document.getElementById('changeAvatar');
        
        changeAvatarBtn.addEventListener('click', () => {
            avatarInput.click();
        });
        
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        const passwordMatchSuccess = document.getElementById('passwordMatchSuccess');
        const passwordConfirmation = document.getElementById('password_confirmation');
        
        // Password requirements
        const requirements = {
            length: document.getElementById('length'),
            uppercase: document.getElementById('uppercase'),
            lowercase: document.getElementById('lowercase'),
            number: document.getElementById('number'),
            special: document.getElementById('special')
        };
        
        function checkPasswordStrength(password) {
            if (!password) return;
            
            // Reset all requirements
            Object.values(requirements).forEach(req => {
                const icon = req.querySelector('i');
                icon.className = 'fas fa-circle-xmark text-danger me-1';
            });
            
            // Check length
            if (password.length >= 8) {
                requirements.length.classList.add('valid');
                requirements.length.querySelector('i').className = 'fas fa-check-circle text-success me-1';
            } else {
                requirements.length.classList.remove('valid');
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                requirements.uppercase.classList.add('valid');
                requirements.uppercase.querySelector('i').className = 'fas fa-check-circle text-success me-1';
            } else {
                requirements.uppercase.classList.remove('valid');
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                requirements.lowercase.classList.add('valid');
                requirements.lowercase.querySelector('i').className = 'fas fa-check-circle text-success me-1';
            } else {
                requirements.lowercase.classList.remove('valid');
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                requirements.number.classList.add('valid');
                requirements.number.querySelector('i').className = 'fas fa-check-circle text-success me-1';
            } else {
                requirements.number.classList.remove('valid');
            }
            
            // Check special character
            if (/[^A-Za-z0-9]/.test(password)) {
                requirements.special.classList.add('valid');
                requirements.special.querySelector('i').className = 'fas fa-check-circle text-success me-1';
            } else {
                requirements.special.classList.remove('valid');
            }
            
            // Calculate password strength
            const result = zxcvbn(password);
            const strength = result.score; // 0-4
            
            // Update strength meter
            const width = (strength + 1) * 25; // Convert 0-4 to 25%-100%
            passwordStrength.style.width = width + '%';
            
            // Update color based on strength
            if (strength <= 1) {
                passwordStrength.className = 'password-strength-bar bg-danger';
            } else if (strength <= 2) {
                passwordStrength.className = 'password-strength-bar bg-warning';
            } else if (strength <= 3) {
                passwordStrength.className = 'password-strength-bar bg-info';
            } else {
                passwordStrength.className = 'password-strength-bar bg-success';
            }
        }
        
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = passwordConfirmation.value;
            
            if (password && confirmPassword) {
                if (password === confirmPassword) {
                    passwordMatch.classList.add('d-none');
                    passwordMatchSuccess.classList.remove('d-none');
                    return true;
                } else {
                    passwordMatch.classList.remove('d-none');
                    passwordMatchSuccess.classList.add('d-none');
                    return false;
                }
            }
            return false;
        }
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                checkPasswordMatch();
            });
            
            passwordConfirmation.addEventListener('input', checkPasswordMatch);
        }
        
        // Form validation
        const form = document.getElementById('userForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Only validate passwords if change password is checked
                if ($('#change_password').is(':checked')) {
                    // Check if passwords match
                    if (!checkPasswordMatch()) {
                        e.preventDefault();
                        passwordConfirmation.focus();
                        return false;
                    }
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
            });
        }
        
        // Permission management
        // Toggle all permissions in a group
        $('.group-select').change(function() {
            const group = $(this).data('group');
            const isChecked = $(this).prop('checked');
            
            $(`.permission-checkbox[data-group="${group}"]`).prop('checked', isChecked);
            updateSelectAllCheckbox();
        });
        
        // Toggle select all permissions
        $('#selectAllPermissions').change(function() {
            const isChecked = $(this).prop('checked');
            $('.permission-checkbox').prop('checked', isChecked);
            $('.group-select').prop('checked', isChecked);
        });
        
        // Update group select when individual permissions change
        $('.permission-checkbox').change(function() {
            const group = $(this).data('group');
            const allChecked = $(`.permission-checkbox[data-group="${group}"]`).length === 
                             $(`.permission-checkbox[data-group="${group}"]:checked`).length;
            
            $(`.group-select[data-group="${group}"]`).prop('checked', allChecked);
            updateSelectAllCheckbox();
        });
        
        function updateSelectAllCheckbox() {
            const allChecked = $('.permission-checkbox').length === $('.permission-checkbox:checked').length;
            $('#selectAllPermissions').prop('checked', allChecked);
        }
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Phone number formatting
        $('#phone').on('input', function() {
            const number = $(this).val().replace(/[^0-9]/g, '');
            if (number.length > 3 && number.length <= 6) {
                $(this).val(number.replace(/(\d{3})(\d{1,3})/, '$1-$2'));
            } else if (number.length > 6) {
                $(this).val(number.replace(/(\d{3})(\d{3})(\d{1,4})/, '$1-$2-$3'));
            }
        });
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkMode = new Darkmode({
            time: '0.5s',
            label: '🌓',
            saveInCookies: true,
            autoMatchOsTheme: true
        });

        // Set initial state
        if (darkMode.isActivated()) {
            darkModeToggle.checked = true;
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('change', () => {
            darkMode.toggle();
        });
    });
</script>
@endpush
