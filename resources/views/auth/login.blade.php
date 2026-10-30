@extends('layouts.app')

@section('content')
    <div class="login-page min-vh-100 d-flex align-items-center position-relative overflow-hidden"
        style="background: linear-gradient(135deg, #f8fafc 0%, #e0e7ff 100%);">

        <div class="position-absolute top-0 start-0 w-100 h-100" style="z-index: 1; opacity: 0.85;">
            <svg width="100%" height="100%" viewBox="0 0 1440 320" preserveAspectRatio="none"
                style="position: absolute; bottom: 0;">

                <!-- Dark Blue Wave Layer 1 -->
                <path fill="#1e3a8a" fill-opacity="0.75"
                    d="M0,192L48,197.3C96,203,192,213,288,213.3C384,213,480,203,576,186.7C672,171,768,149,864,154.7C960,160,1056,192,1152,197.3C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>

                <!-- Dark Blue Wave Layer 2 (slightly different shade) -->
                <path fill="#1e40af" fill-opacity="0.65"
                    d="M0,256L48,240C96,224,192,192,288,186.7C384,181,480,203,576,213.3C672,224,768,224,864,202.7C960,181,1056,139,1152,122.7C1248,107,1344,117,1392,122.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z">
                </path>
            </svg>
        </div>

        <div class="container position-relative" style="z-index: 2;">
            <div class="row justify-content-center">
                <div class="col-lg-5 col-md-7">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body login-card-body">

                            <!-- Header -->
                            <div class="text-center mb-1">
                                <img src="{{ asset('images/template/Fablead_CRM.jpg') }}" alt="Fablead CRM"
                                    class="login-logo img-fluid mb-3"
                                    onerror="this.onerror=null;this.src='{{ url('public/images/template/Fablead_CRM.jpg') }}';">
                                <p class="text-muted mb-0" style="font-size: 1rem;">Welcome to Fablead CRM, Empowering your
                                    solar business with a smart, all-in-one CRM.</p>
                            </div>

                            <div class="login-heading">
                                <div class="d-flex align-items-center gap-2">
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                    <span class="fs-3 px-1 text-dark-blue">Login</span>
                                    <hr class="flex-grow-1 border-1 border-secondary">
                                </div>
                            </div>

                            <form id="login-form" method="POST" action="{{ route('login.submit') }}" novalidate>
                                @csrf



                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-medium">Email Address</label>
                                    <input id="email" type="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email') }}" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-medium">Password</label>
                                    <div class="position-relative">
                                        <input id="password" type="password"
                                            class="form-control form-control-lg @error('password') is-invalid @enderror"
                                            name="password" required style="padding-right: 50px;">
                                        <button type="button"
                                            class="btn position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent text-muted toggle-password"
                                            style="z-index: 10; margin-right: 10px;">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Forgot Password -->
                                <!-- Removed: Forgot Password functionality not implemented -->

                                <!-- Login Button -->
                                <div class="d-grid">
                                    <button type="submit" class="login-submit btn btn-lg fw-semibold text-white"
                                        style="background: linear-gradient(135deg, #2b3a69, #182244); border: none;">
                                        <span class="login-button-label">Sign In</span>
                                        <span class="login-button-loading d-none" role="status">Signing in<span class="login-loading-dots" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span></span>
                                    </button>
                                </div>
                            </form>

                            <footer class="mt-3 text-center text-muted" style="font-size: 0.9rem; font-weight: 500;">
                                &copy; {{ date('Y') }} Copyright - <a href="https://www.fableadtechnolabs.com/" target="_blank" rel="noopener noreferrer" class="text-muted">Fablead Developers Technolab</a>
                            </footer>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        @if(session('expired_error'))
            <div class="modal fade" id="loginExpiryModal" tabindex="-1" aria-labelledby="loginExpiryModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-bottom-0 pb-3 pt-3 px-4" style="background-color: #122137;">
                            <h5 class="modal-title fw-bold d-flex align-items-center gap-2 m-0 text-white" id="loginExpiryModalLabel" style="font-size: 1.15rem;">
                                <i class="fa-solid fa-circle-xmark text-white"></i>
                                <span>Subscription Expired</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        
                        <div class="modal-body p-4">
                            <p class="text-center text-secondary mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                                Your <strong>{{ session('expired_error')['plan_name'] ?? 'Plan' }}</strong> subscription expired on <strong class="text-danger">{{ session('expired_error')['end_date'] ?? '' }}</strong>.<br>
                                Please renew your plan to continue using the platform.
                            </p>

                            <div class="mx-auto" style="max-width: 90%;">
                                <h6 class="fw-bold mb-3" style="color: #f58220; font-size: 1.05rem;">Need Help? Contact Us</h6>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fa-solid fa-phone me-3 fs-5 text-secondary" style="color: #64748b !important;"></i>
                                    <span class="fw-medium text-secondary" style="font-size: 0.95rem;">+91 98247 34531</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fa-solid fa-envelope me-3 fs-5 text-secondary" style="color: #64748b !important;"></i>
                                    <span class="fw-medium text-secondary" style="font-size: 0.95rem;">info@fableadtechnolabs.com</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var loginExpiryModal = new bootstrap.Modal(document.getElementById('loginExpiryModal'));
                    loginExpiryModal.show();
                });
            </script>
        @endif
    </div>

    <style>
        .login-page {
            padding-block: 16px;
        }

        .login-card-body {
            padding: 24px 32px;
        }

        .login-logo {
            max-width: 200px;
        }

        .login-heading {
            margin-block: 16px;
        }

        .login-page .card {
            border-radius: 20px !important;
        }

        .login-page .form-control,
        .login-page .login-submit {
            box-sizing: border-box;
            width: 100%;
            height: 48px;
            min-height: 48px;
            padding: 10px 16px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .login-page .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
        }

        .login-loading-dots span {
            animation: login-dot 1.2s infinite;
            opacity: 0;
        }

        .login-loading-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .login-loading-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes login-dot {
            0%, 20% { opacity: 0; }
            40%, 100% { opacity: 1; }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-loading-dots span {
                animation: none;
                opacity: 1;
            }
        }

        @media (min-width: 992px) {
            .login-page .login-card-body {
                padding: 36px 40px;
            }

            .login-page .login-heading {
                margin-block: 10px;
            }

            .login-page form > .mb-3 {
                margin-bottom: 14px !important;
            }

            .login-page .form-label {
                margin-bottom: 10px;
                font-size: 1.0625rem;
            }

            .login-page .form-control,
            .login-page .login-submit {
                height: 52px;
                min-height: 52px;
                padding: 12px 18px;
                font-size: 1.0625rem;
            }

            .login-page footer {
                margin-top: 24px !important;
            }
        }

        @media (max-width: 575.98px) {
            .login-page > .container {
                padding-inline: 8px;
            }

            .login-page > .container > .row {
                --bs-gutter-x: 0;
            }

            .login-card-body {
                padding: 25px 20px;
            }

            .login-logo {
                max-width: 160px;
            }

            .login-heading {
                margin-block: 12px;
            }

            .login-page .form-label {
                margin-bottom: 6px;
            }
        }

        .form-control:focus {
            border-color: #17234a;
            box-shadow: 0 0 0 4px rgba(27, 28, 77, 0.12);
        }

        .text-dark-blue {
            color: #17234a !important;
        }

        [data-theme="dark"] .text-dark-blue {
            color: #ffffff !important;
        }

        .toggle-password:focus {
            box-shadow: none !important;
            outline: none !important;
        }

        .toggle-password i {
            font-size: 1.25rem;
            transition: color 0.2s ease-in-out;
        }

        .toggle-password:hover i {
            color: #17234a !important;
        }

        [data-theme="dark"] .toggle-password:hover i {
            color: #ffffff !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.querySelector('#login-form');
            const submitButton = loginForm.querySelector('.login-submit');
            const buttonLabel = submitButton.querySelector('.login-button-label');
            const loadingLabel = submitButton.querySelector('.login-button-loading');

            loginForm.addEventListener('submit', function (event) {
                if (submitButton.disabled) {
                    event.preventDefault();
                    return;
                }

                submitButton.disabled = true;
                submitButton.setAttribute('aria-busy', 'true');
                buttonLabel.classList.add('d-none');
                loadingLabel.classList.remove('d-none');
            });

            window.addEventListener('pageshow', function () {
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                buttonLabel.classList.remove('d-none');
                loadingLabel.classList.add('d-none');
            });

            const togglePassword = document.querySelector('.toggle-password');
            const passwordInput = document.querySelector('#password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function () {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const icon = this.querySelector('i');
                    if (type === 'text') {
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            }
        });
    </script>
@endsection
