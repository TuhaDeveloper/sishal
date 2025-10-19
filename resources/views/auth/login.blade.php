@extends('ecommerce.master')

@section('main-section')
<style>
    .login-page {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 80vh;
        padding: 60px 0;
    }
    
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid #e9ecef;
        max-width: 450px;
        width: 100%;
        padding: 50px 40px;
        margin: 0 auto;
    }
    
    .login-title {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .login-title h2 {
        color: #333;
        font-weight: 700;
        margin-bottom: 15px;
        font-size: 2rem;
    }
    
    .login-title p {
        color: #666;
        margin: 0;
        font-size: 1.1rem;
    }
    
    .form-group {
        margin-bottom: 25px;
        position: relative;
    }
    
    .form-control {
        height: 55px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 15px 20px;
        font-size: 16px;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
        width: 100%;
    }
    
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        background-color: white;
        outline: none;
    }
    
    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }
    
    .btn-login {
        height: 55px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        width: 100%;
        position: relative;
        overflow: hidden;
    }
    
    .btn-login:hover {
        background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
    }
    
    .btn-login:active {
        transform: translateY(0);
    }
    
    .btn-login:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }
    
    .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        margin-top: 0;
    }
    
    .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
    }
    
    .form-check-label {
        color: #666;
        font-size: 15px;
        margin-left: 8px;
    }
    
    .forgot-password {
        color: #007bff;
        text-decoration: none;
        font-size: 15px;
        font-weight: 500;
    }
    
    .forgot-password:hover {
        color: #0056b3;
        text-decoration: underline;
    }
    
    .signup-link {
        text-align: center;
        margin-top: 30px;
        color: #666;
        font-size: 15px;
    }
    
    .signup-link a {
        color: #007bff;
        text-decoration: none;
        font-weight: 600;
    }
    
    .signup-link a:hover {
        color: #0056b3;
        text-decoration: underline;
    }
    
    .error-message {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 12px 15px;
        border-radius: 8px;
        margin-top: 10px;
        font-size: 14px;
        display: flex;
        align-items: center;
    }
    
    .success-message {
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 14px;
        display: flex;
        align-items: center;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 18px;
        height: 18px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .login-options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    @media (max-width: 768px) {
        .login-page {
            padding: 20px 0;
            min-height: 70vh;
        }
        
        .login-card {
            padding: 30px 25px;
            margin: 10px;
            max-width: 100%;
            border-radius: 15px;
        }
        
        .login-title h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .login-title p {
            font-size: 1rem;
        }
        
        .form-control {
            height: 50px;
            font-size: 16px;
            padding: 12px 18px;
        }
        
        .form-label {
            font-size: 13px;
            margin-bottom: 6px;
        }
        
        .btn-login {
            height: 50px;
            font-size: 15px;
        }
        
        .login-options {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        
        .form-check-label {
            font-size: 14px;
        }
        
        .forgot-password {
            font-size: 14px;
        }
        
        .signup-link {
            font-size: 14px;
            margin-top: 25px;
        }
    }
    
    @media (max-width: 480px) {
        .login-page {
            padding: 15px 0;
        }
        
        .login-card {
            padding: 25px 20px;
            margin: 5px;
            border-radius: 12px;
        }
        
        .login-title h2 {
            font-size: 1.4rem;
        }
        
        .login-title p {
            font-size: 0.95rem;
        }
        
        .form-control {
            height: 48px;
            font-size: 15px;
            padding: 10px 15px;
        }
        
        .form-label {
            font-size: 12px;
            margin-bottom: 5px;
        }
        
        .btn-login {
            height: 48px;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .login-options {
            margin-bottom: 20px;
        }
    }
</style>

<!-- Login Section -->
<section class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="login-card">
                    <!-- Title -->
                    <div class="login-title">
                        <h2>Welcome Back</h2>
                        <p>Sign in to your account to continue</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="success-message">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" id="loginForm">
                        @csrf

                        <!-- Email Address -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                                   class="form-control" placeholder="Enter your email address">
                            @error('email')
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="current-password" 
                                   class="form-control" placeholder="Enter your password">
                            @error('password')
                                <div class="error-message">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="login-options">
                            <div class="form-check">
                                <input id="remember_me" type="checkbox" name="remember" class="form-check-input">
                                <label for="remember_me" class="form-check-label">Remember me</label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-password">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <!-- Login Button -->
                        <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            <span class="btn-text">Sign In</span>
                        </button>
                    </form>

                    <!-- Sign Up Link -->
                    <div class="signup-link">
                        Don't have an account? 
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Sign up here</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    
    // Add loading state to submit button
    form.addEventListener('submit', function(e) {
        // Show loading state immediately
        loginBtn.disabled = true;
        btnText.innerHTML = '<span class="loading-spinner me-2"></span>Signing In...';
    });
    
    // Add focus effects to input fields
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#007bff';
            this.style.backgroundColor = 'white';
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.style.borderColor = '#e9ecef';
                this.style.backgroundColor = '#f8f9fa';
            }
        });
    });
    
    // Add smooth animations to error messages
    const errorMessages = document.querySelectorAll('.error-message');
    errorMessages.forEach(msg => {
        msg.style.opacity = '0';
        
        setTimeout(() => {
            msg.style.transition = 'opacity 0.3s ease';
            msg.style.opacity = '1';
        }, 100);
    });
});
</script>
@endsection