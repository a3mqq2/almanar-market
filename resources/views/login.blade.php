<!doctype html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8" />
        <title>تسجيل الدخول</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <link rel="shortcut icon" href="{{ asset('assets/images/logo-sm.png') }}" />

        <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&display=swap" rel="stylesheet" />

        <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

        <style>
            :root {
                --login-bg: #ffffff;
                --login-text: #1a1a2e;
                --login-text-secondary: #6b7280;
                --login-input-bg: #f9fafb;
                --login-input-border: #e5e7eb;
                --login-input-focus-border: #4f46e5;
                --login-btn-bg: #4f46e5;
                --login-btn-hover: #4338ca;
                --login-overlay: rgba(15, 15, 35, 0.55);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Almarai', sans-serif !important;
            }

            html, body {
                height: 100%;
                direction: rtl;
                text-align: right;
            }

            body {
                display: flex;
                background: var(--login-bg);
            }

            .login-wrapper {
                display: flex;
                width: 100%;
                min-height: 100vh;
            }

            .login-image-side {
                flex: 1;
                position: relative;
                background: url('{{ asset('assets/images/auth.jpg') }}') center/cover no-repeat;
                display: flex;
                align-items: flex-end;
                justify-content: center;
            }

            .login-image-side::after {
                content: '';
                position: absolute;
                inset: 0;
                background: var(--login-overlay);
            }

            .image-content {
                position: relative;
                z-index: 1;
                padding: 48px;
                color: #fff;
                text-align: center;
            }

            .image-content h2 {
                font-size: 28px;
                font-weight: 800;
                margin-bottom: 8px;
                letter-spacing: -0.3px;
            }

            .image-content p {
                font-size: 15px;
                font-weight: 300;
                opacity: 0.85;
            }

            .login-form-side {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px;
                background: var(--login-bg);
            }

            .login-form-container {
                width: 100%;
                max-width: 400px;
            }

            .login-logo {
                margin-bottom: 40px;
                text-align: center;
            }

            .login-logo img {
                height: 64px;
            }

            .login-heading {
                margin-bottom: 32px;
            }

            .login-heading h1 {
                font-size: 24px;
                font-weight: 800;
                color: var(--login-text);
                margin-bottom: 6px;
            }

            .login-heading p {
                font-size: 14px;
                color: var(--login-text-secondary);
                font-weight: 400;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                font-size: 13px;
                font-weight: 700;
                color: var(--login-text);
                margin-bottom: 6px;
            }

            .form-group label .required {
                color: #ef4444;
                margin-right: 2px;
            }

            .input-wrapper {
                position: relative;
            }

            .input-wrapper i {
                position: absolute;
                right: 14px;
                top: 50%;
                transform: translateY(-50%);
                color: var(--login-text-secondary);
                font-size: 18px;
                pointer-events: none;
            }

            .input-wrapper input {
                width: 100%;
                padding: 12px 44px 12px 14px;
                border: 1.5px solid var(--login-input-border);
                border-radius: 10px;
                background: var(--login-input-bg);
                font-size: 14px;
                font-family: 'Almarai', sans-serif !important;
                color: var(--login-text);
                direction: rtl;
                text-align: right;
                transition: border-color 0.2s;
                outline: none;
            }

            .input-wrapper input::placeholder {
                color: #9ca3af;
                text-align: right;
            }

            .input-wrapper input:focus {
                border-color: var(--login-input-focus-border);
                background: #fff;
            }

            .input-wrapper input.is-invalid {
                border-color: #ef4444;
            }

            .invalid-feedback {
                display: block;
                font-size: 12px;
                color: #ef4444;
                margin-top: 4px;
            }

            .remember-row {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 24px;
            }

            .remember-row input[type="checkbox"] {
                width: 16px;
                height: 16px;
                accent-color: var(--login-btn-bg);
                cursor: pointer;
            }

            .remember-row label {
                font-size: 13px;
                color: var(--login-text-secondary);
                cursor: pointer;
            }

            .login-btn {
                width: 100%;
                padding: 13px;
                border: none;
                border-radius: 10px;
                background: var(--login-btn-bg);
                color: #fff;
                font-size: 15px;
                font-weight: 700;
                font-family: 'Almarai', sans-serif !important;
                cursor: pointer;
                transition: background 0.2s;
            }

            .login-btn:hover {
                background: var(--login-btn-hover);
            }

            .login-btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }

            .spinner-border {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255,255,255,0.3);
                border-radius: 50%;
                border-top-color: #fff;
                animation: spin 0.6s linear infinite;
                vertical-align: middle;
                margin-left: 6px;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            @media (max-width: 991px) {
                .login-image-side {
                    display: none;
                }

                .login-form-side {
                    padding: 32px 24px;
                }
            }
        </style>
    </head>

    <body>
        <div class="login-wrapper">
            <div class="login-form-side">
                <div class="login-form-container">
                    <div class="login-logo">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('HULUL ERP.png') }}" alt="logo" />
                        </a>
                    </div>

                    <div class="login-heading">
                        <h1>مرحباً بك</h1>
                        <p>أدخل بيانات الدخول للمتابعة</p>
                    </div>

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="form-group">
                            <label for="userLogin">
                                اسم المستخدم
                                <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="ti ti-user"></i>
                                <input type="text" class="@error('login') is-invalid @enderror" id="userLogin" name="login" value="{{ old('login') }}" placeholder="username" required autofocus />
                            </div>
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="userPassword">
                                كلمة المرور
                                <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="ti ti-lock"></i>
                                <input type="password" class="@error('password') is-invalid @enderror" id="userPassword" name="password" placeholder="••••••••" required />
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="remember-row">
                            <input type="checkbox" id="rememberMe" name="remember" />
                            <label for="rememberMe">تذكرني</label>
                        </div>

                        <button type="submit" class="login-btn" id="loginBtn">دخول</button>
                    </form>
                </div>
            </div>

            <div class="login-image-side">
                <div class="image-content">
                    <h2>نظام حلول لإدارة الأعمال</h2>
                    <p>إدارة متكاملة للمبيعات والمخزون والحسابات</p>
                </div>
            </div>
        </div>

        <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

        <script>
            document.getElementById('userLogin').addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('userPassword').focus();
                }
            });

            document.querySelector('form').addEventListener('submit', function() {
                var btn = document.getElementById('loginBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border"></span>جاري الدخول...';
            });
        </script>
    </body>
</html>
