@extends('layouts.guest')

@section('title', 'Create an Account')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-user-plus me-2"></i> Create Account</h2>
                <p class="mb-0">Fill in your details to get started</p>
            </div>
            <div class="auth-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <form method="POST" action="{{ route('register') }}" class="needs-validation" novalidate>
                    @csrf
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" 
                                        id="name" name="name" value="{{ old('name') }}" 
                                        placeholder="John Doe" required autocomplete="name" autofocus>
                                </div>
                                @error('name')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                        id="email" name="email" value="{{ old('email') }}" 
                                        placeholder="your@email.com" required autocomplete="email">
                                </div>
                                @error('email')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3 mt-0">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control form-control-lg @error('phone') is-invalid @enderror" 
                                        id="phone" name="phone" value="{{ old('phone') }}" 
                                        placeholder="+1 (555) 123-4567" required autocomplete="tel">
                                </div>
                                @error('phone')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-building"></i>
                                    </span>
                                    <select class="form-select form-select-lg @error('department') is-invalid @enderror" 
                                        id="department" name="department" required>
                                        <option value="" disabled selected>Select Department</option>
                                        <option value="IT" {{ old('department') == 'IT' ? 'selected' : '' }}>IT</option>
                                        <option value="HR" {{ old('department') == 'HR' ? 'selected' : '' }}>HR</option>
                                        <option value="Finance" {{ old('department') == 'Finance' ? 'selected' : '' }}>Finance</option>
                                        <option value="Operations" {{ old('department') == 'Operations' ? 'selected' : '' }}>Operations</option>
                                        <option value="Marketing" {{ old('department') == 'Marketing' ? 'selected' : '' }}>Marketing</option>
                                        <option value="Sales" {{ old('department') == 'Sales' ? 'selected' : '' }}>Sales</option>
                                    </select>
                                </div>
                                @error('department')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-0">
                        <label for="position" class="form-label">Position <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-briefcase"></i>
                            </span>
                            <input type="text" class="form-control form-control-lg @error('position') is-invalid @enderror" 
                                id="position" name="position" value="{{ old('position') }}" 
                                placeholder="e.g., Software Engineer" required>
                        </div>
                        @error('position')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <div class="form-group mt-4">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                id="password" name="password" placeholder="Create a strong password" required 
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                title="Must contain at least one number, one uppercase letter, one lowercase letter, and be at least 8 characters long"
                                autocomplete="new-password"
                                onkeyup="checkPasswordStrength(this.value)">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <div id="password-strength" class="mb-2"></div>
                                <ul class="list-unstyled">
                                    <li id="length" class="text-danger">
                                        <i class="fas fa-circle-xmark me-1"></i> At least 8 characters
                                    </li>
                                    <li id="uppercase" class="text-danger">
                                        <i class="fas fa-circle-xmark me-1"></i> At least one uppercase letter
                                    </li>
                                    <li id="lowercase" class="text-danger">
                                        <i class="fas fa-circle-xmark me-1"></i> At least one lowercase letter
                                    </li>
                                    <li id="number" class="text-danger">
                                        <i class="fas fa-circle-xmark me-1"></i> At least one number
                                    </li>
                                </ul>
                            </small>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    
                    <div class="form-group mt-4">
                        <label for="password-confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg" 
                                id="password-confirm" name="password_confirmation" 
                                placeholder="Confirm your password" required 
                                autocomplete="new-password"
                                onkeyup="checkPasswordMatch()">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password-confirm')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-match" class="mt-2"></div>
                    </div>
                    
                    <div class="form-check mt-4 mb-4">
                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                        <label class="form-check-label small" for="terms">
                            I agree to the <a href="#" class="text-primary">Terms of Service</a> and 
                            <a href="#" class="text-primary">Privacy Policy</a>
                        </label>
                        <div class="invalid-feedback">
                            You must agree to the terms and conditions
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-user-plus me-2"></i> Create Account
                    </button>
                    
                    <div class="text-center mt-4">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="{{ route('login') }}" class="text-primary">Sign in</a>
                        </p>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
