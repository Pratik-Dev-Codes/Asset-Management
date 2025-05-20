@extends('layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2><i class="fas fa-key me-2"></i> Reset Password</h2>
                <p class="mb-0">Enter your email to receive a reset link</p>
            </div>
            <div class="auth-body">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="needs-validation" novalidate>
                    @csrf
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-key fa-4x text-gray-400 mb-3"></i>
                        <p class="text-muted">
                            Enter the email address associated with your account and we'll send you a link to reset your password.
                        </p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ old('email') }}" 
                                placeholder="your@email.com" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                    </button>

                    <div class="text-center mt-4">
                        <p class="small text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            If you don't receive an email, please check your spam folder or contact support.
                        </p>
                    </div>
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
