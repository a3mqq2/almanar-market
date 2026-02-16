
<!doctype html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8" />
        <title> المنار ماركت |  @yield('title')</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="base-url" content="{{ rtrim(url('/'), '/') }}" />
        <meta name="description" content="Paces is a modern, responsive admin dashboard available on ThemeForest. Ideal for building CRM, CMS, project management tools, and custom web applications with a clean UI, flexible layouts, and rich features." />
        <meta name="keywords" content="Paces, admin dashboard, ThemeForest, Bootstrap 5 admin, responsive admin, CRM dashboard, CMS admin, web app UI, admin theme, premium admin template" />
        <meta name="author" content="Coderthemes" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('assets/images/logo-sm.png') }}" />

        <!-- IBM Plex Sans Arabic Font (Local) -->
        <link href="{{ asset('assets/fonts/fonts.css') }}" rel="stylesheet" type="text/css" />

        <!-- Vector Maps css -->
        <link href="{{ asset('assets/plugins/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />

        <!-- Theme Config Js -->
        <script>
            (function() {
                var base = document.querySelector('meta[name="base-url"]').getAttribute('content');
                window.__baseUrl = base;
                var _fetch = window.fetch;
                window.fetch = function(url, opts) {
                    if (typeof url === 'string' && url.startsWith('/')) {
                        url = base + url;
                    }
                    return _fetch.call(this, url, opts);
                };
            })();
        </script>
        <script src="{{ asset('assets/js/config.js') }}"></script>

        <!-- Vendor css -->
        <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

        <!-- App css -->
        <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

        <style>
            * {
                font-family: 'IBM Plex Sans Arabic', sans-serif !important;
            }
        </style>
        @stack('styles')
    </head>

    <body dir="rtl">
        <!-- Begin page -->
        <div class="wrapper">
            <header class="app-topbar">
                <div class="container-fluid topbar-menu">
                    <div class="d-flex align-items-center gap-2">
                        <!-- Topbar Brand Logo -->
                        <div class="logo-topbar">
                            <!-- Logo light -->
                            <a href="{{asset('dashboard')}}" class="logo-light">
                                <span class="logo-lg">
                                    <img src="{{asset('logo-dark.png')}}" alt="logo" />
                                </span>
                                <span class="logo-sm">
                                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo" />
                                </span>
                            </a>

                            <!-- Logo Dark -->
                            <a href="{{asset('dashboard')}}" class="logo-dark">
                                <span class="logo-lg">
                                    <img src="{{asset('logo-dark.png')}}" alt="dark logo" style="height: 100px !important;" />
                                </span>
                                <span class="logo-sm">
                                    <img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo" />
                                </span>
                            </a>
                        </div>

                        <!-- Sidebar Menu Toggle Button -->
                        <button class="sidenav-toggle-button btn btn-primary btn-icon">
                            <i class="ti ti-menu-4"></i>
                        </button>

                        <!-- Horizontal Menu Toggle Button -->
                        <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu">
                            <i class="ti ti-menu-4"></i>
                        </button>

                        <div id="search-box-rounded" class="app-search d-none d-xl-flex">
                            <input type="search" class="form-control rounded-pill topbar-search" name="search" placeholder="ابحث عن رقم فاتورة" />
                            <i class="ti ti-search app-search-icon text-muted"></i>
                        </div>

                   
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <div id="theme-dropdown" class="topbar-item d-none d-sm-flex">
                            <div class="dropdown">
                                <button class="topbar-link" data-bs-toggle="dropdown" type="button" aria-haspopup="false" aria-expanded="false">
                                    <i class="ti ti-sun topbar-link-icon d-none" id="theme-icon-light"></i>
                                    <i class="ti ti-moon topbar-link-icon d-none" id="theme-icon-dark"></i>
                                    <i class="ti ti-sun-moon topbar-link-icon d-none" id="theme-icon-system"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" data-thememode="dropdown">
                                    <label class="dropdown-item cursor-pointer">
                                        <input class="form-check-input" type="radio" name="data-bs-theme" value="light" style="display: none" />
                                        <i class="ti ti-sun align-middle me-1 fs-16"></i>
                                        <span class="align-middle">Light</span>
                                    </label>
                                    <label class="dropdown-item cursor-pointer">
                                        <input class="form-check-input" type="radio" name="data-bs-theme" value="dark" style="display: none" />
                                        <i class="ti ti-moon align-middle me-1 fs-16"></i>
                                        <span class="align-middle">Dark</span>
                                    </label>
                                    <label class="dropdown-item cursor-pointer">
                                        <input class="form-check-input" type="radio" name="data-bs-theme" value="system" style="display: none" />
                                        <i class="ti ti-sun-moon align-middle me-1 fs-16"></i>
                                        <span class="align-middle">System</span>
                                    </label>
                                </div>
                                <!-- end dropdown-menu-->
                            </div>
                            <!-- end dropdown-->
                        </div>

                    


                        <div id="fullscreen-toggler" class="topbar-item d-none d-md-flex">
                            <button class="topbar-link" type="button" data-toggle="fullscreen">
                                <i class="ti ti-maximize topbar-link-icon"></i>
                                <i class="ti ti-minimize topbar-link-icon d-none"></i>
                            </button>
                        </div>

                        <div id="monochrome-toggler" class="topbar-item d-none d-xl-flex">
                            <button id="monochrome-mode" class="topbar-link" type="button" data-toggle="monochrome">
                                <i class="ti ti-palette topbar-link-icon"></i>
                            </button>
                        </div>

                        <div class="topbar-item d-none d-sm-flex">
                            <button class="topbar-link btn-theme-setting" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" type="button">
                                <i class="ti ti-settings topbar-link-icon"></i>
                            </button>
                        </div>

                        @if(config('desktop.mode'))
                        <div id="sync-status-indicator" class="topbar-item">
                            <div class="dropdown">
                                <button class="topbar-link position-relative" data-bs-toggle="dropdown" type="button">
                                    <i class="ti ti-cloud-upload topbar-link-icon" id="sync-icon"></i>
                                    <span id="sync-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning d-none">0</span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 280px;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fw-semibold">حالة المزامنة</span>
                                        <span id="network-status-text" class="badge bg-success">متصل</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="mb-2">
                                        <small class="text-muted">آخر مزامنة:</small>
                                        <div id="last-sync-time" class="fw-medium">-</div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">تغييرات معلقة:</small>
                                        <div id="pending-changes-count" class="fw-medium">0</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="sync-pull-btn" class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="ti ti-cloud-download me-1"></i>
                                            سحب
                                        </button>
                                        <button type="button" id="sync-push-btn" class="btn btn-outline-success btn-sm flex-fill">
                                            <i class="ti ti-cloud-upload me-1"></i>
                                            رفع
                                        </button>
                                    </div>
                                    <button type="button" id="manual-sync-btn" class="btn btn-primary btn-sm w-100 mt-2">
                                        <i class="ti ti-refresh me-1"></i>
                                        مزامنة كاملة
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div id="user-dropdown-detailed" class="topbar-item nav-user">
                            <div class="dropdown">
                                <a class="topbar-link dropdown-toggle drop-arrow-none px-2 d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#!" aria-haspopup="false" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <span class="avatar-title bg-primary text-white rounded-circle fs-14 fw-bold">
                                            {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'م' }}
                                        </span>
                                    </div>
                                    <span class="d-none d-lg-inline fw-semibold">{{ Auth::check() ? Auth::user()->name : 'مستخدم' }}</span>
                                    <i class="ti ti-chevron-down align-middle d-none d-lg-inline"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item fw-semibold">
                                            <i class="ti ti-logout me-1 fs-lg align-middle"></i>
                                            <span class="align-middle">تسجيل الخروج</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <!-- Topbar End -->
 <div class="sidenav-menu">
    <!-- Brand Logo -->
    <a href="{{asset('dashboard')}}" class="logo">
        <span class="logo logo-light">
            <span class="logo-lg"><img src="{{asset('logo-dark.png')}}" style="height:50px!important;"  alt="logo" /></span>
            <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo" /></span>
        </span>

        <span class="logo logo-dark">
            <span class="logo-lg"><img src="{{asset('logo-dark.png')}}" alt="dark logo" style="height: 50px!important;"  /></span>
            <span class="logo-sm"><img src="{{asset('assets/images/logo-sm.png')}}" alt="small logo" /></span>
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-on-hover">
        <i class="ti ti-circle align-middle"></i>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-offcanvas">
        <i class="ti ti-menu-4 align-middle"></i>
    </button>

    <div class="scrollbar" data-simplebar="">
        <div id="user-profile-settings" class="sidenav-user p-2">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm">
                    <span class="avatar-title bg-primary text-white rounded-circle fs-14 fw-bold">
                        {{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 2)) : 'م' }}
                    </span>
                </div>
                <div class="flex-grow-1">
                    <span class="sidenav-user-name fw-semibold fs-14">{{ Auth::check() ? Auth::user()->name : 'مستخدم' }}</span>
                </div>
            </div>
        </div>

        <!--- Sidenav Menu -->
        @include('layouts.sidebar')
    </div>
</div>
<!-- Sidenav Menu End -->


            <!-- ========================================== -->
            <!-- Start Main Content -->
            <!-- ========================================== -->

            <div class="content-page">
                <div class="container-fluid">
                    <div class="page-title-head d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="page-main-title m-0">@yield('title')</h4>
                        </div>

                        <div class="text-end">
                            <ol class="breadcrumb m-0 py-0">
                                {{-- <li class="breadcrumb-item"><a href="javascript: void(0);">Paces</a></li>
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                <li class="breadcrumb-item active">eCommerce</li> --}}

                                @yield('breadcrumb')
                            </ol>
                        </div>
                    </div>

                    @yield('content')

                </div>
                <!-- container -->

                <!-- Footer Start -->
                <footer class="footer">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6 text-center text-md-start">
                                ©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script>
                                <span class="fw-semibold">جميع الحقوق محفوظة</span>
                            </div>
                           
                        </div>
                    </div>
                </footer>
                <!-- end Footer -->

            </div>

            <!-- ========================================== -->
            <!-- End of Main Content -->
            <!-- ========================================== -->
        </div>
        <!-- END wrapper -->

        <div class="offcanvas offcanvas-end overflow-hidden" tabindex="-1" id="theme-settings-offcanvas">
            <div class="d-flex justify-content-between text-bg-primary gap-2 p-3" style="background-image: url({{ asset('assets/images/settings-bg.png') }})">
                <div>
                    <h5 class="mb-1 fw-bold text-white text-uppercase">عدل شكل الواجهة زي ماتحب</h5>
                </div>

                <div class="flex-grow-0">
                    <button type="button" class="d-block btn btn-sm bg-white bg-opacity-25 text-white rounded-circle btn-icon" data-bs-dismiss="offcanvas">
                        <i class="ti ti-x fs-lg"></i>
                    </button>
                </div>
            </div>

            <div class="offcanvas-body theme-customizer-bar p-0 h-100" data-simplebar="">
                <div id="skin" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Select Theme</h5>
                    <div class="row g-3">
                        <div class="col-6" id="skin-default">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-default" value="default" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-default">
                                    <img src="{{ asset('assets/images/layouts/skin-default.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Default</h5>
                        </div>

                        <div class="col-6" id="skin-minimal">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-minimal" value="minimal" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-minimal">
                                    <img src="{{ asset('assets/images/layouts/skin-minimal.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Minimal</h5>
                        </div>

                        <div class="col-6" id="skin-modern">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-modern" value="modern" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-modern">
                                    <img src="{{ asset('assets/images/layouts/skin-modern.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Modern</h5>
                        </div>

                        <div class="col-6" id="skin-material">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-material" value="material" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-material">
                                    <img src="{{ asset('assets/images/layouts/skin-material.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Material</h5>
                        </div>

                        <div class="col-6" id="skin-saas">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-saas" value="saas" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-saas">
                                    <img src="{{ asset('assets/images/layouts/skin-saas.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">SaaS</h5>
                        </div>

                        <div class="col-6" id="skin-flat">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-flat" value="flat" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-flat">
                                    <img src="{{ asset('assets/images/layouts/skin-flat.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Flat</h5>
                        </div>

                        <div class="col-6" id="skin-galaxy">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-galaxy" value="galaxy" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-galaxy">
                                    <img src="{{ asset('assets/images/layouts/skin-galaxy.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Galaxy</h5>
                        </div>

                        <div class="col-6" id="skin-luxe">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-luxe" value="luxe" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-luxe">
                                    <img src="{{ asset('assets/images/layouts/skin-luxe.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Luxe</h5>
                        </div>

                        <div class="col-6" id="skin-retro">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-retro" value="retro" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-retro">
                                    <img src="{{ asset('assets/images/layouts/skin-retro.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Retro</h5>
                        </div>

                        <div class="col-6" id="skin-neon">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-neon" value="neon" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-neon">
                                    <img src="{{ asset('assets/images/layouts/skin-neon.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Neon</h5>
                        </div>

                        <div class="col-6" id="skin-pixel">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-pixel" value="pixel" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-pixel">
                                    <img src="{{ asset('assets/images/layouts/skin-pixel.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Pixel</h5>
                        </div>

                        <div class="col-6" id="skin-soft">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-soft" value="soft" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-soft">
                                    <img src="{{ asset('assets/images/layouts/skin-soft.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Soft</h5>
                        </div>

                        <div class="col-6" id="skin-mono">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-mono" value="mono" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-mono">
                                    <img src="{{ asset('assets/images/layouts/skin-mono.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Mono</h5>
                        </div>

                        <div class="col-6" id="skin-prism">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-prism" value="prism" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-prism">
                                    <img src="{{ asset('assets/images/layouts/skin-prism.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Prism</h5>
                        </div>

                        <div class="col-6" id="skin-nova">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-nova" value="nova" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-nova">
                                    <img src="{{ asset('assets/images/layouts/skin-nova.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Nova</h5>
                        </div>

                        <div class="col-6" id="skin-zen">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-zen" value="zen" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-zen">
                                    <img src="{{ asset('assets/images/layouts/skin-zen.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Zen</h5>
                        </div>

                        <div class="col-6" id="skin-elegant">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-elegant" value="elegant" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-elegant">
                                    <img src="{{ asset('assets/images/layouts/skin-elegant.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Elegant</h5>
                        </div>

                        <div class="col-6" id="skin-vivid">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-vivid" value="vivid" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-vivid">
                                    <img src="{{ asset('assets/images/layouts/skin-vivid.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Vivid</h5>
                        </div>

                        <div class="col-6" id="skin-aurora">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-aurora" value="aurora" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-aurora">
                                    <img src="{{ asset('assets/images/layouts/skin-aurora.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Aurora</h5>
                        </div>

                        <div class="col-6" id="skin-crystal">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-crystal" value="crystal" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-crystal">
                                    <img src="{{ asset('assets/images/layouts/skin-crystal.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Crystal</h5>
                        </div>

                        <div class="col-6" id="skin-matrix">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-matrix" value="matrix" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-matrix">
                                    <img src="{{ asset('assets/images/layouts/skin-matrix.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Matrix</h5>
                        </div>

                        <div class="col-6" id="skin-orbit">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-orbit" value="orbit" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-orbit">
                                    <img src="{{ asset('assets/images/layouts/skin-orbit.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Orbit</h5>
                        </div>

                        <div class="col-6" id="skin-neo">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-neo" value="neo" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-neo">
                                    <img src="{{ asset('assets/images/layouts/skin-neo.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Neo</h5>
                        </div>

                        <div class="col-6" id="skin-silver">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-silver" value="silver" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-silver">
                                    <img src="{{ asset('assets/images/layouts/skin-silver.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Silver</h5>
                        </div>

                        <div class="col-6" id="skin-xenon">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-skin" id="demo-skin-xenon" value="xenon" />
                                <label class="form-check-label p-0 w-100" for="demo-skin-xenon">
                                    <img src="{{ asset('assets/images/layouts/skin-xenon.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Xenon</h5>
                        </div>
                    </div>
                </div>

                <div id="theme" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Color Scheme</h5>
                    <div class="row">
                        <div class="col-4" id="theme-light">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-light" value="light" />
                                <label class="form-check-label p-0 w-100" for="layout-color-light">
                                    <img src="{{ asset('assets/images/layouts/theme-light.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Light</h5>
                        </div>

                        <div class="col-4" id="theme-dark">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-dark" value="dark" />
                                <label class="form-check-label p-0 w-100" for="layout-color-dark">
                                    <img src="{{ asset('assets/images/layouts/theme-dark.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Dark</h5>
                        </div>

                        <div class="col-4" id="theme-system">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-bs-theme" id="layout-color-system" value="system" />
                                <label class="form-check-label p-0 w-100" for="layout-color-system">
                                    <img src="{{ asset('assets/images/layouts/theme-system.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">System</h5>
                        </div>
                    </div>
                </div>

                <div id="topbar-color" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Topbar Color</h5>

                    <div class="row g-3">
                        <div class="col-4" id="topbar-color-light">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar-color" id="layout-topbar-color-light" value="light" />
                                <label class="form-check-label p-0 w-100" for="layout-topbar-color-light">
                                    <img src="{{ asset('assets/images/layouts/topbar-color-light.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="text-center text-muted mt-2 mb-0">Light</h5>
                        </div>

                        <div class="col-4" id="topbar-color-dark">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar-color" id="layout-topbar-color-dark" value="dark" />
                                <label class="form-check-label p-0 w-100" for="layout-topbar-color-dark">
                                    <img src="{{ asset('assets/images/layouts/topbar-color-dark.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Dark</h5>
                        </div>

                        <div class="col-4" id="topbar-color-gray">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar-color" id="layout-topbar-color-gray" value="gray" />
                                <label class="form-check-label p-0 w-100" for="layout-topbar-color-gray">
                                    <img src="{{ asset('assets/images/layouts/topbar-color-gray.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Gray</h5>
                        </div>

                        <div class="col-4" id="topbar-color-gradient">
                            <div class="form-check card-radio">
                                <input class="form-check-input" type="radio" name="data-topbar-color" id="layout-topbar-color-gradient" value="gradient" />
                                <label class="form-check-label p-0 w-100" for="layout-topbar-color-gradient">
                                    <img src="{{ asset('assets/images/layouts/topbar-color-gradient.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Gradient</h5>
                        </div>
                    </div>
                </div>

                <div id="sidenav-color" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Sidenav Color</h5>

                    <div class="row g-3">
                        <div class="col-4" id="sidenav-color-light">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-menu-color" id="layout-sidenav-color-light" value="light" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-color-light">
                                    <img src="{{ asset('assets/images/layouts/sidenav-color-light.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Light</h5>
                        </div>

                        <div class="col-4" id="sidenav-color-dark">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-menu-color" id="layout-sidenav-color-dark" value="dark" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-color-dark">
                                    <img src="{{ asset('assets/images/layouts/sidenav-color-dark.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Dark</h5>
                        </div>

                        <div class="col-4" id="sidenav-color-gray">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-menu-color" id="layout-sidenav-color-gray" value="gray" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-color-gray">
                                    <img src="{{ asset('assets/images/layouts/sidenav-color-gray.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Gray</h5>
                        </div>

                        <div class="col-4" id="sidenav-color-gradient">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-menu-color" id="layout-sidenav-color-gradient" value="gradient" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-color-gradient">
                                    <img src="{{ asset('assets/images/layouts/sidenav-color-gradient.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Gradient</h5>
                        </div>
                        <div class="col-4" id="sidenav-color-image">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-menu-color" id="layout-sidenav-color-image" value="image" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-color-image">
                                    <img src="{{ asset('assets/images/layouts/sidenav-color-image.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="fs-sm text-center text-muted mt-2 mb-0">Image</h5>
                        </div>
                    </div>
                </div>

                <div id="sidenav-size" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Sidebar Size</h5>

                    <div class="row g-3">
                        <div class="col-4" id="sidenav-size-default">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-default" value="default" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-default">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-default.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Default</h5>
                        </div>

                        <div class="col-4" id="sidenav-size-compact">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-compact" value="compact" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-compact">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-compact.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Compact</h5>
                        </div>

                        <div class="col-4" id="sidenav-size-condensed">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-condensed" value="condensed" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-condensed">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-condensed.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Condensed</h5>
                        </div>

                        <div class="col-4" id="sidenav-size-on-hover">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-small-hover" value="on-hover" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-small-hover">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-on-hover.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">On Hover</h5>
                        </div>

                        <div class="col-4" id="sidenav-size-on-hover-active">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-small-hover-active" value="on-hover-active" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-small-hover-active">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-on-hover-active.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 fs-base text-center text-muted mt-2">On Hover - Show</h5>
                        </div>

                        <div class="col-4" id="sidenav-size-offcanvas">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-sidenav-size" id="layout-sidenav-size-offcanvas" value="offcanvas" />
                                <label class="form-check-label p-0 w-100" for="layout-sidenav-size-offcanvas">
                                    <img src="{{ asset('assets/images/layouts/sidenav-size-offcanvas.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Offcanvas</h5>
                        </div>
                    </div>
                </div>

                <div id="width" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Layout Width</h5>

                    <div class="row g-3">
                        <div class="col-4" id="width-fluid">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-layout-width" id="layout-width-fluid" value="fluid" />
                                <label class="form-check-label p-0 w-100" for="layout-width-fluid">
                                    <img src="{{ asset('assets/images/layouts/width-fluid.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Fluid</h5>
                        </div>

                        <div class="col-4" id="width-boxed">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="data-layout-width" id="layout-width-boxed" value="boxed" />
                                <label class="form-check-label p-0 w-100" for="layout-width-boxed">
                                    <img src="{{ asset('assets/images/layouts/width-boxed.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">Boxed</h5>
                        </div>
                    </div>
                </div>

                <div id="dir" class="p-3 border-bottom border-dashed">
                    <h5 class="mb-3 fw-bold">Layout Direction</h5>

                    <div class="row g-3">
                        <div class="col-4" id="dir-ltr">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="dir" id="layout-dir-ltr" value="ltr" />
                                <label class="form-check-label p-0 w-100" for="layout-dir-ltr">
                                    <img src="{{ asset('assets/images/layouts/dir-ltr.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">LTR</h5>
                        </div>

                        <div class="col-4" id="dir-rtl">
                            <div class="form-check sidebar-setting card-radio">
                                <input class="form-check-input" type="radio" name="dir" id="layout-dir-rtl" value="rtl" />
                                <label class="form-check-label p-0 w-100" for="layout-dir-rtl">
                                    <img src="{{ asset('assets/images/layouts/dir-rtl.png') }}" alt="layout-img" class="img-fluid" />
                                </label>
                            </div>
                            <h5 class="mb-0 text-center text-muted mt-2">RTL</h5>
                        </div>
                    </div>
                </div>

                <div id="position" class="p-3 border-bottom border-dashed">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Layout Position</h5>

                        <div class="d-flex gap-1">
                            <div id="position-fixed">
                                <input type="radio" class="btn-check" name="data-layout-position" id="layout-position-fixed" value="fixed" />
                                <label class="btn btn-sm btn-soft-warning w-sm" for="layout-position-fixed">Fixed</label>
                            </div>
                            <div id="position-scrollable">
                                <input type="radio" class="btn-check" name="data-layout-position" id="layout-position-scrollable" value="scrollable" />
                                <label class="btn btn-sm btn-soft-warning w-sm ms-0" for="layout-position-scrollable">Scrollable</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sidenav-user" class="p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <label class="fw-bold m-0" for="sidebaruser-check">Sidebar User Info</label>
                        </h5>
                        <div class="form-check form-switch fs-lg">
                            <input type="checkbox" class="form-check-input" name="sidebar-user" id="sidebaruser-check" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="offcanvas-footer border-top p-3 text-center">
                <div class="row justify-content-end">
                    <div class="col-6">
                        <button type="button" class="btn btn-danger fw-semibold py-2 w-100" id="reset-layout">Reset</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- end offcanvas-->
 <!-- Vendor js -->
<script src="{{ asset('assets/js/vendors.min.js') }}"></script>

<!-- App js -->
<script src="{{ asset('assets/js/app.js') }}"></script>
<script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>

        <!-- Apex Chart js -->
        <script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>

        <!-- Vector Map Js -->
        <script src="{{ asset('assets/plugins/jsvectormap/jsvectormap.min.js') }}"></script>
        <script src="{{ asset('assets/js/maps/world-merc.js') }}"></script>
        <script src="{{ asset('assets/js/maps/world.js') }}"></script>

        <!-- Custom table -->
        <script src="{{ asset('assets/js/pages/custom-table.js') }}"></script>

        <!-- Dashboard js -->
        <script src="{{ asset('assets/js/pages/dashboard-ecommerce.js') }}"></script>

        @stack('scripts')

        @if(config('desktop.mode'))
        <script>
        (function() {
            const syncIcon = document.getElementById('sync-icon');
            const syncBadge = document.getElementById('sync-badge');
            const networkStatusText = document.getElementById('network-status-text');
            const lastSyncTime = document.getElementById('last-sync-time');
            const pendingChangesCount = document.getElementById('pending-changes-count');
            const manualSyncBtn = document.getElementById('manual-sync-btn');
            const syncPullBtn = document.getElementById('sync-pull-btn');
            const syncPushBtn = document.getElementById('sync-push-btn');

            function setButtonLoading(btn, loading, originalHtml) {
                if (loading) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="ti ti-loader me-1" style="animation: spin 1s linear infinite;"></i>';
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }

            function showResult(success, message) {
                const alertClass = success ? 'alert-success' : 'alert-danger';
                const icon = success ? 'ti-check' : 'ti-x';
                const toast = document.createElement('div');
                toast.className = `alert ${alertClass} position-fixed bottom-0 end-0 m-3 d-flex align-items-center`;
                toast.style.zIndex = '9999';
                toast.innerHTML = `<i class="ti ${icon} me-2"></i>${message}`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }

            async function syncAction(action) {
                const response = await fetch(`/api/sync/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                return await response.json();
            }

            if (syncPullBtn) {
                syncPullBtn.addEventListener('click', async function() {
                    const originalHtml = this.innerHTML;
                    setButtonLoading(this, true);
                    try {
                        const result = await syncAction('pull');
                        showResult(result.success, result.success ? `تم سحب ${result.pulled || 0} سجل` : result.message);
                        if (result.success) lastSyncTime.textContent = new Date().toLocaleString('ar-SA');
                    } catch (e) {
                        showResult(false, 'حدث خطأ في السحب');
                    }
                    setButtonLoading(this, false, originalHtml);
                });
            }

            if (syncPushBtn) {
                syncPushBtn.addEventListener('click', async function() {
                    const originalHtml = this.innerHTML;
                    setButtonLoading(this, true);
                    try {
                        const result = await syncAction('push');
                        showResult(result.success, result.success ? `تم رفع ${result.pushed || 0} سجل` : result.message);
                        if (result.success) lastSyncTime.textContent = new Date().toLocaleString('ar-SA');
                    } catch (e) {
                        showResult(false, 'حدث خطأ في الرفع');
                    }
                    setButtonLoading(this, false, originalHtml);
                });
            }

            if (manualSyncBtn) {
                manualSyncBtn.addEventListener('click', async function() {
                    const originalHtml = this.innerHTML;
                    setButtonLoading(this, true);
                    try {
                        const pullResult = await syncAction('pull');
                        const pushResult = await syncAction('push');
                        const success = pullResult.success && pushResult.success;
                        showResult(success, success ? 'تمت المزامنة بنجاح' : 'حدث خطأ في المزامنة');
                        if (success) lastSyncTime.textContent = new Date().toLocaleString('ar-SA');
                    } catch (e) {
                        showResult(false, 'حدث خطأ في المزامنة');
                    }
                    setButtonLoading(this, false, originalHtml);
                });
            }

            let isOnline = false;
            let isSyncing = false;

            async function checkServerConnection() {
                try {
                    const response = await fetch('/api/sync/check-connection', {
                        method: 'GET',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        signal: AbortSignal.timeout(10000)
                    });
                    const result = await response.json();
                    isOnline = result.online === true;
                } catch (e) {
                    isOnline = false;
                }

                if (networkStatusText) {
                    networkStatusText.textContent = isOnline ? 'متصل' : 'غير متصل';
                    networkStatusText.className = isOnline ? 'text-success' : 'text-danger';
                }
                if (syncIcon) {
                    syncIcon.className = isOnline ? 'ti ti-cloud-check text-success' : 'ti ti-cloud-off text-danger';
                }

                return isOnline;
            }

            async function autoSync() {
                if (isSyncing || !isOnline) return;

                isSyncing = true;
                try {
                    const pushResult = await syncAction('push');
                    if (!pushResult.success) {
                        console.error('Sync push failed:', pushResult.message);
                        showResult(false, 'فشل رفع البيانات: ' + (pushResult.message || '').substring(0, 100));
                    } else if (pushResult.errors && pushResult.errors.length > 0) {
                        console.error('Sync push errors:', pushResult.errors);
                        showResult(false, `فشل مزامنة ${pushResult.errors.length} سجل: ${pushResult.errors[0].message}`);
                    }
                    const pullResult = await syncAction('pull');
                    if (!pullResult.success) {
                        console.error('Sync pull failed:', pullResult.message);
                    }
                    await updateStatus();
                } catch (e) {
                    console.error('Auto sync error:', e);
                }
                isSyncing = false;
            }

            async function updateStatus() {
                try {
                    const response = await fetch('/api/sync/status');
                    const status = await response.json();
                    if (status.pending_count > 0) {
                        syncBadge.textContent = status.pending_count;
                        syncBadge.classList.remove('d-none');
                    } else {
                        syncBadge.classList.add('d-none');
                    }
                    pendingChangesCount.textContent = status.pending_count || 0;
                    if (status.last_sync) {
                        lastSyncTime.textContent = new Date(status.last_sync).toLocaleString('ar-SA');
                    }
                } catch (e) {}
            }

            async function syncLoop() {
                await checkServerConnection();
                if (isOnline) {
                    await autoSync();
                }
            }

            updateStatus();
            setTimeout(syncLoop, 3000);
            setInterval(syncLoop, 300000);
            setInterval(updateStatus, 300000);
        })();
        </script>
        @endif

        <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>
    </body>
</html>
