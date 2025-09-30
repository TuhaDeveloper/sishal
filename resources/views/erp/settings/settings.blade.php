@extends('erp.master')

@section('title', 'Settings')

@section('body')
    @include('erp.components.sidebar')
    <div class="main-content bg-light min-vh-100" id="mainContent">
        @include('erp.components.header')
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-10">
                    <!-- Page Header -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h2 class="mb-1 fw-bold text-dark">Settings</h2>
                            <p class="text-muted mb-0">Manage your application settings and preferences</p>
                        </div>
                    </div>

                    <!-- Settings Card -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white p-0 border-0">
                            <!-- Modern Tab Navigation -->
                            <ul class="nav nav-tabs nav-tabs-modern border-0 px-4 pt-4" id="settingsTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                        <i class="fas fa-cog me-2"></i>General
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab" aria-controls="branding" aria-selected="false">
                                        <i class="fas fa-palette me-2"></i>Branding
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">
                                        <i class="fas fa-address-book me-2"></i>Contact Info
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">
                                        <i class="fas fa-share-alt me-2"></i>Social Media
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" id="settingsForm">
                                @csrf
                                @method('POST')
                                
                                <div class="tab-content" id="settingsTabContent">
                                    <!-- General Tab -->
                                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <h5 class="fw-semibold text-dark mb-3">Basic Information</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Site Title</label>
                                                <input type="text" name="site_title" class="form-control form-control-lg" placeholder="Enter your site title" value="{{ $settings->site_title ?? '' }}">
                                                <small class="text-muted">This appears in browser tabs and search results</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Site Description</label>
                                                <textarea name="site_description" class="form-control" rows="3" placeholder="Brief description of your site">{{ $settings->site_description ?? '' }}</textarea>
                                                <small class="text-muted">Used for SEO and meta descriptions</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Top Text</label>
                                                <input type="text" name="top_text" class="form-control" placeholder="Header announcement text" value="{{ $settings->top_text ?? '' }}">
                                                <small class="text-muted">Displays at the top of your website</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Footer Text</label>
                                                <input type="text" name="footer_text" class="form-control" placeholder="Copyright or footer message" value="{{ $settings->footer_text ?? '' }}">
                                                <small class="text-muted">Appears in the website footer</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Branding Tab -->
                                    <div class="tab-pane fade" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <h5 class="fw-semibold text-dark mb-3">Visual Identity</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Site Logo</label>
                                                <div class="upload-area border-2 border-dashed rounded-3 p-4 text-center">
                                                    <input type="file" name="site_logo" class="form-control d-none" id="logoUpload" accept="image/*">
                                                    <label for="logoUpload" class="cursor-pointer">
                                                        @if(!empty($settings->site_logo))
                                                            <img src="{{ asset($settings->site_logo) }}" alt="Logo" class="img-thumbnail mb-2" style="max-height: 80px;">
                                                            <div class="text-muted small">Click to change logo</div>
                                                        @else
                                                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                                            <div class="text-muted">Click to upload logo</div>
                                                            <small class="text-muted d-block">PNG, JPG, SVG up to 2MB</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">Site Favicon</label>
                                                <div class="upload-area border-2 border-dashed rounded-3 p-4 text-center">
                                                    <input type="file" name="site_favicon" class="form-control d-none" id="faviconUpload" accept="image/*">
                                                    <label for="faviconUpload" class="cursor-pointer">
                                                        @if(!empty($settings->site_favicon))
                                                            <img src="{{ asset($settings->site_favicon) }}" alt="Favicon" class="img-thumbnail mb-2" style="max-height: 60px;">
                                                            <div class="text-muted small">Click to change favicon</div>
                                                        @else
                                                            <i class="fas fa-image fa-2x text-muted mb-2"></i>
                                                            <div class="text-muted">Click to upload favicon</div>
                                                            <small class="text-muted d-block">ICO, PNG 16x16 or 32x32</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact Information Tab -->
                                    <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <h5 class="fw-semibold text-dark mb-3">Contact Details</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fas fa-envelope me-2 text-primary"></i>Email Address
                                                </label>
                                                <input type="email" name="contact_email" class="form-control" placeholder="contact@example.com" value="{{ $settings->contact_email ?? '' }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fas fa-phone me-2 text-success"></i>Phone Number
                                                </label>
                                                <input type="text" name="contact_phone" class="form-control" placeholder="+1 (555) 123-4567" value="{{ $settings->contact_phone ?? '' }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-medium">
                                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>Address
                                                </label>
                                                <textarea name="contact_address" class="form-control" rows="3" placeholder="Enter your business address">{{ $settings->contact_address ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Social Media Tab -->
                                    <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <h5 class="fw-semibold text-dark mb-3">Social Media Links</h5>
                                                <p class="text-muted">Connect your social media profiles</p>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fab fa-facebook text-primary me-2"></i>Facebook URL
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://</span>
                                                    <input type="text" name="facebook_url" class="form-control" placeholder="facebook.com/yourpage" value="{{ str_replace('https://', '', $settings->facebook_url ?? '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fab fa-twitter text-info me-2"></i>X (Twitter) URL
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://</span>
                                                    <input type="text" name="x_url" class="form-control" placeholder="x.com/yourusername" value="{{ str_replace('https://', '', $settings->x_url ?? '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fab fa-youtube text-danger me-2"></i>YouTube URL
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://</span>
                                                    <input type="text" name="youtube_url" class="form-control" placeholder="youtube.com/yourchannel" value="{{ str_replace('https://', '', $settings->youtube_url ?? '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-medium">
                                                    <i class="fab fa-instagram text-warning me-2"></i>Instagram URL
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text">https://</span>
                                                    <input type="text" name="instagram_url" class="form-control" placeholder="instagram.com/yourusername" value="{{ str_replace('https://', '', $settings->instagram_url ?? '') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="border-top mt-5 pt-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-save me-2"></i>Save Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .nav-tabs-modern .nav-link {
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
            transition: all 0.3s ease;
            margin-right: 0.5rem;
        }

        .nav-tabs-modern .nav-link:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .nav-tabs-modern .nav-link.active {
            background: #fff;
            color: #0d6efd !important;
            border-bottom: 2px solid #0d6efd !important;
        }

        .upload-area {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: #0d6efd !important;
            background: #f8f9ff;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .form-control {
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .btn {
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .card {
            border-radius: 1rem;
        }

        .form-label {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .text-muted {
            font-size: 0.875rem;
        }

        .input-group-text {
            background: #f8f9fa;
            border-color: #dee2e6;
            font-size: 0.875rem;
        }
    </style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logo preview
        const logoInput = document.querySelector('input[name="site_logo"]');
        const logoUploadArea = document.querySelector('#logoUpload').closest('.upload-area');
        const logoPreview = logoUploadArea.querySelector('img');
        if (logoInput) {
            logoInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        // Hide default text/icon
                        logoUploadArea.querySelectorAll('i, div.text-muted, small').forEach(function(el) { el.style.display = 'none'; });
                        if (logoPreview) {
                            logoPreview.src = ev.target.result;
                            logoPreview.style.display = 'block';
                        } else {
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            img.className = 'img-thumbnail mb-2';
                            img.style.maxHeight = '80px';
                            logoInput.closest('.upload-area').prepend(img);
                        }
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        // Favicon preview
        const faviconInput = document.querySelector('input[name="site_favicon"]');
        const faviconUploadArea = document.querySelector('#faviconUpload').closest('.upload-area');
        const faviconPreview = faviconUploadArea.querySelector('img');
        if (faviconInput) {
            faviconInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        // Hide default text/icon
                        faviconUploadArea.querySelectorAll('i, div.text-muted, small').forEach(function(el) { el.style.display = 'none'; });
                        if (faviconPreview) {
                            faviconPreview.src = ev.target.result;
                            faviconPreview.style.display = 'block';
                        } else {
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            img.className = 'img-thumbnail mb-2';
                            img.style.maxHeight = '60px';
                            faviconInput.closest('.upload-area').prepend(img);
                        }
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }
    });
</script>
@endpush