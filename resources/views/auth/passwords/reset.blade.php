@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-key me-2"></i> Create New Password</h2>
                <p class="mb-0">Enter your new password below</p>
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

                <form method="POST" action="{{ route('password.update') }}" class="needs-validation" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-4x text-gray-400 mb-3"></i>
                        <p class="text-muted">
                            Please create a new password for your account. Make sure it's strong and secure.
                        </p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ $email ?? old('email') }}" 
                                placeholder="your@email.com" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="password" class="form-label">New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                id="password" name="password" placeholder="Create a strong password" required 
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

                    <div class="form-group mb-4">
                        <label for="password-confirm" class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control form-control-lg" 
                                id="password-confirm" name="password_confirmation" 
                                placeholder="Confirm your new password" required 
                                autocomplete="new-password"
                                onkeyup="checkPasswordMatch()">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password-confirm')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div id="password-match" class="mt-2"></div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-sync-alt me-2"></i> Reset Password
                    </button>
                </form>

                <div class="text-center mt-4">
                    <hr class="my-4">
                    <p class="mb-0">
                        Remember your password? 
                        <a href="{{ route('login') }}" class="text-primary">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
