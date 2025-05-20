@extends('layouts.app')

@section('title', 'Add New User')

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
                <div class="card-header">
                    <h5 class="mb-0">Add New User</h5>
                </div>

                <div class="card-body">
                    <form id="userForm" action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <div class="mb-3">
                                    <img id="avatarPreview" src="{{ asset('images/default-avatar.png') }}" alt="Avatar Preview" class="avatar-preview">
                                    <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*">
                                    <button type="button" id="changeAvatar" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera me-1"></i> Change Photo
                                    </button>
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
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                            <label class="form-check-label" for="is_active">Active Account</label>
                                        </div>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified" value="1" checked>
                                            <label class="form-check-label" for="email_verified">Email Verified</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="send_welcome_email" name="send_welcome_email" value="1" checked>
                                            <label class="form-check-label" for="send_welcome_email">Send Welcome Email</label>
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
                                                       id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                                @error('first_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                                @error('last_name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                                   id="email" name="email" value="{{ old('email') }}" required>
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">@</span>
                                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                                       id="username" name="username" value="{{ old('username') }}" required>
                                            </div>
                                            <small class="text-muted">Only letters, numbers, and underscores allowed</small>
                                            @error('username')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                                       id="password" name="password" required>
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
                                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" 
                                                       id="password_confirmation" name="password_confirmation" required>
                                                <div class="form-text">
                                                    <span id="passwordMatch" class="text-danger">
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
                                
                                <div class="card mb-4">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Role & Permissions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="roles" class="form-label">User Roles <span class="text-danger">*</span></label>
                                            <select class="form-select select2 @error('roles') is-invalid @enderror" 
                                                    id="roles" name="roles[]" multiple required>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('roles')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="permissions-container">
                                            <label class="form-label">Direct Permissions</label>
                                            <div class="row">
                                                @foreach($permissions->groupBy('group') as $group => $groupPermissions)
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100">
                                                            <div class="card-header py-2 bg-light">
                                                                <h6 class="mb-0">{{ ucfirst($group) }}</h6>
                                                            </div>
                                                            <div class="card-body p-2">
                                                                @foreach($groupPermissions as $permission)
                                                                    <div class="form-check">
                                                                        <input class="form-check-input" type="checkbox" 
                                                                               id="permission_{{ $permission->id }}" 
                                                                               name="permissions[]" 
                                                                               value="{{ $permission->id }}"
                                                                               {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
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
                                                       id="phone" name="phone" value="{{ old('phone') }}">
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
                                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
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
                                                      id="address" name="address" rows="2">{{ old('address') }}</textarea>
                                            @error('address')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="city" class="form-label">City</label>
                                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                                       id="city" name="city" value="{{ old('city') }}">
                                                @error('city')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="state" class="form-label">State/Province</label>
                                                <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                                       id="state" name="state" value="{{ old('state') }}">
                                                @error('state')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label for="postal_code" class="form-label">Postal Code</label>
                                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                                       id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
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
                                                    <option value="{{ $code }}" {{ old('country') == $code ? 'selected' : '' }}>
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
                                                      id="bio" name="bio" rows="3">{{ old('bio') }}</textarea>
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
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i> Save User
                            </button>
                        </div>
                    </form>
                </div>
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
        
        passwordInput.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        passwordConfirmation.addEventListener('input', checkPasswordMatch);
        
        // Form validation
        const form = document.getElementById('userForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(e) {
            // Check if passwords match
            if (!checkPasswordMatch()) {
                e.preventDefault();
                passwordConfirmation.focus();
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';
        });
        
        // Toggle permissions based on roles
        const rolesSelect = document.getElementById('roles');
        const permissionsContainer = document.querySelector('.permissions-container');
        
        // This would be populated from an API call in a real application
        // For now, we'll just show/hide based on role selection
        rolesSelect.addEventListener('change', function() {
            // In a real app, you would fetch permissions for selected roles via AJAX
            // and update the permissions checkboxes accordingly
            console.log('Selected roles:', $(this).val());
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize date picker for date fields
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
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
    });
</script>
@endpush
