@extends('ecommerce.master')

@section('main-section')
    <!-- Hero Section -->
    

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">

        <!-- Contact Information -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="mb-4">
                    <h2 class="section-title mb-2 text-start">Get in Touch</h2>
                    <p class="section-subtitle text-start mb-0">We're here to help you find the perfect water purification solution for your needs</p>
                </div>

                <div class="row g-4">
                    <!-- Contact Cards -->
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="service-icon d-inline-flex align-items-center justify-content-center mb-4"
                                    style="width: 80px; height: 80px;">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h4 class="promo-title mb-3">Call Us</h4>
                                <p class="promo-description mb-3">Speak directly with our experts</p>
                                <p class="stat-number fs-5">{{$general_settings->contact_phone}}</p>
                                <p class="stat-label">Mon - Fri: 9:00 AM - 6:00 PM</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="service-icon d-inline-flex align-items-center justify-content-center mb-4"
                                    style="width: 80px; height: 80px;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h4 class="promo-title mb-3">Email Us</h4>
                                <p class="promo-description mb-3">Send us your questions anytime</p>
                                <p class="stat-number fs-6">{{$general_settings->contact_email}}</p>
                                <p class="stat-label">We respond within 24 hours</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm text-center">
                            <div class="card-body p-4">
                                <div class="service-icon d-inline-flex align-items-center justify-content-center mb-4"
                                    style="width: 80px; height: 80px;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <h4 class="promo-title mb-3">Visit Us</h4>
                                <p class="promo-description mb-3">Come to our showroom</p>
                                <p class="stat-number fs-6">{{$general_settings->contact_address}}</p>
                                <p class="stat-label">Open 7 days a week</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form + Support Image Section -->
        <div class="row g-4 align-items-stretch mb-5">
            <!-- Left: Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-4">
                            <h3 class="section-title mb-2">Can't find the answer you are looking for?</h3>
                            <p class="text-muted mb-0">Our friendly assistant is here to assist you 24 hours a day!</p>
                        </div>
                        <form id="contactForm" method="POST" action="{{ route('contact.submit') }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="fullName" class="form-label promo-title">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="fullName" name="full_name" placeholder="Enter full name" required>
                                    <div class="invalid-feedback" id="fullNameError"></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phoneNumber" class="form-label promo-title">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phoneNumber" name="phone_number" placeholder="Enter phone number" required>
                                    <div class="invalid-feedback" id="phoneNumberError"></div>
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label promo-title">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter subject line" required>
                                    <div class="invalid-feedback" id="subjectError"></div>
                                </div>
                                <div class="col-12">
                                    <label for="messageText" class="form-label promo-title">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="messageText" name="message" rows="6" placeholder="Write your message ..." required></textarea>
                                    <div class="invalid-feedback" id="messageError"></div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary-custom px-4" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm d-none" id="spinner" role="status" aria-hidden="true"></span>
                                    <span id="submitText">Send</span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Success/Error Messages -->
                        <div id="alertContainer" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Right: Support Image -->
            <div class="col-lg-5">
                <div class="h-100 w-100 rounded-3 overflow-hidden shadow-sm d-flex align-items-center justify-content-center" style="background:#f7f8fb;">
                    @if(isset($general_settings->support_image) && $general_settings->support_image)
                        <img src="{{ asset($general_settings->support_image) }}" alt="Customer Support" class="img-fluid" style="object-fit:cover; max-height: 520px;">
                    @else
                        <img src="{{ asset('public/static/customer-support.jpg') }}" alt="Customer Support" class="img-fluid" style="object-fit:cover; max-height: 520px;">
                    @endif
                </div>
            </div>
        </div>
        </div>

    <!-- Font Awesome is already loaded in master.blade.php - no need for dynamic loading -->

    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const submitText = document.getElementById('submitText');
        const alertContainer = document.getElementById('alertContainer');

        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            clearErrors();
            clearAlerts();
            
            // Show loading state
            setLoadingState(true);
            
            // Get form data
            const formData = new FormData(contactForm);
            
            // Send AJAX request
            fetch(contactForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                setLoadingState(false);
                
                if (data.success) {
                    showAlert('success', data.message);
                    contactForm.reset();
                } else {
                    if (data.errors) {
                        showValidationErrors(data.errors);
                    } else {
                        showAlert('danger', data.message);
                    }
                }
            })
            .catch(error => {
                setLoadingState(false);
                console.error('Error:', error);
                showAlert('danger', 'An error occurred. Please try again.');
            });
        });

        function setLoadingState(loading) {
            if (loading) {
                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                submitText.textContent = 'Sending...';
            } else {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                submitText.textContent = 'Send';
            }
        }

        function clearErrors() {
            const errorElements = document.querySelectorAll('.invalid-feedback');
            errorElements.forEach(element => {
                element.textContent = '';
                element.style.display = 'none';
            });
            
            const inputElements = document.querySelectorAll('.form-control');
            inputElements.forEach(element => {
                element.classList.remove('is-invalid');
            });
        }

        function showValidationErrors(errors) {
            Object.keys(errors).forEach(field => {
                const errorElement = document.getElementById(field + 'Error');
                const inputElement = document.getElementById(field === 'full_name' ? 'fullName' : 
                                                         field === 'phone_number' ? 'phoneNumber' : 
                                                         field === 'message' ? 'messageText' : field);
                
                if (errorElement && inputElement) {
                    errorElement.textContent = errors[field][0];
                    errorElement.style.display = 'block';
                    inputElement.classList.add('is-invalid');
                }
            });
        }

        function clearAlerts() {
            alertContainer.innerHTML = '';
        }

        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            alertContainer.innerHTML = alertHtml;
        }
    });
    </script>
@endsection


