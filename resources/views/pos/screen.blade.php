<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>نقطة البيع | المنار ماركت</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/logo-sm.png') }}">
    <link href="{{ asset('assets/fonts/fonts.css') }}" rel="stylesheet">
    <script src="{{ asset('assets/js/config.js') }}"></script>
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet">
    <style>
        * { font-family: 'IBM Plex Sans Arabic', sans-serif !important; }
        :root {
            --pos-bg: #f5f7fa;
            --pos-card: #ffffff;
            --pos-border: #e0e4e8;
            --pos-header: #ffffff;
            --pos-text: #333;
            --pos-muted: #6c757d;
            --pos-success: #28a745;
            --pos-danger: #dc3545;
            --pos-primary: #0d6efd;
            --pos-warning: #ffc107;
        }
        [data-bs-theme="dark"] {
            --pos-bg: #1a1d21;
            --pos-card: #212529;
            --pos-border: #373b3e;
            --pos-header: #212529;
            --pos-text: #e9ecef;
            --pos-muted: #adb5bd;
        }
        html, body { height: 100%; margin: 0; padding: 0; overflow: hidden; background: var(--pos-bg); }
        .pos-container { display: flex; flex-direction: column; height: 100vh; }
        .pos-header {
            background: var(--pos-header);
            border-bottom: 1px solid var(--pos-border);
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-shrink: 0;
        }
        .pos-header .logo img { height: 40px; }
        .pos-header .user-info { display: flex; align-items: center; gap: 0.5rem; }
        .pos-main { flex: 1; display: flex; overflow: hidden; padding: 0.5rem; gap: 0.5rem; }
        .pos-cart {
            flex: 0 0 45%;
            display: flex;
            flex-direction: column;
            background: var(--pos-card);
            border-radius: 8px;
            border: 1px solid var(--pos-border);
            overflow: hidden;
        }
        .pos-products {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--pos-card);
            border-radius: 8px;
            border: 1px solid var(--pos-border);
            overflow: hidden;
        }
        .cart-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid var(--pos-border);
            gap: 0.75rem;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item .item-info { flex: 1; }
        .cart-item .item-name { font-weight: 600; margin-bottom: 0.25rem; }
        .cart-item .item-meta { font-size: 0.85rem; color: var(--pos-muted); }
        .cart-item .unit-select {
            padding: 0.15rem 0.4rem;
            font-size: 0.75rem;
            border: 1px solid var(--pos-border);
            border-radius: 4px;
            background: var(--pos-bg);
            color: var(--pos-text);
            cursor: pointer;
        }
        .cart-item .unit-select:hover { border-color: var(--pos-primary); }
        .cart-item .unit-select:focus { outline: none; border-color: var(--pos-primary); box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25); }
        .cart-item .item-qty {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .cart-item .item-qty input {
            width: 60px;
            text-align: center;
            border: 1px solid var(--pos-border);
            border-radius: 4px;
            padding: 0.25rem;
        }
        .cart-item .item-total { font-weight: 700; min-width: 80px; text-align: left; }
        .cart-item .item-remove { color: var(--pos-danger); cursor: pointer; padding: 0.25rem; }
        .cart-summary {
            border-top: 2px solid var(--pos-border);
            padding: 1rem;
            background: var(--pos-bg);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.35rem 0;
        }
        .summary-row.total {
            font-size: 1.25rem;
            font-weight: 700;
            border-top: 1px solid var(--pos-border);
            padding-top: 0.75rem;
            margin-top: 0.5rem;
        }
        .cart-actions {
            padding: 0.75rem 1rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            border-top: 1px solid var(--pos-border);
        }
        .cart-actions .btn { padding: 0.75rem 0.5rem; font-weight: 600; }
        .products-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--pos-border);
        }
        .products-header .search-box {
            display: flex;
            gap: 0.5rem;
        }
        .products-header .search-box input {
            flex: 1;
            border: 1px solid var(--pos-border);
            border-radius: 4px;
            padding: 0.5rem 1rem;
        }
        .products-list {
            flex: 1;
            overflow-y: auto;
            padding: 0.5rem;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border: 1px solid var(--pos-border);
            border-radius: 6px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.15s;
            gap: 0.75rem;
        }
        .product-item:hover { background: var(--pos-bg); border-color: var(--pos-primary); }
        .product-item .product-info { flex: 1; }
        .product-item .product-name { font-weight: 600; }
        .product-item .product-barcode { font-size: 0.8rem; color: var(--pos-muted); }
        .product-item .product-price { font-weight: 700; color: var(--pos-success); }
        .product-item .product-stock { font-size: 0.85rem; color: var(--pos-muted); }
        .customer-section {
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .customer-section .customer-input { flex: 1; }
        .customer-section .customer-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: var(--pos-bg);
            border-radius: 4px;
            flex: 1;
        }
        .toast-container { position: fixed; top: 20px; left: 20px; z-index: 9999; }
        .empty-cart {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--pos-muted);
        }
        .empty-cart i { font-size: 4rem; margin-bottom: 1rem; }
        .suspended-badge {
            position: absolute;
            top: -5px;
            left: -5px;
            background: var(--pos-danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-action {
            min-height: 44px;
            min-width: 44px;
        }
        .header-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            min-width: 70px;
            font-size: 0.75rem;
            gap: 0.25rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .header-btn i {
            font-size: 1.5rem;
        }
        .header-btn span {
            font-size: 0.7rem;
            font-weight: 500;
        }
        .header-btn:hover {
            transform: translateY(-2px);
        }
        .header-divider {
            width: 1px;
            height: 40px;
            background: var(--pos-border);
            margin: 0 0.5rem;
        }
        .numpad-container {
            display: none;
            padding: 0.5rem;
            background: var(--pos-bg);
            border-top: 1px solid var(--pos-border);
        }
        .numpad-container.show { display: block; }
        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }
        .numpad .btn {
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .keyboard-hint {
            font-size: 0.7rem;
            color: var(--pos-muted);
            margin-top: 0.25rem;
        }
        @media (max-width: 992px) {
            .pos-main { flex-direction: column; }
            .pos-cart { flex: 0 0 50%; }
            .pos-products { flex: 1; }
            .keyboard-hint { display: none; }
            .numpad-container.show { display: block; }
        }
        @media (min-width: 993px) {
            .numpad-container { display: none !important; }
        }
    </style>
</head>
<body dir="rtl">
    <div class="pos-container">
        <header class="pos-header">
            <div class="d-flex align-items-center gap-3">
                @if($isManager)
                <a href="{{ route('dashboard') }}" class="logo">
                    <img src="{{ asset('logo-dark.png') }}" alt="logo">
                </a>
                @else
                <span class="logo">
                    <img src="{{ asset('logo-dark.png') }}" alt="logo">
                </span>
                @endif
                <h5 class="mb-0 d-none d-md-block">نقطة البيع</h5>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-outline-secondary header-btn position-relative" id="showSuspendedBtn" title="الفواتير المعلقة (F8)">
                    <i class="ti ti-clock-pause"></i>
                    <span>معلقة</span>
                    @if($suspendedCount > 0)
                    <span class="suspended-badge">{{ $suspendedCount }}</span>
                    @endif
                </button>

                <button type="button" class="btn btn-outline-warning header-btn" id="returnBtn" title="استرجاع فاتورة (F7)">
                    <i class="ti ti-receipt-refund"></i>
                    <span>استرجاع</span>
                </button>

                @if($isManager)
                <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary header-btn" title="سجل المبيعات">
                    <i class="ti ti-receipt"></i>
                    <span>المبيعات</span>
                </a>

                <a href="{{ route('reports.daily-report') }}" class="btn btn-outline-info header-btn" title="التقرير اليومي">
                    <i class="ti ti-report"></i>
                    <span>التقرير</span>
                </a>
                @endif

                <div class="header-divider"></div>

                <button type="button" class="btn btn-outline-secondary header-btn" id="themeToggle" title="تغيير السمة">
                    <i class="ti ti-sun" id="themeIcon"></i>
                    <span>السمة</span>
                </button>

                <button type="button" class="btn btn-outline-{{ $hasOpenShift ? 'success' : 'danger' }} header-btn" id="shiftStatusBtn" onclick="showShiftInfo()" title="معلومات الوردية">
                    <i class="ti ti-clock-{{ $hasOpenShift ? 'check' : 'off' }}"></i>
                    <span>الوردية</span>
                </button>

                <button type="button" class="btn btn-danger header-btn" id="endShiftBtn" title="إنهاء الجلسة وإغلاق الوردية">
                    <i class="ti ti-power"></i>
                    <span>إنهاء</span>
                </button>

                <button type="button" class="btn btn-outline-danger header-btn" id="logoutBtn" title="تسجيل الخروج">
                    <i class="ti ti-logout"></i>
                    <span>خروج</span>
                </button>

                <div class="header-divider d-none d-md-block"></div>

                <div class="d-none d-md-flex align-items-center gap-2 px-2">
                    <div class="text-end" style="line-height: 1.2;">
                        <div class="fw-bold" style="font-size: 0.85rem;">{{ Auth::user()->name }}</div>
                        <small class="text-{{ $isManager ? 'primary' : 'success' }}">{{ $isManager ? 'مدير' : 'كاشير' }}</small>
                    </div>
                    <div class="rounded-circle bg-{{ $isManager ? 'primary' : 'success' }} text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="ti ti-user" style="font-size: 1.25rem;"></i>
                    </div>
                </div>

                @if($isManager)
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary header-btn d-none d-md-flex" title="لوحة التحكم">
                    <i class="ti ti-layout-dashboard"></i>
                    <span>التحكم</span>
                </a>
                @endif
            </div>
        </header>

        <div class="pos-main">
            <div class="pos-cart">
                <div class="customer-section">
                    <i class="ti ti-user"></i>
                    <div class="customer-input" id="customerInputContainer">
                        <input type="text" class="form-control form-control-sm" id="customerSearch" placeholder="بحث عن زبون..." autocomplete="off">
                    </div>
                    <div class="customer-info d-none" id="customerInfoContainer">
                        <span id="customerName"></span>
                        <span class="badge bg-info" id="customerBalance"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearCustomer">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>

                <div class="cart-header">
                    <h6 class="mb-0"><i class="ti ti-shopping-cart me-1"></i>السلة <small class="text-muted fw-normal" style="font-size: 0.65rem;">(↑↓±1 ←→±5 F6وحدة)</small></h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="clearCartBtn" title="تفريغ السلة">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>

                <div class="cart-items" id="cartItems">
                    <div class="empty-cart" id="emptyCart">
                        <i class="ti ti-shopping-cart-off"></i>
                        <p>السلة فارغة</p>
                        <small>امسح باركود أو ابحث عن منتج</small>
                    </div>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>المجموع الفرعي</span>
                        <span id="subtotal">0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>الإجمالي</span>
                        <span id="total">0.00</span>
                    </div>
                </div>

                <div class="cart-actions" style="grid-template-columns: repeat(4, 1fr);">
                    <button type="button" class="btn btn-success btn-action" id="quickCashBtn" title="دفع فوري (Space)">
                        <i class="ti ti-bolt d-block d-md-none"></i>
                        <span class="d-none d-md-inline"><i class="ti ti-bolt me-1"></i>فوري</span>
                        <div class="keyboard-hint">Space</div>
                    </button>
                    <button type="button" class="btn btn-primary btn-action" id="servicesBtn" title="طرق الدفع (F2)">
                        <i class="ti ti-credit-card d-block d-md-none"></i>
                        <span class="d-none d-md-inline"><i class="ti ti-credit-card me-1"></i>دفع</span>
                        <div class="keyboard-hint">F2</div>
                    </button>
                    <button type="button" class="btn btn-info btn-action" id="payMultiBtn" title="دفع متعدد (F3)">
                        <i class="ti ti-wallet d-block d-md-none"></i>
                        <span class="d-none d-md-inline"><i class="ti ti-wallet me-1"></i>متعدد</span>
                        <div class="keyboard-hint">F3</div>
                    </button>
                    <button type="button" class="btn btn-warning btn-action" id="suspendBtn" title="تعليق الفاتورة (F9)">
                        <i class="ti ti-clock-pause d-block d-md-none"></i>
                        <span class="d-none d-md-inline"><i class="ti ti-clock-pause me-1"></i>تعليق</span>
                        <div class="keyboard-hint">F9</div>
                    </button>
                </div>
            </div>

            <div class="pos-products">
                <div class="products-header">
                    <div class="search-box">
                        <input type="text" class="form-control" id="barcodeInput" placeholder="امسح الباركود أو ابحث..." autofocus autocomplete="off">
                        <button type="button" class="btn btn-primary btn-action" id="searchBtn">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>

                <div class="products-list" id="productsList">
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-barcode fs-1 d-block mb-2"></i>
                        <p>امسح الباركود أو ابحث عن منتج</p>
                    </div>
                </div>

                <div class="numpad-container" id="numpadContainer">
                    <div class="numpad">
                        <button type="button" class="btn btn-outline-secondary" data-num="1">1</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="2">2</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="3">3</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="4">4</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="5">5</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="6">6</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="7">7</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="8">8</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="9">9</button>
                        <button type="button" class="btn btn-outline-secondary" data-num=".">.</button>
                        <button type="button" class="btn btn-outline-secondary" data-num="0">0</button>
                        <button type="button" class="btn btn-danger" data-num="clear"><i class="ti ti-backspace"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-cash me-2"></i>إتمام الدفع</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">المطلوب دفعه</label>
                                <div class="fs-2 fw-bold text-primary" id="modalTotal">0.00</div>
                            </div>

                            <div id="paymentMethodsContainer">
                                <div class="payment-entry mb-3" data-index="0">
                                    <label class="form-label">طريقة الدفع</label>
                                    <select class="form-select payment-method-select mb-2">
                                        @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" data-requires-cashbox="{{ $method->requires_cashbox ? '1' : '0' }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="cashbox-select-container mb-2">
                                        <label class="form-label">الخزينة</label>
                                        <select class="form-select cashbox-select">
                                            @foreach($cashboxes as $cashbox)
                                            <option value="{{ $cashbox->id }}">{{ $cashbox->name }} ({{ number_format($cashbox->current_balance, 2) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <label class="form-label">المبلغ</label>
                                    <input type="number" class="form-control payment-amount" step="0.01" min="0">
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm mb-3 d-none" id="addPaymentMethodBtn">
                                <i class="ti ti-plus me-1"></i>إضافة طريقة دفع
                            </button>

                            <div class="mb-3">
                                <label class="form-label">ملاحظات</label>
                                <textarea class="form-control" id="paymentNotes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-3">ملخص الفاتورة</h6>
                                <div class="d-flex justify-content-between mb-2 fw-bold">
                                    <span>الإجمالي:</span>
                                    <span id="modalTotalSummary">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>المدفوع:</span>
                                    <span class="text-success" id="modalPaid">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>المتبقي:</span>
                                    <span class="text-danger" id="modalRemaining">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 d-none" id="changeContainer">
                                    <span>الباقي للزبون:</span>
                                    <span class="text-info fw-bold" id="modalChange">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-success" id="confirmPaymentBtn">
                        <i class="ti ti-check me-1"></i>تأكيد الدفع
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="suspendedModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ti ti-clock-pause me-1"></i>الفواتير المعلقة</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush" id="suspendedList">
                        <div class="text-center py-4 text-muted">جاري التحميل...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ti ti-receipt me-1"></i>تم البيع بنجاح</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <i class="ti ti-circle-check text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-2">رقم الفاتورة</h5>
                    <div class="fs-4 fw-bold text-primary mb-3" id="invoiceNumber"></div>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-outline-primary" id="printReceiptBtn">
                            <i class="ti ti-printer me-1"></i>طباعة
                        </button>
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal" id="newSaleBtn">
                            <i class="ti ti-plus me-1"></i>فاتورة جديدة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerSearchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ti ti-user-search me-1"></i>بحث عن زبون</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="customerSearchSection">
                        <div class="mb-3">
                            <input type="text" class="form-control" id="customerSearchInput" placeholder="اسم أو رقم هاتف الزبون..." autofocus>
                        </div>
                        <div class="list-group" id="customerResults"></div>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-outline-success btn-sm" id="showAddCustomerBtn">
                                <i class="ti ti-user-plus me-1"></i>إضافة زبون جديد
                            </button>
                        </div>
                    </div>
                    <div id="addCustomerSection" class="d-none">
                        <div class="d-flex align-items-center mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="backToSearchCustomerBtn">
                                <i class="ti ti-arrow-right"></i>
                            </button>
                            <h6 class="mb-0"><i class="ti ti-user-plus me-1"></i>إضافة زبون جديد</h6>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label">الاسم <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newCustomerName" placeholder="اسم الزبون">
                            </div>
                            <div class="col-12">
                                <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newCustomerPhone" placeholder="رقم الهاتف">
                            </div>
                            <div class="col-6">
                                <label class="form-label">حد الائتمان</label>
                                <input type="number" class="form-control" id="newCustomerCreditLimit" value="0" min="0" step="0.01">
                            </div>
                            <div class="col-6">
                                <label class="form-label">السماح بالآجل</label>
                                <select class="form-select" id="newCustomerAllowCredit">
                                    <option value="0">لا</option>
                                    <option value="1">نعم</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-success w-100" id="saveNewCustomerBtn">
                                    <i class="ti ti-check me-1"></i>حفظ واختيار الزبون
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="returnModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h6 class="modal-title"><i class="ti ti-receipt-refund me-1"></i>استرجاع فاتورة</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="returnSearchSection">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="returnInvoiceSearch" placeholder="بحث برقم الفاتورة أو اسم الزبون..." autofocus>
                            <button type="button" class="btn btn-primary" id="searchInvoiceBtn">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                        <div id="invoiceSearchResults" class="list-group"></div>
                    </div>

                    <div id="returnDetailsSection" class="d-none">
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="ti ti-file-invoice me-1"></i>بيانات الفاتورة</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="backToSearchBtn">
                                    <i class="ti ti-arrow-right me-1"></i>رجوع
                                </button>
                            </div>
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-6 col-md-3 mb-2">
                                        <small class="text-muted d-block">رقم الفاتورة</small>
                                        <strong id="returnInvoiceNumber"></strong>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <small class="text-muted d-block">التاريخ</small>
                                        <span id="returnInvoiceDate"></span>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <small class="text-muted d-block">الزبون</small>
                                        <span id="returnCustomerName"></span>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <small class="text-muted d-block">الإجمالي</small>
                                        <strong id="returnInvoiceTotal" class="text-primary"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mb-3" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" id="selectAllItems"></th>
                                        <th>المنتج</th>
                                        <th style="width: 80px;">الكمية</th>
                                        <th style="width: 100px;">كمية الإرجاع</th>
                                        <th style="width: 80px;">السعر</th>
                                        <th style="width: 90px;">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItemsBody"></tbody>
                            </table>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">سبب الاسترجاع <span class="text-danger">*</span></label>
                                <select class="form-select" id="returnReason" required>
                                    <option value="">اختر السبب</option>
                                    <option value="damaged">تالف</option>
                                    <option value="wrong_invoice">خطأ في الفاتورة</option>
                                    <option value="unsatisfied">زبون غير راضٍ</option>
                                    <option value="expired">منتج منتهي</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">طريقة الاسترداد <span class="text-danger">*</span></label>
                                <select class="form-select" id="refundMethod" required>
                                    <option value="">اختر الطريقة</option>
                                    <option value="cash">رد نقدي للخزينة</option>
                                    <option value="store_credit">رصيد للزبون</option>
                                    <option value="deduct_credit">خصم من حساب الزبون</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="cashboxSelectGroup">
                                <label class="form-label">الخزينة <span class="text-danger">*</span></label>
                                <select class="form-select" id="returnCashbox">
                                    @foreach($cashboxes as $cb)
                                    <option value="{{ $cb->id }}">{{ $cb->name }} ({{ number_format($cb->current_balance, 2) }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">إعادة المخزون</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="restoreStock" checked>
                                    <label class="form-check-label" for="restoreStock">إرجاع الكمية للمخزون</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">ملاحظات إضافية</label>
                                <textarea class="form-control" id="returnNotes" rows="2" placeholder="ملاحظات اختيارية..."></textarea>
                            </div>
                        </div>

                        <div class="card bg-light mt-3">
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fs-5">إجمالي الاسترداد:</span>
                                    <span class="fs-4 fw-bold text-danger" id="returnTotalAmount">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="returnModalFooter">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-warning d-none" id="processReturnBtn">
                        <i class="ti ti-check me-1"></i>تأكيد الاسترداد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="servicesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ti ti-credit-card me-1"></i>طرق الدفع</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        @foreach($paymentMethods as $method)
                        <div class="col-6">
                            <button type="button" class="btn btn-outline-primary w-100 py-3 payment-method-btn" data-method-id="{{ $method->id }}" data-requires-cashbox="{{ $method->requires_cashbox ? '1' : '0' }}">
                                <i class="ti ti-credit-card fs-2 d-block mb-2"></i>
                                {{ $method->name }}
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="closeShiftModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h6 class="modal-title"><i class="ti ti-clock-pause me-1"></i>إغلاق الوردية</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="shiftSummaryContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-2">جاري تحميل الملخص...</p>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3"><i class="ti ti-cash me-1"></i>إدخال أرصدة الإغلاق للصناديق</h6>
                    <div id="closingCashboxesContainer"></div>
                    <div class="mt-3 p-3 rounded" style="background: var(--bs-tertiary-bg);">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="small text-muted">إجمالي المتوقع</div>
                                <div class="fs-5 fw-bold" id="totalExpectedBalance">0.00</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">إجمالي الفعلي</div>
                                <div class="fs-5 fw-bold" id="totalActualBalance">0.00</div>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">إجمالي الفرق</div>
                                <div class="fs-5 fw-bold" id="totalDifferenceValue">0.00</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="shiftCloseNotes" rows="2" placeholder="ملاحظات اختيارية..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" id="confirmCloseShift">
                        <i class="ti ti-check me-1"></i>إغلاق الوردية
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shiftInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title"><i class="ti ti-info-circle me-1"></i>معلومات الوردية</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="shiftInfoContent">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-danger" id="openCloseShiftModal">
                        <i class="ti ti-clock-pause me-1"></i>إغلاق الوردية
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    <script src="{{ asset('assets/js/keyboard-manager.js') }}"></script>
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let cart = [];
        let selectedCustomer = null;
        let lastSaleId = null;
        let paymentMode = 'cash';

        let hasOpenShift = {{ $hasOpenShift ? 'true' : 'false' }};
        let currentShiftId = {{ $currentShift?->id ?? 'null' }};
        let currentShiftCashboxes = [];
        let shiftCashboxData = [];

        const closeShiftModal = new bootstrap.Modal(document.getElementById('closeShiftModal'));
        const shiftInfoModal = new bootstrap.Modal(document.getElementById('shiftInfoModal'));

        if (hasOpenShift) {
            showToast('تم فتح الوردية تلقائياً', 'success');
        }

        @if(session('error'))
        Swal.fire({
            title: 'تنبيه',
            text: '{{ session("error") }}',
            icon: 'warning',
            confirmButtonText: 'حسناً'
        });
        @endif

        document.getElementById('logoutBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'تسجيل الخروج',
                text: 'هل تريد تسجيل الخروج؟',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'نعم، خروج',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    performLogout();
                }
            });
        });

        document.getElementById('endShiftBtn').addEventListener('click', function() {
            if (!hasOpenShift || !currentShiftId) {
                showToast('لا يوجد وردية مفتوحة', 'warning');
                return;
            }

            Swal.fire({
                title: 'إنهاء الجلسة',
                html: '<p>هل تريد إغلاق الوردية وإنهاء الجلسة؟</p><small class="text-muted">سيتم تسجيل خروجك تلقائياً بعد إغلاق الوردية</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'نعم، إنهاء الجلسة',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    loadShiftSummary();
                    closeShiftModal.show();
                }
            });
        });

        async function loadShiftSummary() {
            if (!currentShiftId) return;

            try {
                const response = await fetch(`{{ url('shifts') }}/${currentShiftId}/summary`);
                const result = await response.json();

                if (result.success) {
                    const s = result.summary;
                    shiftCashboxData = s.cashboxes || [];

                    const totalOpeningBalance = parseFloat(s.total_opening_balance) || 0;
                    const totalCashSales = parseFloat(s.total_cash_sales) || 0;
                    const totalCardSales = parseFloat(s.total_card_sales) || 0;
                    const totalOtherSales = parseFloat(s.total_other_sales) || 0;
                    const totalRefunds = parseFloat(s.total_refunds) || 0;
                    const totalExpectedBalance = parseFloat(s.total_expected_balance) || 0;

                    document.getElementById('shiftSummaryContent').innerHTML = `
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 rounded text-center" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">إجمالي الرصيد الافتتاحي</div>
                                    <div class="fs-4 fw-bold">${totalOpeningBalance.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 rounded text-center" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">المبيعات النقدية</div>
                                    <div class="fs-4 fw-bold text-success">${totalCashSales.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded text-center" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">مبيعات البطاقة</div>
                                    <div class="fs-5 fw-bold">${totalCardSales.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded text-center" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">طرق أخرى</div>
                                    <div class="fs-5 fw-bold">${totalOtherSales.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded text-center" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">المرتجعات</div>
                                    <div class="fs-5 fw-bold text-danger">${totalRefunds.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>عدد الفواتير: ${s.sales_count}</span>
                                    <span>وقت الفتح: ${s.opened_at}</span>
                                </div>
                            </div>
                        </div>
                    `;

                    renderClosingCashboxes();
                    updateTotalDifference();
                }
            } catch (error) {
                console.error('Load shift summary error:', error);
                document.getElementById('shiftSummaryContent').innerHTML = `
                    <div class="alert alert-danger">حدث خطأ في تحميل الملخص</div>
                `;
            }
        }

        function renderClosingCashboxes() {
            const container = document.getElementById('closingCashboxesContainer');
            let html = '';

            shiftCashboxData.forEach((cb, index) => {
                const expected = parseFloat(cb.expected_balance) || 0;
                html += `
                    <div class="closing-cashbox-row mb-3 p-3 rounded" style="background: var(--bs-tertiary-bg);" data-cashbox-id="${cb.id}">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="fw-bold">${cb.name}</div>
                                <div class="small text-muted">${cb.type || ''}</div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="small text-muted">الافتتاحي</div>
                                <div class="fw-bold">${parseFloat(cb.opening_balance).toFixed(2)}</div>
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="small text-muted">المتوقع</div>
                                <div class="fw-bold text-primary expected-balance">${expected.toFixed(2)}</div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">الفعلي</label>
                                <input type="number" class="form-control form-control-sm text-center closing-balance-input"
                                    data-cashbox-id="${cb.id}" data-expected="${expected}" value="${expected.toFixed(2)}" min="0" step="0.01">
                            </div>
                            <div class="col-md-2 text-center">
                                <div class="small text-muted">الفرق</div>
                                <div class="fw-bold cashbox-difference" data-cashbox-id="${cb.id}">0.00</div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;

            container.querySelectorAll('.closing-balance-input').forEach(input => {
                input.addEventListener('input', function() {
                    updateCashboxDifference(this);
                    updateTotalDifference();
                });
            });
        }

        function updateCashboxDifference(input) {
            const cashboxId = input.dataset.cashboxId;
            const expected = parseFloat(input.dataset.expected) || 0;
            const actual = parseFloat(input.value) || 0;
            const difference = actual - expected;

            const diffElement = document.querySelector(`.cashbox-difference[data-cashbox-id="${cashboxId}"]`);
            if (diffElement) {
                diffElement.textContent = difference.toFixed(2);
                if (difference == 0) {
                    diffElement.className = 'fw-bold cashbox-difference text-success';
                } else if (difference > 0) {
                    diffElement.className = 'fw-bold cashbox-difference text-info';
                } else {
                    diffElement.className = 'fw-bold cashbox-difference text-danger';
                }
            }
        }

        function updateTotalDifference() {
            let totalExpected = 0;
            let totalActual = 0;

            document.querySelectorAll('.closing-balance-input').forEach(input => {
                totalExpected += parseFloat(input.dataset.expected) || 0;
                totalActual += parseFloat(input.value) || 0;
            });

            const totalDiff = totalActual - totalExpected;

            document.getElementById('totalExpectedBalance').textContent = totalExpected.toFixed(2);
            document.getElementById('totalActualBalance').textContent = totalActual.toFixed(2);

            const diffEl = document.getElementById('totalDifferenceValue');
            diffEl.textContent = totalDiff.toFixed(2);

            if (totalDiff == 0) {
                diffEl.className = 'fs-5 fw-bold text-success';
            } else if (totalDiff > 0) {
                diffEl.className = 'fs-5 fw-bold text-info';
            } else {
                diffEl.className = 'fs-5 fw-bold text-danger';
            }
        }

        document.getElementById('openCloseShiftModal').addEventListener('click', function() {
            shiftInfoModal.hide();
            loadShiftSummary();
            closeShiftModal.show();
        });

        document.getElementById('confirmCloseShift').addEventListener('click', async function() {
            const cashboxes = [];
            document.querySelectorAll('.closing-balance-input').forEach(input => {
                cashboxes.push({
                    cashbox_id: parseInt(input.dataset.cashboxId),
                    closing_balance: parseFloat(input.value) || 0
                });
            });

            if (cashboxes.length == 0) {
                showToast('لا توجد صناديق للإغلاق', 'warning');
                return;
            }

            const notes = document.getElementById('shiftCloseNotes').value;

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإغلاق...';

            try {
                const response = await fetch(`{{ url('shifts') }}/${currentShiftId}/close`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ cashboxes, notes })
                });

                const result = await response.json();

                if (result.success) {
                    closeShiftModal.hide();

                    const totalDifference = parseFloat(result.shift.total_difference) || 0;
                    if (result.shift.requires_approval) {
                        let cashboxesHtml = result.shift.cashboxes.map(cb => `
                            <div class="d-flex justify-content-between mb-1">
                                <span>${cb.name}:</span>
                                <span class="${cb.difference >= 0 ? 'text-success' : 'text-danger'}">${cb.difference.toFixed(2)}</span>
                            </div>
                        `).join('');

                        Swal.fire({
                            title: 'تم إغلاق الوردية',
                            html: `
                                <div class="text-start">
                                    ${cashboxesHtml}
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>إجمالي الفرق:</span>
                                        <span class="${totalDifference >= 0 ? 'text-success' : 'text-danger'}">${totalDifference.toFixed(2)}</span>
                                    </div>
                                    <p class="text-warning mt-2 mb-0"><i class="ti ti-alert-triangle me-1"></i>يتطلب موافقة المدير</p>
                                </div>
                            `,
                            icon: 'warning',
                            confirmButtonText: 'حسناً'
                        });
                    } else {
                        showToast('تم إغلاق الوردية بنجاح', 'success');
                    }

                    hasOpenShift = false;
                    currentShiftId = null;
                    currentShiftCashboxes = [];
                    shiftCashboxData = [];
                    updateShiftStatus();

                    setTimeout(() => {
                        performLogout();
                    }, 1500);
                } else {
                    showToast(result.message || 'حدث خطأ', 'danger');
                }
            } catch (error) {
                console.error('Close shift error:', error);
                showToast('حدث خطأ في الاتصال', 'danger');
            }

            this.disabled = false;
            this.innerHTML = '<i class="ti ti-check me-1"></i>إغلاق الوردية';
        });

        async function showShiftInfo() {
            if (!currentShiftId) {
                window.location.reload();
                return;
            }

            try {
                const response = await fetch(`{{ url('shifts') }}/${currentShiftId}`);
                const result = await response.json();

                if (result.success) {
                    const shift = result.shift;
                    const totalOpeningBalance = parseFloat(shift.total_opening_balance) || 0;
                    const totalSales = parseFloat(shift.total_sales) || 0;
                    const totalExpectedBalance = parseFloat(shift.total_expected_balance) || 0;

                    let cashboxesHtml = '';
                    if (shift.cashboxes && shift.cashboxes.length > 0) {
                        cashboxesHtml = `
                            <div class="mb-3">
                                <div class="fw-bold mb-2">الصناديق:</div>
                                ${shift.cashboxes.map(cb => `
                                    <div class="d-flex justify-content-between align-items-center mb-1 p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                        <span>${cb.name}</span>
                                        <div class="text-end">
                                            <span class="small text-muted">افتتاحي:</span> ${parseFloat(cb.opening_balance).toFixed(2)}
                                            <span class="mx-1">|</span>
                                            <span class="small text-muted">متوقع:</span> ${parseFloat(cb.expected_balance).toFixed(2)}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }

                    document.getElementById('shiftInfoContent').innerHTML = `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">الكاشير:</span>
                                <span class="fw-bold">${shift.user_name}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">وقت الفتح:</span>
                                <span>${shift.opened_at}</span>
                            </div>
                        </div>
                        ${cashboxesHtml}
                        <hr>
                        <div class="row g-2 text-center">
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">إجمالي الرصيد الافتتاحي</div>
                                    <div class="fw-bold">${totalOpeningBalance.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">إجمالي المبيعات</div>
                                    <div class="fw-bold text-success">${totalSales.toFixed(2)}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">عدد الفواتير</div>
                                    <div class="fw-bold">${shift.sales_count}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 rounded" style="background: var(--bs-tertiary-bg);">
                                    <div class="small text-muted">إجمالي المتوقع</div>
                                    <div class="fw-bold">${totalExpectedBalance.toFixed(2)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    shiftInfoModal.show();
                }
            } catch (error) {
                console.error('Show shift info error:', error);
                showToast('حدث خطأ', 'danger');
            }
        }

        function updateShiftStatus() {
            const shiftBtn = document.getElementById('shiftStatusBtn');
            if (shiftBtn) {
                if (hasOpenShift) {
                    shiftBtn.className = 'btn btn-outline-success btn-sm';
                    shiftBtn.innerHTML = '<i class="ti ti-clock-check"></i>';
                } else {
                    shiftBtn.className = 'btn btn-outline-danger btn-sm';
                    shiftBtn.innerHTML = '<i class="ti ti-clock-off"></i>';
                }
            }
        }

        function checkShiftBeforeSale() {
            if (!hasOpenShift) {
                Swal.fire({
                    title: 'لا يوجد وردية مفتوح',
                    text: 'يجب فتح وردية قبل إتمام عملية البيع',
                    icon: 'warning',
                    confirmButtonText: 'فتح وردية'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
                return false;
            }
            return true;
        }

        window.showShiftInfo = showShiftInfo;
        window.checkShiftBeforeSale = checkShiftBeforeSale;

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0 show`;
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.getElementById('toastContainer').appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        function performLogout() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            form.innerHTML = `<input type="hidden" name="_token" value="${csrfToken}">`;
            document.body.appendChild(form);
            form.submit();
        }

        async function searchProducts(query) {
            if (query.length < 2) {
                document.getElementById('productsList').innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-barcode fs-1 d-block mb-2"></i>
                        <p>امسح الباركود أو ابحث عن منتج</p>
                    </div>
                `;
                return;
            }

            try {
                const response = await fetch(`{{ route('pos.search-products') }}?q=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success && result.products.length > 0) {
                    renderProducts(result.products);
                } else {
                    document.getElementById('productsList').innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="ti ti-search-off fs-1 d-block mb-2"></i>
                            <p>لم يتم العثور على منتجات</p>
                        </div>
                    `;
                }
            } catch (error) {
                showToast('حدث خطأ في البحث', 'danger');
            }
        }

        async function getProductByBarcode(barcode) {
            try {
                const response = await fetch(`{{ route('pos.product-barcode') }}?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();

                if (result.success) {
                    addToCart(result.product, result.product.barcode_label || null);
                    document.getElementById('barcodeInput').value = '';
                } else {
                    showToast('المنتج غير موجود', 'warning');
                }
            } catch (error) {
                showToast('حدث خطأ', 'danger');
            }
        }

        function renderProducts(products) {
            const container = document.getElementById('productsList');
            container.innerHTML = products.map(p => {
                let expiryInfo = '';
                if (p.expiry_status == 'expired') {
                    expiryInfo = `<span class="badge bg-danger">منتهي</span>`;
                } else if (p.expiry_status == 'critical') {
                    expiryInfo = `<span class="badge bg-warning text-dark">${p.days_to_expiry} يوم</span>`;
                } else if (p.expiry_date) {
                    expiryInfo = `<small class="text-muted">${p.expiry_date}</small>`;
                }
                return `
                <div class="product-item ${p.expiry_status == 'expired' ? 'border-danger' : p.expiry_status == 'critical' ? 'border-warning' : ''}" data-product='${JSON.stringify(p)}' style="${p.expiry_status == 'expired' ? 'background: #fff5f5;' : p.expiry_status == 'critical' ? 'background: #fffbeb;' : ''}">
                    <div class="product-info">
                        <div class="product-name">${p.name}</div>
                        <div class="product-barcode">${p.barcode || '-'} ${expiryInfo}</div>
                    </div>
                    <div class="text-end">
                        <div class="product-price">${(p.base_unit?.sale_price || 0).toFixed(2)}</div>
                        <div class="product-stock">المخزون: ${p.stock.toFixed(2)}</div>
                    </div>
                </div>
            `}).join('');

            container.querySelectorAll('.product-item').forEach(item => {
                item.addEventListener('click', function() {
                    const product = JSON.parse(this.dataset.product);
                    addToCart(product);
                });
            });
        }

        function addToCart(product, barcodeLabel = null) {
            const baseUnit = product.base_unit;
            if (!baseUnit) {
                showToast('المنتج ليس له وحدة أساسية', 'warning');
                return;
            }

            const existingIndex = cart.findIndex(item =>
                item.product_id == product.id && item.product_unit_id == baseUnit.id && item.barcode_label == barcodeLabel
            );

            const unitPrice = parseFloat(baseUnit.sale_price) || 0;
            const unitName = baseUnit.name || 'وحدة';
            const displayName = barcodeLabel ? `${product.name} (${barcodeLabel})` : product.name;

            const availableUnits = [];
            if (product.base_unit) {
                availableUnits.push({
                    id: product.base_unit.id,
                    name: product.base_unit.name,
                    sale_price: parseFloat(product.base_unit.sale_price) || 0,
                    multiplier: 1,
                    is_base: true
                });
            }
            if (product.units && product.units.length > 0) {
                product.units.forEach(u => {
                    if (!u.is_base) {
                        availableUnits.push({
                            id: u.id,
                            name: u.name,
                            sale_price: parseFloat(u.sale_price) || 0,
                            multiplier: parseFloat(u.multiplier) || 1,
                            is_base: false
                        });
                    }
                });
            }

            if (existingIndex >= 0) {
                cart[existingIndex].quantity += 1;
            } else {
                cart.push({
                    product_id: product.id,
                    product_unit_id: baseUnit.id,
                    name: displayName,
                    barcode_label: barcodeLabel,
                    unit_name: unitName,
                    unit_price: unitPrice,
                    multiplier: 1,
                    quantity: 1,
                    stock: product.stock || 0,
                    available_units: availableUnits,
                    expiry_date: product.expiry_date || null,
                    expiry_status: product.expiry_status || null,
                    days_to_expiry: product.days_to_expiry
                });

                if (product.expiry_status == 'expired') {
                    showToast(`تحذير: ${displayName} منتهي الصلاحية!`, 'danger');
                } else if (product.expiry_status == 'critical') {
                    showToast(`تحذير: ${displayName} قارب على الانتهاء (${product.days_to_expiry} يوم)`, 'warning');
                }
            }

            renderCart();
            updateTotals();
            selectLastItem();
        }

        const emptyCartHtml = `
            <div class="empty-cart" id="emptyCart">
                <i class="ti ti-shopping-cart-off"></i>
                <p>السلة فارغة</p>
                <small>امسح باركود أو ابحث عن منتج</small>
            </div>
        `;

        function renderCart() {
            const container = document.getElementById('cartItems');

            if (cart.length == 0) {
                container.innerHTML = emptyCartHtml;
                return;
            }

            const cartItemsHtml = cart.map((item, index) => {
                const price = parseFloat(item.unit_price) || 0;
                const qty = parseFloat(item.quantity) || 1;
                const total = qty * price;
                const hasMultipleUnits = item.available_units && item.available_units.length > 1;

                let unitSelector = '';
                if (hasMultipleUnits) {
                    const unitOptions = item.available_units.map(u =>
                        `<option value="${u.id}" data-price="${u.sale_price}" data-multiplier="${u.multiplier}" ${u.id == item.product_unit_id ? 'selected' : ''}>${u.name}</option>`
                    ).join('');
                    unitSelector = `
                        <select class="form-select form-select-sm unit-select" style="width: auto; min-width: 70px; font-size: 0.8rem;">
                            ${unitOptions}
                        </select>
                    `;
                } else {
                    unitSelector = `<span class="badge bg-secondary">${item.unit_name || 'وحدة'}</span>`;
                }

                let expiryBadge = '';
                if (item.expiry_status == 'expired') {
                    expiryBadge = `<span class="badge bg-danger" title="منتهي الصلاحية"><i class="ti ti-alert-circle"></i> منتهي</span>`;
                } else if (item.expiry_status == 'critical') {
                    expiryBadge = `<span class="badge bg-warning text-dark" title="${item.days_to_expiry} يوم"><i class="ti ti-clock"></i> ${item.days_to_expiry}ي</span>`;
                } else if (item.expiry_status == 'warning') {
                    expiryBadge = `<span class="badge bg-info" title="${item.expiry_date}"><i class="ti ti-calendar"></i> ${item.days_to_expiry}ي</span>`;
                }

                return `
                <div class="cart-item ${item.expiry_status == 'expired' ? 'border-danger' : item.expiry_status == 'critical' ? 'border-warning' : ''}" data-index="${index}" style="${item.expiry_status == 'expired' ? 'background: #fff5f5;' : item.expiry_status == 'critical' ? 'background: #fffbeb;' : ''}">
                    <div class="item-info">
                        <div class="item-name d-flex align-items-center gap-1">${item.name} ${expiryBadge}</div>
                        <div class="item-meta d-flex align-items-center gap-1">
                            ${unitSelector}
                            <span class="text-muted">× ${price.toFixed(2)}</span>
                        </div>
                    </div>
                    <div class="item-qty">
                        <button type="button" class="btn btn-sm btn-outline-secondary qty-btn" data-action="minus">-</button>
                        <input type="number" class="qty-input" value="${qty}" min="0.0001" step="1">
                        <button type="button" class="btn btn-sm btn-outline-secondary qty-btn" data-action="plus">+</button>
                    </div>
                    <div class="item-total">${total.toFixed(2)}</div>
                    <div class="item-remove" title="حذف">
                        <i class="ti ti-trash"></i>
                    </div>
                </div>
            `;
            }).join('');

            container.innerHTML = cartItemsHtml;

            container.querySelectorAll('.cart-item').forEach(item => {
                const index = parseInt(item.dataset.index);

                item.querySelector('.item-remove').addEventListener('click', () => {
                    cart.splice(index, 1);
                    renderCart();
                    updateTotals();
                });

                item.querySelectorAll('.qty-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const action = btn.dataset.action;
                        if (action == 'plus') {
                            cart[index].quantity += 1;
                        } else if (action == 'minus' && cart[index].quantity > 1) {
                            cart[index].quantity -= 1;
                        }
                        renderCart();
                        updateTotals();
                    });
                });

                item.querySelector('.qty-input').addEventListener('change', function() {
                    const qty = parseFloat(this.value) || 1;
                    cart[index].quantity = Math.max(0.0001, qty);
                    renderCart();
                    updateTotals();
                });

                // Unit change handler
                const unitSelect = item.querySelector('.unit-select');
                if (unitSelect) {
                    unitSelect.addEventListener('change', function() {
                        changeItemUnit(index, this.value);
                    });
                }
            });
        }

        function changeItemUnit(itemIndex, unitId) {
            const item = cart[itemIndex];
            if (!item || !item.available_units) return;

            const newUnit = item.available_units.find(u => u.id == unitId);
            if (!newUnit) return;

            // Update item with new unit info
            item.product_unit_id = newUnit.id;
            item.unit_name = newUnit.name;
            item.unit_price = newUnit.sale_price;
            item.multiplier = newUnit.multiplier;

            renderCart();
            updateTotals();
            highlightCartItem(itemIndex);
        }

        function cycleItemUnit(itemIndex) {
            const item = cart[itemIndex];
            if (!item || !item.available_units || item.available_units.length <= 1) {
                showToast('لا توجد وحدات أخرى متاحة', 'info');
                return;
            }

            // Find current unit index and cycle to next
            const currentIndex = item.available_units.findIndex(u => u.id == item.product_unit_id);
            const nextIndex = (currentIndex + 1) % item.available_units.length;
            const nextUnit = item.available_units[nextIndex];

            changeItemUnit(itemIndex, nextUnit.id);
            showToast(`تم تغيير الوحدة إلى: ${nextUnit.name}`, 'success');
        }

        function updateTotals() {
            const total = cart.reduce((sum, item) => sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0)), 0);
            document.getElementById('subtotal').textContent = total.toFixed(2);
            document.getElementById('total').textContent = total.toFixed(2);
        }

        function clearCart() {
            cart = [];
            selectedCustomer = null;
            document.getElementById('customerInputContainer').classList.remove('d-none');
            document.getElementById('customerInfoContainer').classList.add('d-none');
            document.getElementById('customerSearch').value = '';
            renderCart();
            updateTotals();
        }

        document.getElementById('clearCartBtn').addEventListener('click', () => {
            if (cart.length == 0) return;
            Swal.fire({
                title: 'تفريغ السلة؟',
                text: 'سيتم حذف جميع المنتجات من السلة',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'نعم، تفريغ',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    clearCart();
                }
            });
        });

        let searchTimeout;
        document.getElementById('barcodeInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            searchTimeout = setTimeout(() => searchProducts(query), 300);
        });

        document.getElementById('barcodeInput').addEventListener('keydown', function(e) {
            if (e.key == 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim();
                if (barcode) {
                    getProductByBarcode(barcode);
                }
            }
        });

        function openServicesModal() {
            if (cart.length == 0) {
                showToast('السلة فارغة', 'warning');
                return;
            }
            new bootstrap.Modal(document.getElementById('servicesModal')).show();
        }

        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const methodId = this.dataset.methodId;
                const methodName = this.textContent.trim();
                bootstrap.Modal.getInstance(document.getElementById('servicesModal')).hide();
                processPaymentWithMethod(methodId, methodName);
            });
        });

        async function processPaymentWithMethod(methodId, methodName) {
            if (cart.length == 0) {
                showToast('السلة فارغة', 'warning');
                return;
            }

            if (!hasOpenShift) {
                Swal.fire({
                    title: 'لا يوجد وردية مفتوح',
                    text: 'يجب فتح وردية قبل إتمام عملية البيع',
                    icon: 'warning',
                    confirmButtonText: 'فتح وردية'
                }).then(() => window.location.reload());
                return;
            }

            const total = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
            const defaultCashboxId = document.querySelector('.cashbox-select')?.value;

            showToast('جاري إتمام البيع...', 'info');

            try {
                const response = await fetch('{{ route("pos.complete-sale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        customer_id: selectedCustomer?.id || null,
                        items: cart.map(item => ({
                            product_id: item.product_id,
                            product_unit_id: item.product_unit_id,
                            barcode_label: item.barcode_label || null,
                            quantity: item.quantity,
                            unit_price: item.unit_price
                        })),
                        payments: [{
                            payment_method_id: methodId,
                            amount: total,
                            cashbox_id: defaultCashboxId || null,
                            reference_number: null
                        }],
                        notes: null
                    })
                });

                const result = await response.json();

                if (result.success) {
                    lastSaleId = result.sale.id;
                    console.log('Sale completed with shift_id:', result.shift_id);
                    showToast(`تم البيع (${methodName}): ${result.invoice_number}`, 'success');
                    window.open(`{{ url('sales') }}/${lastSaleId}/print-thermal?auto=1&close=1`, 'print_receipt', 'width=400,height=600,noopener');
                    clearCart();
                } else {
                    if (result.type == 'no_shift') {
                        hasOpenShift = false;
                        currentShiftId = null;
                        Swal.fire({
                            title: 'لا يوجد وردية مفتوح',
                            text: result.message,
                            icon: 'warning',
                            confirmButtonText: 'فتح وردية'
                        }).then(() => window.location.reload());
                    } else {
                        showToast(result.message, 'danger');
                    }
                }
            } catch (error) {
                showToast('حدث خطأ في إتمام البيع', 'danger');
            }
        }

        function openPaymentModal(mode) {
            if (cart.length == 0) {
                showToast('السلة فارغة', 'warning');
                return;
            }

            if (!hasOpenShift) {
                Swal.fire({
                    title: 'لا يوجد وردية مفتوح',
                    text: 'يجب فتح وردية قبل إتمام عملية البيع',
                    icon: 'warning',
                    confirmButtonText: 'فتح وردية'
                }).then(() => window.location.reload());
                return;
            }

            paymentMode = mode;
            const total = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

            document.getElementById('modalTotal').textContent = total.toFixed(2);
            document.getElementById('modalTotalSummary').textContent = total.toFixed(2);

            const container = document.getElementById('paymentMethodsContainer');
            const entries = container.querySelectorAll('.payment-entry');
            entries.forEach((entry, index) => {
                if (index > 0) entry.remove();
            });
            const firstEntry = container.querySelector('.payment-entry');
            firstEntry.querySelector('.payment-amount').value = total.toFixed(2);

            if (mode == 'multi') {
                document.getElementById('addPaymentMethodBtn').classList.remove('d-none');
            } else {
                document.getElementById('addPaymentMethodBtn').classList.add('d-none');
            }

            updatePaymentSummary();
            new bootstrap.Modal(document.getElementById('paymentModal')).show();
        }

        function updatePaymentSummary() {
            const total = parseFloat(document.getElementById('modalTotalSummary').textContent) || 0;
            let paid = 0;
            document.querySelectorAll('.payment-amount').forEach(input => {
                paid += parseFloat(input.value) || 0;
            });

            const remaining = Math.max(0, total - paid);
            const change = Math.max(0, paid - total);

            document.getElementById('modalPaid').textContent = paid.toFixed(2);
            document.getElementById('modalRemaining').textContent = remaining.toFixed(2);
            document.getElementById('modalChange').textContent = change.toFixed(2);

            if (change > 0) {
                document.getElementById('changeContainer').classList.remove('d-none');
            } else {
                document.getElementById('changeContainer').classList.add('d-none');
            }
        }

        document.getElementById('paymentMethodsContainer').addEventListener('input', function(e) {
            if (e.target.classList.contains('payment-amount')) {
                updatePaymentSummary();
            }
        });

        document.getElementById('quickCashBtn').addEventListener('click', () => quickCashPayment());
        document.getElementById('servicesBtn').addEventListener('click', () => openServicesModal());
        document.getElementById('payMultiBtn').addEventListener('click', () => openPaymentModal('multi'));

        document.getElementById('addPaymentMethodBtn').addEventListener('click', function() {
            const container = document.getElementById('paymentMethodsContainer');
            const entries = container.querySelectorAll('.payment-entry');
            const newIndex = entries.length;
            const firstEntry = entries[0];
            const newEntry = firstEntry.cloneNode(true);
            newEntry.setAttribute('data-index', newIndex);
            newEntry.querySelector('.payment-amount').value = '';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-outline-danger btn-sm mt-2 w-100 remove-payment-btn';
            removeBtn.innerHTML = '<i class="ti ti-trash me-1"></i>حذف';
            removeBtn.addEventListener('click', function() {
                newEntry.remove();
                updatePaymentSummary();
            });
            newEntry.appendChild(removeBtn);
            container.appendChild(newEntry);
            updatePaymentSummary();
        });

        document.getElementById('confirmPaymentBtn').addEventListener('click', async function() {
            if (!hasOpenShift) {
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                Swal.fire({
                    title: 'لا يوجد وردية مفتوح',
                    text: 'يجب فتح وردية قبل إتمام عملية البيع',
                    icon: 'warning',
                    confirmButtonText: 'فتح وردية'
                }).then(() => window.location.reload());
                return;
            }

            const total = parseFloat(document.getElementById('modalTotalSummary').textContent) || 0;
            const payments = [];

            document.querySelectorAll('.payment-entry').forEach(entry => {
                const methodId = entry.querySelector('.payment-method-select').value;
                const amount = parseFloat(entry.querySelector('.payment-amount').value) || 0;
                const cashboxId = entry.querySelector('.cashbox-select')?.value;
                const referenceNumber = entry.querySelector('.reference-input')?.value;

                if (amount > 0) {
                    payments.push({
                        payment_method_id: methodId,
                        amount: amount,
                        cashbox_id: cashboxId || null,
                        reference_number: referenceNumber || null
                    });
                }
            });

            const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
            if (totalPaid < total && !selectedCustomer) {
                showToast('المبلغ المدفوع أقل من الإجمالي. يرجى اختيار زبون للبيع الآجل.', 'warning');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري المعالجة...';

            try {
                const response = await fetch('{{ route("pos.complete-sale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        customer_id: selectedCustomer?.id || null,
                        items: cart.map(item => ({
                            product_id: item.product_id,
                            product_unit_id: item.product_unit_id,
                            barcode_label: item.barcode_label || null,
                            quantity: item.quantity,
                            unit_price: item.unit_price
                        })),
                        payments: payments,
                        notes: document.getElementById('paymentNotes').value
                    })
                });

                const result = await response.json();

                if (result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                    lastSaleId = result.sale.id;
                    console.log('Sale completed with shift_id:', result.shift_id);
                    document.getElementById('invoiceNumber').textContent = result.invoice_number;
                    new bootstrap.Modal(document.getElementById('receiptModal')).show();
                    clearCart();
                } else {
                    if (result.type == 'no_shift') {
                        bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();
                        hasOpenShift = false;
                        currentShiftId = null;
                        Swal.fire({
                            title: 'لا يوجد وردية مفتوح',
                            text: result.message,
                            icon: 'warning',
                            confirmButtonText: 'فتح وردية'
                        }).then(() => window.location.reload());
                    } else {
                        showToast(result.message, 'danger');
                    }
                }
            } catch (error) {
                showToast('حدث خطأ في إتمام البيع', 'danger');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>تأكيد الدفع';
        });

        document.getElementById('printReceiptBtn').addEventListener('click', () => {
            if (lastSaleId) {
                window.open(`{{ url('sales') }}/${lastSaleId}/print-thermal?auto=1`, 'print_receipt', 'width=400,height=600,noopener');
            }
        });

        // Quick Cash Payment (F10) - No modal, instant completion
        async function quickCashPayment() {
            if (cart.length == 0) {
                showToast('السلة فارغة', 'warning');
                return;
            }

            if (!hasOpenShift) {
                Swal.fire({
                    title: 'لا يوجد وردية مفتوح',
                    text: 'يجب فتح وردية قبل إتمام عملية البيع',
                    icon: 'warning',
                    confirmButtonText: 'فتح وردية'
                }).then(() => window.location.reload());
                return;
            }

            const total = cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);

            const defaultCashboxId = document.querySelector('.cashbox-select')?.value;

            // Get cash payment method (first one, usually cash)
            const cashMethodId = document.querySelector('.payment-method-select')?.value;
            if (!cashMethodId) {
                showToast('لا توجد طريقة دفع متاحة', 'danger');
                return;
            }

            // Show loading toast
            showToast('جاري إتمام البيع...', 'info');

            try {
                const response = await fetch('{{ route("pos.complete-sale") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        customer_id: selectedCustomer?.id || null,
                        items: cart.map(item => ({
                            product_id: item.product_id,
                            product_unit_id: item.product_unit_id,
                            barcode_label: item.barcode_label || null,
                            quantity: item.quantity,
                            unit_price: item.unit_price
                        })),
                        payments: [{
                            payment_method_id: cashMethodId,
                            amount: total,
                            cashbox_id: defaultCashboxId || null,
                            reference_number: null
                        }],
                        notes: null
                    })
                });

                const result = await response.json();

                if (result.success) {
                    lastSaleId = result.sale.id;
                    console.log('Sale completed with shift_id:', result.shift_id);

                    // Show success notification
                    showToast(`تم البيع: ${result.invoice_number}`, 'success');

                    // Auto-print receipt
                    window.open(`{{ url('sales') }}/${lastSaleId}/print-thermal?auto=1&close=1`, 'print_receipt', 'width=400,height=600,noopener');

                    // Clear cart and reset
                    clearCart();
                } else {
                    if (result.type == 'no_shift') {
                        hasOpenShift = false;
                        currentShiftId = null;
                        Swal.fire({
                            title: 'لا يوجد وردية مفتوح',
                            text: result.message,
                            icon: 'warning',
                            confirmButtonText: 'فتح وردية'
                        }).then(() => window.location.reload());
                    } else {
                        showToast(result.message, 'danger');
                    }
                }
            } catch (error) {
                showToast('حدث خطأ في إتمام البيع', 'danger');
            }
        }

        document.getElementById('suspendBtn').addEventListener('click', async () => {
            if (cart.length == 0) {
                showToast('السلة فارغة', 'warning');
                return;
            }

            try {
                const response = await fetch('{{ route("pos.suspend") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        customer_id: selectedCustomer?.id || null,
                        items: cart.map(item => ({
                            product_id: item.product_id,
                            unit_id: item.product_unit_id,
                            barcode_label: item.barcode_label || null,
                            quantity: item.quantity,
                            unit_price: item.unit_price
                        }))
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('تم تعليق الفاتورة', 'success');
                    clearCart();
                    updateSuspendedBadge();
                } else {
                    showToast(result.message, 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ', 'danger');
            }
        });

        async function loadSuspendedSales() {
            const container = document.getElementById('suspendedList');
            container.innerHTML = '<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span></div>';

            try {
                const response = await fetch('{{ route("pos.suspended") }}');
                const result = await response.json();

                if (result.success && result.sales.length > 0) {
                    container.innerHTML = result.sales.map(sale => `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">${sale.invoice_number}</div>
                                <small class="text-muted">${sale.customer_name} - ${sale.items_count} منتج</small>
                            </div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-primary">${parseFloat(sale.total).toFixed(2)}</span>
                                <button type="button" class="btn btn-sm btn-success resume-btn" data-id="${sale.id}">
                                    <i class="ti ti-player-play"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger delete-suspended-btn" data-id="${sale.id}">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');

                    container.querySelectorAll('.resume-btn').forEach(btn => {
                        btn.addEventListener('click', () => resumeSuspendedSale(btn.dataset.id));
                    });

                    container.querySelectorAll('.delete-suspended-btn').forEach(btn => {
                        btn.addEventListener('click', () => deleteSuspendedSale(btn.dataset.id));
                    });
                } else {
                    container.innerHTML = '<div class="text-center py-4 text-muted">لا توجد فواتير معلقة</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="text-center py-4 text-danger">حدث خطأ</div>';
            }
        }

        async function resumeSuspendedSale(id) {
            try {
                const response = await fetch(`{{ url('pos/resume') }}/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    cart = result.sale.items.map(item => {
                        const displayName = item.barcode_label ? `${item.product_name} (${item.barcode_label})` : item.product_name;
                        return {
                            product_id: item.product_id,
                            product_unit_id: item.unit_id,
                            barcode_label: item.barcode_label || null,
                            name: displayName,
                            unit_name: item.unit_name,
                            unit_price: parseFloat(item.unit_price),
                            quantity: parseFloat(item.quantity),
                            stock: 999
                        };
                    });

                    if (result.sale.customer) {
                        selectedCustomer = result.sale.customer;
                        document.getElementById('customerInputContainer').classList.add('d-none');
                        document.getElementById('customerInfoContainer').classList.remove('d-none');
                        document.getElementById('customerName').textContent = selectedCustomer.name;
                        document.getElementById('customerBalance').textContent = parseFloat(selectedCustomer.current_balance).toFixed(2);
                    }

                    renderCart();
                    updateTotals();
                    bootstrap.Modal.getInstance(document.getElementById('suspendedModal')).hide();
                    showToast('تم استئناف الفاتورة', 'success');
                    updateSuspendedBadge();
                } else {
                    showToast(result.message, 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ', 'danger');
            }
        }

        async function deleteSuspendedSale(id) {
            try {
                const response = await fetch(`{{ url('pos/suspended') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showToast('تم حذف الفاتورة المعلقة', 'success');
                    loadSuspendedSales();
                    updateSuspendedBadge();
                } else {
                    showToast(result.message, 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ', 'danger');
            }
        }

        function updateSuspendedBadge() {
            fetch('{{ route("pos.suspended") }}')
                .then(r => r.json())
                .then(result => {
                    const badge = document.querySelector('.suspended-badge');
                    const count = result.sales?.length || 0;
                    if (count > 0) {
                        if (badge) {
                            badge.textContent = count;
                        } else {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'suspended-badge';
                            newBadge.textContent = count;
                            document.getElementById('showSuspendedBtn').appendChild(newBadge);
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                });
        }

        document.getElementById('showSuspendedBtn').addEventListener('click', () => {
            loadSuspendedSales();
            new bootstrap.Modal(document.getElementById('suspendedModal')).show();
        });

        let customerSearchTimeout;
        document.getElementById('customerSearch').addEventListener('focus', function() {
            resetCustomerModal();
            new bootstrap.Modal(document.getElementById('customerSearchModal')).show();
            setTimeout(() => document.getElementById('customerSearchInput').focus(), 300);
        });

        document.getElementById('customerSearchInput').addEventListener('input', function() {
            clearTimeout(customerSearchTimeout);
            const query = this.value.trim();
            customerSearchTimeout = setTimeout(() => searchCustomers(query), 300);
        });

        async function searchCustomers(query) {
            if (query.length < 2) {
                document.getElementById('customerResults').innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`{{ route('pos.search-customers') }}?q=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success && result.customers.length > 0) {
                    document.getElementById('customerResults').innerHTML = result.customers.map(c => `
                        <a href="#" class="list-group-item list-group-item-action customer-result" data-customer='${JSON.stringify(c)}'>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold">${c.name}</div>
                                    <small class="text-muted">${c.phone}</small>
                                </div>
                                <div class="text-end">
                                    <div class="badge ${parseFloat(c.current_balance) > 0 ? 'bg-danger' : 'bg-success'}">${parseFloat(c.current_balance).toFixed(2)}</div>
                                    ${c.allow_credit ? '<small class="text-info d-block">آجل</small>' : ''}
                                </div>
                            </div>
                        </a>
                    `).join('');

                    document.querySelectorAll('.customer-result').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            selectCustomer(JSON.parse(this.dataset.customer));
                        });
                    });
                } else {
                    document.getElementById('customerResults').innerHTML = '<div class="text-center py-3 text-muted">لا يوجد نتائج</div>';
                }
            } catch (error) {
                console.error(error);
            }
        }

        function selectCustomer(customer) {
            selectedCustomer = customer;
            document.getElementById('customerInputContainer').classList.add('d-none');
            document.getElementById('customerInfoContainer').classList.remove('d-none');
            document.getElementById('customerName').textContent = customer.name;
            document.getElementById('customerBalance').textContent = parseFloat(customer.current_balance).toFixed(2);
            bootstrap.Modal.getInstance(document.getElementById('customerSearchModal')).hide();
            resetCustomerModal();
        }

        function resetCustomerModal() {
            document.getElementById('customerSearchSection').classList.remove('d-none');
            document.getElementById('addCustomerSection').classList.add('d-none');
            document.getElementById('customerSearchInput').value = '';
            document.getElementById('customerResults').innerHTML = '';
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerCreditLimit').value = '0';
            document.getElementById('newCustomerAllowCredit').value = '0';
        }

        document.getElementById('showAddCustomerBtn').addEventListener('click', () => {
            document.getElementById('customerSearchSection').classList.add('d-none');
            document.getElementById('addCustomerSection').classList.remove('d-none');
            document.getElementById('newCustomerName').focus();
        });

        document.getElementById('backToSearchCustomerBtn').addEventListener('click', () => {
            document.getElementById('customerSearchSection').classList.remove('d-none');
            document.getElementById('addCustomerSection').classList.add('d-none');
            document.getElementById('customerSearchInput').focus();
        });

        document.getElementById('saveNewCustomerBtn').addEventListener('click', async function() {
            const name = document.getElementById('newCustomerName').value.trim();
            const phone = document.getElementById('newCustomerPhone').value.trim();
            const creditLimit = parseFloat(document.getElementById('newCustomerCreditLimit').value) || 0;
            const allowCredit = document.getElementById('newCustomerAllowCredit').value == '1';

            if (!name) {
                showToast('يرجى إدخال اسم الزبون', 'warning');
                return;
            }
            if (!phone) {
                showToast('يرجى إدخال رقم الهاتف', 'warning');
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const response = await fetch('{{ route("pos.customer.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        name: name,
                        phone: phone,
                        credit_limit: creditLimit,
                        allow_credit: allowCredit
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('تم إضافة الزبون بنجاح', 'success');
                    selectCustomer(result.customer);
                } else {
                    showToast(result.message || 'حدث خطأ', 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ في إضافة الزبون', 'danger');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ واختيار الزبون';
        });

        document.getElementById('clearCustomer').addEventListener('click', () => {
            selectedCustomer = null;
            document.getElementById('customerInputContainer').classList.remove('d-none');
            document.getElementById('customerInfoContainer').classList.add('d-none');
            document.getElementById('customerSearch').value = '';
        });

        document.getElementById('themeToggle').addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme == 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', newTheme);
            const icon = document.getElementById('themeIcon');
            icon.className = newTheme == 'dark' ? 'ti ti-moon' : 'ti ti-sun';

            let config = JSON.parse(sessionStorage.getItem('__THEME_CONFIG__') || '{}');
            config.theme = newTheme;
            sessionStorage.setItem('__THEME_CONFIG__', JSON.stringify(config));
        });

        (function initThemeIcon() {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = currentTheme == 'dark' ? 'ti ti-moon' : 'ti ti-sun';
            }
        })();

        KeyboardManager.init({
            primaryInput: document.getElementById('barcodeInput')
        }).registerMultiple({
            ' ': () => quickCashPayment(),
            'F2': () => openServicesModal(),
            'F3': () => openPaymentModal('multi'),
            'F6': () => {
                if (selectedCartIndex >= 0 && selectedCartIndex < cart.length) {
                    cycleItemUnit(selectedCartIndex);
                } else if (cart.length > 0) {
                    selectedCartIndex = cart.length - 1;
                    highlightCartItem(selectedCartIndex);
                    cycleItemUnit(selectedCartIndex);
                }
            },
            'F7': () => document.getElementById('returnBtn').click(),
            'F8': () => {
                loadSuspendedSales();
                new bootstrap.Modal(document.getElementById('suspendedModal')).show();
            },
            'F9': () => document.getElementById('suspendBtn').click(),
            'Escape': () => {},
            'Delete': () => {
                if (selectedCartIndex >= 0 && selectedCartIndex < cart.length) {
                    cart.splice(selectedCartIndex, 1);
                    if (cart.length == 0) {
                        selectedCartIndex = -1;
                    } else if (selectedCartIndex >= cart.length) {
                        selectedCartIndex = cart.length - 1;
                    }
                    renderCart();
                    updateTotals();
                    if (selectedCartIndex >= 0) highlightCartItem(selectedCartIndex);
                }
            },
            'ArrowUp': () => adjustActiveQty(1),
            'ArrowDown': () => adjustActiveQty(-1),
            'ArrowRight': () => adjustActiveQty(5),
            'ArrowLeft': () => adjustActiveQty(-5),
            'Plus': () => adjustActiveQty(1),
            'Minus': () => adjustActiveQty(-1)
        });

        let selectedCartIndex = -1;

        function selectLastItem() {
            if (cart.length > 0) {
                selectedCartIndex = cart.length - 1;
                highlightCartItem(selectedCartIndex);
            }
        }

        function adjustActiveQty(delta) {
            if (cart.length == 0) return;
            if (selectedCartIndex < 0 || selectedCartIndex >= cart.length) {
                selectedCartIndex = cart.length - 1;
                highlightCartItem(selectedCartIndex);
            }
            const item = cart[selectedCartIndex];
            let newQty = item.quantity + delta;
            if (delta > 0 && item.stock > 0 && newQty > item.stock) {
                newQty = item.stock;
                showToast('تم الوصول للحد الأقصى من المخزون', 'warning');
            }
            if (newQty <= 0) {
                cart.splice(selectedCartIndex, 1);
                if (cart.length == 0) {
                    selectedCartIndex = -1;
                } else if (selectedCartIndex >= cart.length) {
                    selectedCartIndex = cart.length - 1;
                }
                renderCart();
                updateTotals();
                if (selectedCartIndex >= 0) highlightCartItem(selectedCartIndex);
                return;
            }
            item.quantity = newQty;
            renderCart();
            updateTotals();
            highlightCartItem(selectedCartIndex);
        }

        function navigateCart(direction) {
            if (cart.length == 0) return;
            selectedCartIndex += direction;
            if (selectedCartIndex < 0) selectedCartIndex = cart.length - 1;
            if (selectedCartIndex >= cart.length) selectedCartIndex = 0;
            highlightCartItem(selectedCartIndex);
        }

        function highlightCartItem(index) {
            document.querySelectorAll('.cart-item').forEach((item, i) => {
                item.classList.toggle('selected', i == index);
                if (i == index) {
                    item.style.background = 'var(--pos-bg)';
                    item.style.borderColor = 'var(--pos-primary)';
                } else {
                    item.style.background = '';
                    item.style.borderColor = '';
                }
            });
        }

        function adjustSelectedQty(delta) {
            if (selectedCartIndex < 0 || selectedCartIndex >= cart.length) return;
            const newQty = Math.max(0.0001, cart[selectedCartIndex].quantity + delta);
            cart[selectedCartIndex].quantity = newQty;
            renderCart();
            updateTotals();
            highlightCartItem(selectedCartIndex);
        }

        let returnSaleData = null;
        let returnItems = [];

        document.getElementById('returnBtn').addEventListener('click', () => {
            resetReturnModal();
            new bootstrap.Modal(document.getElementById('returnModal')).show();
            setTimeout(() => document.getElementById('returnInvoiceSearch').focus(), 300);
        });

        function resetReturnModal() {
            returnSaleData = null;
            returnItems = [];
            document.getElementById('returnSearchSection').classList.remove('d-none');
            document.getElementById('returnDetailsSection').classList.add('d-none');
            document.getElementById('processReturnBtn').classList.add('d-none');
            document.getElementById('returnInvoiceSearch').value = '';
            document.getElementById('invoiceSearchResults').innerHTML = '';
            document.getElementById('returnReason').value = '';
            document.getElementById('refundMethod').value = '';
            document.getElementById('returnNotes').value = '';
            document.getElementById('restoreStock').checked = true;
            document.getElementById('returnTotalAmount').textContent = '0.00';
        }

        document.getElementById('backToSearchBtn').addEventListener('click', resetReturnModal);

        let returnSearchTimeout;
        document.getElementById('returnInvoiceSearch').addEventListener('input', function() {
            clearTimeout(returnSearchTimeout);
            const query = this.value.trim();

            if (query.length < 3) {
                document.getElementById('invoiceSearchResults').innerHTML = '<div class="text-center py-3 text-muted">أدخل 3 أحرف على الأقل</div>';
                return;
            }

            returnSearchTimeout = setTimeout(() => searchInvoicesForReturn(query), 300);
        });

        document.getElementById('searchInvoiceBtn').addEventListener('click', () => {
            const query = document.getElementById('returnInvoiceSearch').value.trim();
            if (query.length >= 3) {
                searchInvoicesForReturn(query);
            }
        });

        async function searchInvoicesForReturn(query) {
            const container = document.getElementById('invoiceSearchResults');
            container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';

            try {
                const response = await fetch(`{{ route('pos.return.search') }}?q=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success && result.sales.length > 0) {
                    container.innerHTML = result.sales.map(sale => `
                        <a href="#" class="list-group-item list-group-item-action invoice-result" data-id="${sale.id}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${sale.invoice_number}</strong>
                                    <small class="text-muted d-block">${sale.customer_name} - ${sale.sale_date}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-primary">${parseFloat(sale.total).toFixed(2)}</span>
                                    ${sale.total_returned > 0 ? `<small class="text-warning d-block">مرتجع: ${parseFloat(sale.total_returned).toFixed(2)}</small>` : ''}
                                </div>
                            </div>
                        </a>
                    `).join('');

                    container.querySelectorAll('.invoice-result').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            loadSaleForReturn(this.dataset.id);
                        });
                    });
                } else {
                    container.innerHTML = '<div class="text-center py-3 text-muted">لا توجد فواتير قابلة للاسترداد</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="text-center py-3 text-danger">حدث خطأ في البحث</div>';
            }
        }

        async function loadSaleForReturn(saleId) {
            try {
                const response = await fetch(`{{ url('pos/return/sale') }}/${saleId}`);
                const result = await response.json();

                if (result.success) {
                    returnSaleData = result.sale;
                    displaySaleForReturn(result.sale);
                } else {
                    showToast(result.message, 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ في تحميل الفاتورة', 'danger');
            }
        }

        function displaySaleForReturn(sale) {
            document.getElementById('returnSearchSection').classList.add('d-none');
            document.getElementById('returnDetailsSection').classList.remove('d-none');
            document.getElementById('processReturnBtn').classList.remove('d-none');

            document.getElementById('returnInvoiceNumber').textContent = sale.invoice_number;
            document.getElementById('returnInvoiceDate').textContent = sale.sale_date;
            document.getElementById('returnCustomerName').textContent = sale.customer_name;
            document.getElementById('returnInvoiceTotal').textContent = parseFloat(sale.total).toFixed(2);

            const refundMethodSelect = document.getElementById('refundMethod');
            refundMethodSelect.innerHTML = '<option value="">اختر الطريقة</option><option value="cash">رد نقدي للخزينة</option>';

            if (sale.customer_id) {
                refundMethodSelect.innerHTML += '<option value="store_credit">رصيد للزبون</option>';
                if (sale.credit_amount > 0) {
                    refundMethodSelect.innerHTML += '<option value="deduct_credit">خصم من حساب الزبون</option>';
                }
            }

            returnItems = sale.items.map(item => ({
                ...item,
                return_qty: 0,
                selected: false
            }));

            renderReturnItems();
            updateReturnTotal();
        }

        function renderReturnItems() {
            const tbody = document.getElementById('returnItemsBody');
            tbody.innerHTML = returnItems.map((item, index) => `
                <tr>
                    <td>
                        <input type="checkbox" class="return-item-check" data-index="${index}" ${item.selected ? 'checked' : ''} ${item.returnable_qty <= 0 ? 'disabled' : ''}>
                    </td>
                    <td>
                        <div>${item.product_name}</div>
                        <small class="text-muted">${item.unit_name}</small>
                    </td>
                    <td>${parseFloat(item.quantity).toFixed(2)}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm return-qty-input" data-index="${index}"
                            value="${item.return_qty}" min="0" max="${item.returnable_qty}" step="0.01"
                            ${item.returnable_qty <= 0 ? 'disabled' : ''}>
                        ${item.returned_qty > 0 ? `<small class="text-warning">مرتجع: ${parseFloat(item.returned_qty).toFixed(2)}</small>` : ''}
                    </td>
                    <td>${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="return-item-total">${(item.return_qty * item.unit_price).toFixed(2)}</td>
                </tr>
            `).join('');

            tbody.querySelectorAll('.return-item-check').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const index = parseInt(this.dataset.index);
                    returnItems[index].selected = this.checked;
                    if (this.checked && returnItems[index].return_qty == 0) {
                        returnItems[index].return_qty = returnItems[index].returnable_qty;
                    } else if (!this.checked) {
                        returnItems[index].return_qty = 0;
                    }
                    renderReturnItems();
                    updateReturnTotal();
                });
            });

            tbody.querySelectorAll('.return-qty-input').forEach(input => {
                input.addEventListener('input', function() {
                    const index = parseInt(this.dataset.index);
                    let val = parseFloat(this.value) || 0;
                    val = Math.min(val, returnItems[index].returnable_qty);
                    val = Math.max(val, 0);
                    returnItems[index].return_qty = val;
                    returnItems[index].selected = val > 0;
                    renderReturnItems();
                    updateReturnTotal();
                });
            });
        }

        document.getElementById('selectAllItems').addEventListener('change', function() {
            const checked = this.checked;
            returnItems.forEach(item => {
                if (item.returnable_qty > 0) {
                    item.selected = checked;
                    item.return_qty = checked ? item.returnable_qty : 0;
                }
            });
            renderReturnItems();
            updateReturnTotal();
        });

        function updateReturnTotal() {
            const total = returnItems.reduce((sum, item) => sum + (item.return_qty * item.unit_price), 0);
            document.getElementById('returnTotalAmount').textContent = total.toFixed(2);
        }

        document.getElementById('refundMethod').addEventListener('change', function() {
            const cashboxGroup = document.getElementById('cashboxSelectGroup');
            if (this.value == 'cash') {
                cashboxGroup.classList.remove('d-none');
            } else {
                cashboxGroup.classList.add('d-none');
            }
        });

        document.getElementById('processReturnBtn').addEventListener('click', async function() {
            const reason = document.getElementById('returnReason').value;
            const refundMethod = document.getElementById('refundMethod').value;
            const cashboxId = document.getElementById('returnCashbox').value;
            const restoreStock = document.getElementById('restoreStock').checked;
            const notes = document.getElementById('returnNotes').value;

            if (!reason) {
                showToast('يجب اختيار سبب الاسترجاع', 'warning');
                return;
            }

            if (!refundMethod) {
                showToast('يجب اختيار طريقة الاسترداد', 'warning');
                return;
            }

            const selectedItems = returnItems.filter(item => item.return_qty > 0);
            if (selectedItems.length == 0) {
                showToast('يجب اختيار عناصر للإرجاع', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: 'تأكيد الاسترداد',
                html: `
                    <p>هل أنت متأكد من استرداد ${selectedItems.length} عنصر؟</p>
                    <p class="text-danger fw-bold">المبلغ: ${document.getElementById('returnTotalAmount').textContent} د.ل</p>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                cancelButtonText: 'إلغاء',
                confirmButtonText: 'تأكيد الاسترداد'
            });

            if (!result.isConfirmed) return;

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري المعالجة...';

            try {
                const response = await fetch('{{ route("pos.return.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        sale_id: returnSaleData.id,
                        items: selectedItems.map(item => ({
                            sale_item_id: item.id,
                            quantity: item.return_qty
                        })),
                        reason: reason,
                        reason_notes: notes,
                        refund_method: refundMethod,
                        cashbox_id: refundMethod == 'cash' ? cashboxId : null,
                        restore_stock: restoreStock,
                        notes: notes
                    })
                });

                const data = await response.json();

                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('returnModal')).hide();

                    await Swal.fire({
                        icon: 'success',
                        title: 'تم الاسترداد بنجاح',
                        html: `
                            <p>رقم المرتجع: <strong>${data.return.return_number}</strong></p>
                            <p>المبلغ: <strong>${parseFloat(data.return.total_amount).toFixed(2)} د.ل</strong></p>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'طباعة الإيصال',
                        cancelButtonText: 'إغلاق'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.open(`{{ url('pos/return') }}/${data.return.id}/print?auto=1`, '_blank', 'width=400,height=600');
                        }
                    });
                } else {
                    showToast(data.message, 'danger');
                }
            } catch (error) {
                showToast('حدث خطأ في معالجة الاسترداد', 'danger');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>تأكيد الاسترداد';
        });

        if ('ontouchstart' in window) {
            document.getElementById('numpadContainer').classList.add('show');

            document.querySelectorAll('.numpad button').forEach(btn => {
                btn.addEventListener('click', function() {
                    const activeInput = document.activeElement;
                    if (activeInput.tagName != 'INPUT') return;

                    const num = this.dataset.num;
                    if (num == 'clear') {
                        activeInput.value = activeInput.value.slice(0, -1);
                    } else {
                        activeInput.value += num;
                    }
                    activeInput.dispatchEvent(new Event('input'));
                });
            });
        }

        document.getElementById('barcodeInput').focus();
    });
    </script>
</body>
</html>
