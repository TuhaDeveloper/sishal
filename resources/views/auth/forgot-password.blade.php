<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - SISAL FASHION</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .forgot-password-page {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
    
        .forgot-password-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            padding: 50px 40px;
        }
        
        .forgot-password-title {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .forgot-password-title h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 2rem;
        }
        
        .forgot-password-title p {
            color: #666;
            margin: 0;
            font-size: 1rem;
            line-height: 1.6;
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
            border-color: #00512C;
            box-shadow: 0 0 0 0.2rem rgba(0, 81, 44, 0.25);
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
        
        .btn-send-link {
            height: 55px;
            background: linear-gradient(135deg, #00512C 0%, #0b7a4a 100%);
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .btn-send-link:hover {
            background: linear-gradient(135deg, #004124 0%, #00512C 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 81, 44, 0.3);
        }
        
        .btn-send-link:active {
            transform: translateY(0);
        }
        
        .btn-send-link:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .remember-login {
            text-align: center;
            margin-top: 25px;
        }
        
        .remember-login a {
            color: #00512C;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
        }
        
        .remember-login a:hover {
            color: #004124;
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
        
        @media (max-width: 768px) {
            body {
                align-items: flex-start;
            }
            
            .forgot-password-page {
                padding: 20px;
                margin-top: 20px;
            }
            
            .forgot-password-card {
                padding: 30px 25px;
                margin: 10px;
            }
            
            .forgot-password-title h2 {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }
            
            .forgot-password-title p {
                font-size: 0.95rem;
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
            
            .btn-send-link {
                height: 50px;
                font-size: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .forgot-password-page {
                padding: 15px;
            }
            
            .forgot-password-card {
                padding: 25px 20px;
                margin: 5px;
                border-radius: 15px;
            }
            
            .forgot-password-title h2 {
                font-size: 1.4rem;
            }
            
            .forgot-password-title p {
                font-size: 0.9rem;
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
            
            .btn-send-link {
                height: 48px;
                font-size: 14px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Forgot Password Section -->
<div class="forgot-password-page">
    <div class="forgot-password-card">
        <!-- Title -->
        <div class="forgot-password-title">
            <h2>Forgot Password?</h2>
            <p>No problem. Just let us know your email address and we will email you a password reset link.</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <div class="success-message">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('status') }}
            </div>
        @endif

        <!-- Forgot Password Form -->
        <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
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

            <!-- Send Link Button -->
            <button type="submit" class="btn btn-primary btn-send-link" id="sendLinkBtn">
                <i class="fas fa-paper-plane me-2"></i>
                <span class="btn-text">Send Reset Link</span>
            </button>
        </form>

        <!-- Login Link -->
        <div class="remember-login">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-left me-2"></i>Back to Login
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const sendLinkBtn = document.getElementById('sendLinkBtn');
    const btnText = sendLinkBtn.querySelector('.btn-text');
    
    // Add loading state to submit button
    form.addEventListener('submit', function(e) {
        // Show loading state immediately
        sendLinkBtn.disabled = true;
        btnText.innerHTML = '<span class="loading-spinner me-2"></span>Sending Link...';
    });
    
    // Add focus effects to input fields
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.style.borderColor = '#00512C';
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
</body>
</html>
