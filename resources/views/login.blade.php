
<!doctype html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8" />
        <title>تسجيل الدخول</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('assets/images/logo-sm.png') }}" />

        <!-- IBM Plex Sans Arabic Font (Local) -->
        <link href="{{ asset('assets/fonts/fonts.css') }}" rel="stylesheet" type="text/css" />

        <!-- Theme Config Js -->
        <script src="{{ asset('assets/js/config.js') }}"></script>

        <!-- Vendor css -->
        <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

        <style>
            * {
                font-family: 'IBM Plex Sans Arabic', sans-serif !important;
            }
            html, body {
                direction: rtl;
                text-align: right;
            }
            input, textarea, select {
                direction: rtl;
                text-align: right;
            }
            input::placeholder {
                text-align: right;
            }
            .form-control {
                text-align: right;
            }
            .form-check {
                padding-right: 1.5em;
                padding-left: 0;
            }
            .form-check .form-check-input {
                float: right;
                margin-right: -1.5em;
                margin-left: 0;
            }
            .app-search .app-search-icon {
                left: auto;
                right: 12px;
            }
            .app-search input {
                padding-right: 40px;
                padding-left: 12px;
            }
        </style>
    </head>

    <body>
        <div class="position-absolute top-0 end-0" style="opacity: 0.12;">
            <svg width="450" height="450" viewBox="0 0 450 450" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="islamicArch1" x="0" y="0" width="150" height="150" patternUnits="userSpaceOnUse">
                        <!-- Islamic 8-pointed star -->
                        <polygon points="75,10 85,40 115,40 90,60 100,90 75,72 50,90 60,60 35,40 65,40" fill="none" stroke="#8B6914" stroke-width="1.5"/>
                        <polygon points="75,10 85,40 115,40 90,60 100,90 75,72 50,90 60,60 35,40 65,40" fill="none" stroke="#8B6914" stroke-width="1.5" transform="rotate(22.5 75 50)"/>
                        <!-- Dome arch -->
                        <path d="M20,150 Q20,80 75,50 Q130,80 130,150" fill="none" stroke="#8B6914" stroke-width="2"/>
                        <path d="M35,150 Q35,95 75,70 Q115,95 115,150" fill="none" stroke="#8B6914" stroke-width="1"/>
                        <!-- Minaret elements -->
                        <line x1="0" y1="75" x2="20" y2="75" stroke="#8B6914" stroke-width="1"/>
                        <line x1="130" y1="75" x2="150" y2="75" stroke="#8B6914" stroke-width="1"/>
                        <!-- Geometric borders -->
                        <rect x="5" y="5" width="140" height="140" fill="none" stroke="#8B6914" stroke-width="0.5" rx="5"/>
                        <!-- Inner decorative circles -->
                        <circle cx="75" cy="115" r="15" fill="none" stroke="#8B6914" stroke-width="1"/>
                        <circle cx="75" cy="115" r="8" fill="none" stroke="#8B6914" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="450" height="450" fill="url(#islamicArch1)"/>
            </svg>
        </div>
        <div class="position-absolute bottom-0 start-0" style="opacity: 0.12; transform: rotate(180deg);">
            <svg width="450" height="450" viewBox="0 0 450 450" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="islamicArch2" x="0" y="0" width="150" height="150" patternUnits="userSpaceOnUse">
                        <!-- Islamic 8-pointed star -->
                        <polygon points="75,10 85,40 115,40 90,60 100,90 75,72 50,90 60,60 35,40 65,40" fill="none" stroke="#8B6914" stroke-width="1.5"/>
                        <polygon points="75,10 85,40 115,40 90,60 100,90 75,72 50,90 60,60 35,40 65,40" fill="none" stroke="#8B6914" stroke-width="1.5" transform="rotate(22.5 75 50)"/>
                        <!-- Dome arch -->
                        <path d="M20,150 Q20,80 75,50 Q130,80 130,150" fill="none" stroke="#8B6914" stroke-width="2"/>
                        <path d="M35,150 Q35,95 75,70 Q115,95 115,150" fill="none" stroke="#8B6914" stroke-width="1"/>
                        <!-- Minaret elements -->
                        <line x1="0" y1="75" x2="20" y2="75" stroke="#8B6914" stroke-width="1"/>
                        <line x1="130" y1="75" x2="150" y2="75" stroke="#8B6914" stroke-width="1"/>
                        <!-- Geometric borders -->
                        <rect x="5" y="5" width="140" height="140" fill="none" stroke="#8B6914" stroke-width="0.5" rx="5"/>
                        <!-- Inner decorative circles -->
                        <circle cx="75" cy="115" r="15" fill="none" stroke="#8B6914" stroke-width="1"/>
                        <circle cx="75" cy="115" r="8" fill="none" stroke="#8B6914" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="450" height="450" fill="url(#islamicArch2)"/>
            </svg>
        </div>
        <div class="auth-box d-flex align-items-center">
            <div class="container-xxl">
                <div class="row align-items-center justify-content-center">
                    <div class="col-xl-10">
                        <div class="card">
                            <div class="row justify-content-between g-0">
                                <div class="col-lg-6">
                                    <div class="card-body">
                                        <div class="auth-brand text-center mb-4 position-relative">
                                            <a href="{{ url('/') }}" class="logo-dark">
                                                <img src="{{ asset('logo-dark.png') }}" style="height:76px!important;" alt="logo" />
                                            </a>
                                            <a href="{{ url('/') }}" class="logo-light">
                                                <img src="{{ asset('logo-dark.png') }}" style="height: 70px!important;" alt="logo" />
                                            </a>
                                            <h4 class="fw-bold text-dark mt-3">مرحباً بك</h4>
                                            <p class="text-muted">أدخل اسم المستخدم وكلمة المرور للدخول</p>
                                        </div>

                                        <form method="POST" action="{{ route('login.submit') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="userLogin" class="form-label">
                                                    اسم المستخدم أو البريد الإلكتروني
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="app-search">
                                                    <input type="text" class="form-control @error('login') is-invalid @enderror" id="userLogin" name="login" value="{{ old('login') }}" placeholder="username" required autofocus />
                                                    <i class="ti ti-user app-search-icon text-muted"></i>
                                                </div>
                                                @error('login')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <label for="userPassword" class="form-label">
                                                    كلمة المرور
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <div class="app-search">
                                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="userPassword" name="password" placeholder="••••••••" required />
                                                    <i class="ti ti-lock-password app-search-icon text-muted"></i>
                                                </div>
                                                @error('password')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input form-check-input-light fs-14" type="checkbox" id="rememberMe" name="remember" />
                                                    <label class="form-check-label" for="rememberMe">تذكرني</label>
                                                </div>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary fw-semibold py-2">دخول</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none d-lg-block">
                                    <div class="h-100 position-relative card-side-img rounded-end overflow-hidden" style="background-image: url({{ asset('assets/images/auth.jpg') }})">
                                        <div class="p-4 card-img-overlay rounded-end auth-overlay d-flex align-items-end justify-content-center"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor js -->
        <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

        <!-- App js -->
        <script src="{{ asset('assets/js/app.js') }}"></script>

        @if(config('desktop.mode'))
        <script>
        (function() {
            let isOnline = false;
            let isSyncing = false;

            async function checkServerConnection() {
                try {
                    const response = await fetch('{{ config("desktop.server_url") }}/api/v1/sync/timestamp', {
                        method: 'GET',
                        mode: 'cors',
                        cache: 'no-cache',
                        signal: AbortSignal.timeout(5000)
                    });
                    isOnline = response.ok;
                } catch (e) {
                    isOnline = false;
                }
                return isOnline;
            }

            async function syncAction(action) {
                const response = await fetch(`/api/sync/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                return await response.json();
            }

            async function autoSync() {
                if (isSyncing || !isOnline) return;
                isSyncing = true;
                try {
                    await syncAction('push');
                    await syncAction('pull');
                } catch (e) {}
                isSyncing = false;
            }

            async function syncLoop() {
                await checkServerConnection();
                if (isOnline) {
                    await autoSync();
                }
            }

            checkServerConnection();
            setInterval(syncLoop, 60000);
        })();
        </script>
        @endif
    </body>
</html>
