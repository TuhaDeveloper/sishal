@extends('erp.master')

@section('title', 'Profile Settings')

@push('styles')
<style>
    .profile-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .profile-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .avatar-upload {
        position: relative;
        display: inline-block;
    }
    .avatar-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        cursor: pointer;
    }
    .avatar-upload:hover .avatar-overlay {
        opacity: 1;
    }
    .password-strength {
        height: 4px;
        border-radius: 2px;
        margin-top: 8px;
        transition: all 0.3s ease;
    }
    .strength-weak { background: #dc3545; }
    .strength-medium { background: #ffc107; }
    .strength-strong { background: #28a745; }
    .card-custom {
        border: none;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        border-radius: 12px;
        overflow: hidden;
    }
    .form-control {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
    }
    .btn-custom {
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .activity-item {
        border-left: 3px solid #007bff;
        padding-left: 15px;
        margin-bottom: 15px;
    }
</style>
@endpush

@section('body')
@include('erp.components.sidebar')
<div class="main-content" id="mainContent">
    @include('erp.components.header')
    <div class="container py-5">
        <!-- Profile Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex align-items-center">
                    <div class="avatar-upload me-4">
                        <img src="{{ Auth::user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->first_name . ' ' . Auth::user()->last_name) . '&background=007bff&color=fff&size=80' }}" 
                             alt="Profile Avatar" class="profile-avatar" id="avatarPreview">
                        <div class="avatar-overlay">
                            <i class="fas fa-camera text-white"></i>
                        </div>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    </div>
                    <div>
                        <h2 class="mb-1">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</h2>
                        <p class="text-muted mb-0">{{ Auth::user()->email }}</p>
                        <small class="text-muted">Member since {{ Auth::user()->created_at->format('M Y') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-edit text-primary me-2"></i>
                            <h5 class="mb-0">Profile Information</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('erp.profile.update') }}" id="profileForm">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label fw-semibold">
                                            <i class="fas fa-user me-1"></i>First Name
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('first_name') is-invalid @enderror" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="{{ old('first_name', Auth::user()->first_name) }}" 
                                               required
                                               maxlength="50">
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label fw-semibold">
                                            <i class="fas fa-user me-1"></i>Last Name
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('last_name') is-invalid @enderror" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="{{ old('last_name', Auth::user()->last_name) }}" 
                                               required
                                               maxlength="50">
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', Auth::user()->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label fw-semibold">
                                            <i class="fas fa-phone me-1"></i>Phone Number
                                        </label>
                                        <input type="tel" 
                                               class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" 
                                               name="phone" 
                                               value="{{ old('phone', Auth::user()->phone) }}"
                                               placeholder="+1 (555) 123-4567">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="timezone" class="form-label fw-semibold">
                                            <i class="fas fa-clock me-1"></i>Timezone
                                        </label>
                                        <select class="form-select @error('timezone') is-invalid @enderror" 
                                                id="timezone" 
                                                name="timezone">
                                            <option value="">Select Timezone</option>
                                            <option value="UTC" {{ old('timezone', Auth::user()->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                            <option value="America/New_York" {{ old('timezone', Auth::user()->timezone) == 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                            <option value="America/Chicago" {{ old('timezone', Auth::user()->timezone) == 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                            <option value="America/Denver" {{ old('timezone', Auth::user()->timezone) == 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                            <option value="America/Los_Angeles" {{ old('timezone', Auth::user()->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                        </select>
                                        @error('timezone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary btn-custom">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-custom" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i>Reset Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card card-custom">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-lock text-warning me-2"></i>
                            <h5 class="mb-0">Change Password</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        @if (session('password_status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>{{ session('password_status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        
                        <form method="POST" action="{{ route('erp.profile.password') }}" id="passwordForm">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label fw-semibold">
                                    <i class="fas fa-key me-1"></i>Current Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current_password" 
                                           name="current_password" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                        <i class="fas fa-eye" id="current_password_icon"></i>
                                    </button>
                                </div>
                                @error('current_password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-1"></i>New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password" 
                                           required
                                           minlength="8"
                                           oninput="checkPasswordStrength(this.value)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="fas fa-eye" id="password_icon"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <small class="form-text text-muted">
                                    Password must be at least 8 characters with uppercase, lowercase, number, and special character.
                                </small>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label fw-semibold">
                                    <i class="fas fa-check-double me-1"></i>Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation" 
                                           required
                                           oninput="checkPasswordMatch()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                                        <i class="fas fa-eye" id="password_confirmation_icon"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="form-text"></div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning btn-custom" id="changePasswordBtn" disabled>
                                <i class="fas fa-shield-alt me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-lg-4">
                <!-- Account Activity -->
                <div class="card card-custom mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-history text-info me-2"></i>
                            <h6 class="mb-0">Recent Activity</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="activity-item">
                            <small class="text-muted">Last login</small>
                            <div class="fw-semibold">{{ Auth::user()->last_login_at?->diffForHumans() ?? 'Never' }}</div>
                        </div>
                        <div class="activity-item">
                            <small class="text-muted">Profile updated</small>
                            <div class="fw-semibold">{{ Auth::user()->updated_at->diffForHumans() }}</div>
                        </div>
                        <div class="activity-item">
                            <small class="text-muted">Account created</small>
                            <div class="fw-semibold">{{ Auth::user()->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card card-custom">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            <h6 class="mb-0">Security Settings</h6>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-semibold">Two-Factor Authentication</div>
                                <small class="text-muted">Add an extra layer of security</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="twoFactorSwitch" 
                                       {{ Auth::user()->two_factor_enabled ? 'checked' : '' }}>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Email Notifications</div>
                                <small class="text-muted">Receive security alerts</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" 
                                       {{ Auth::user()->email_notifications ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('passwordStrength');
    const btn = document.getElementById('changePasswordBtn');
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    strengthBar.className = 'password-strength';
    
    if (strength < 3) {
        strengthBar.classList.add('strength-weak');
    } else if (strength < 5) {
        strengthBar.classList.add('strength-medium');
    } else {
        strengthBar.classList.add('strength-strong');
    }
    
    checkPasswordMatch();
}

// Password match checker
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirmation').value;
    const matchDiv = document.getElementById('passwordMatch');
    const btn = document.getElementById('changePasswordBtn');
    
    if (confirm === '') {
        matchDiv.innerHTML = '';
        btn.disabled = true;
        return;
    }
    
    if (password === confirm) {
        matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>Passwords match</span>';
        btn.disabled = password.length < 8;
    } else {
        matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Passwords do not match</span>';
        btn.disabled = true;
    }
}

// Reset form
function resetForm() {
    document.getElementById('profileForm').reset();
}

// Avatar upload (placeholder - requires backend implementation)
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

document.querySelector('.avatar-upload').addEventListener('click', function() {
    document.getElementById('avatarInput').click();
});

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>
@endpush
@endsection