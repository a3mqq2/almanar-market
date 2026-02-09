@extends('layouts.app')

@section('title', 'فاتورة مشتريات جديدة')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">المشتريات</a></li>
    <li class="breadcrumb-item active">فاتورة جديدة</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    /* Card Styles */
    .purchase-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        position: relative;
    }
    .purchase-card-header {
        background: var(--bs-light, #f8f9fa);
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .purchase-card-header i {
        font-size: 1.25rem;
        color: var(--bs-primary);
    }
    .purchase-card-header h6 {
        margin: 0;
        font-weight: 600;
        font-size: 1rem;
    }
    .purchase-card-body {
        padding: 1.25rem;
        background: var(--bs-card-bg, #fff);
    }

    [data-bs-theme="dark"] .purchase-card {
        background: var(--bs-card-bg, #212529);
    }
    [data-bs-theme="dark"] .purchase-card-header {
        background: var(--bs-tertiary-bg, #2b3035);
    }
    [data-bs-theme="dark"] .purchase-card-body {
        background: var(--bs-card-bg, #212529);
    }

    /* Supplier Balance Box */
    .supplier-balance {
        background-color: #0d6efd !important;
        border-radius: 8px;
        padding: 0.75rem 1rem;
        margin-top: 0.5rem;
        display: none;
        text-align: center;
    }
    .supplier-balance.show {
        display: block !important;
    }
    .supplier-balance .supplier-balance-label {
        font-size: 0.75rem;
        opacity: 0.9;
        color: #ffffff !important;
    }
    .supplier-balance .supplier-balance-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #ffffff !important;
    }

    /* Tom Select z-index fix */
    .ts-wrapper {
        position: relative;
        z-index: 100;
    }
    .ts-wrapper.dropdown-active {
        z-index: 1100;
    }
    .ts-wrapper .ts-dropdown {
        z-index: 1100 !important;
        position: absolute !important;
    }

    /* Tom Select RTL & Dark Mode */
    .ts-wrapper {
        direction: rtl;
    }
    .ts-wrapper .ts-control {
        background: #fff;
        border-color: var(--bs-border-color);
        color: #333;
        border-radius: 6px;
        padding: 0.5rem 0.75rem;
    }
    .ts-wrapper .ts-control input {
        color: #333;
    }
    .ts-wrapper .ts-dropdown {
        background: #fff;
        border-color: var(--bs-border-color);
        color: #333;
    }
    .ts-wrapper .ts-dropdown .option {
        color: #333;
    }
    .ts-wrapper .ts-dropdown .option:hover,
    .ts-wrapper .ts-dropdown .active {
        background: #f8f9fa;
        color: #333;
    }
    .ts-wrapper.focus .ts-control {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-control {
        background: var(--bs-card-bg, #212529);
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-control input {
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-dropdown {
        background: var(--bs-card-bg, #212529);
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-dropdown .option {
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-dropdown .option:hover,
    [data-bs-theme="dark"] .ts-wrapper .ts-dropdown .active {
        background: var(--bs-tertiary-bg, #2b3035);
    }

    /* Items Table */
    .items-table-wrapper {
        overflow-x: auto;
    }
    .items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .items-table th {
        background: var(--bs-light, #f8f9fa);
        padding: 0.875rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        border-bottom: 2px solid var(--bs-border-color);
        white-space: nowrap;
        color: var(--bs-body-color);
    }
    .items-table td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-card-bg, #fff);
    }
    .items-table tbody tr:hover td {
        background: var(--bs-light, #f8f9fa);
    }
    .items-table .form-control,
    .items-table .form-select {
        font-size: 0.875rem;
    }
    .item-row {
        transition: background-color 0.15s ease;
    }

    [data-bs-theme="dark"] .items-table th {
        background: var(--bs-tertiary-bg, #2b3035);
    }
    [data-bs-theme="dark"] .items-table td {
        background: var(--bs-card-bg, #212529);
    }
    [data-bs-theme="dark"] .items-table tbody tr:hover td {
        background: var(--bs-tertiary-bg, #2b3035);
    }

    /* Summary Card */
    .summary-card {
        position: sticky;
        top: 1rem;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .summary-row:last-of-type {
        border-bottom: none;
    }
    .summary-row.total {
        font-weight: 700;
        font-size: 1.25rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 2px solid var(--bs-border-color);
        color: var(--bs-primary);
    }
    .summary-label {
        color: var(--bs-secondary-color);
    }
    .summary-value {
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    /* Product Search */
    .product-search-box {
        position: relative;
    }
    .product-search-input {
        padding-right: 2.5rem;
    }
    .product-search-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--bs-secondary-color);
    }
    .product-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1050;
        display: none;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        margin-top: 4px;
    }
    .product-search-results.show {
        display: block;
    }
    .product-search-item {
        padding: 0.875rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid var(--bs-border-color);
        transition: background 0.15s;
        background: var(--bs-card-bg, #fff);
    }
    .product-search-item:last-child {
        border-bottom: none;
    }
    .product-search-item:hover {
        background: var(--bs-light, #f8f9fa);
    }
    .product-search-item .product-name {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .product-search-item .product-meta {
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
        display: flex;
        gap: 1rem;
    }

    [data-bs-theme="dark"] .product-search-results {
        background: var(--bs-card-bg, #212529);
    }
    [data-bs-theme="dark"] .product-search-item {
        background: var(--bs-card-bg, #212529);
    }
    [data-bs-theme="dark"] .product-search-item:hover {
        background: var(--bs-tertiary-bg, #2b3035);
    }

    /* Barcode Input */
    .barcode-input-wrapper {
        position: relative;
    }
    .barcode-input-wrapper .spinner-border {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        display: none;
    }
    .barcode-input-wrapper.loading .spinner-border {
        display: block;
    }
    .barcode-input-wrapper.loading input {
        padding-left: 2.5rem;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
        border-top: 1px solid var(--bs-border-color);
    }
    .action-buttons .btn {
        padding: 0.75rem 1rem;
        font-weight: 500;
    }

    /* Toast Container */
    .toast-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
    }

    /* Form Controls in Dark Mode */
    .form-control:focus,
    .form-select:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.15);
    }

    /* Discount/Tax Inputs */
    .discount-tax-group {
        background: var(--bs-light, #f8f9fa);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .discount-tax-group .form-label {
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    [data-bs-theme="dark"] .discount-tax-group {
        background: var(--bs-tertiary-bg, #2b3035);
    }

    /* Empty State */
    .empty-items {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--bs-secondary-color);
        background: var(--bs-light, #f8f9fa);
        border-radius: 8px;
        margin: 0.5rem;
    }
    .empty-items i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    [data-bs-theme="dark"] .empty-items {
        background: var(--bs-tertiary-bg, #2b3035);
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<form id="purchaseForm" novalidate>
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <!-- Supplier Card -->
            <div class="purchase-card">
                <div class="purchase-card-header">
                    <i class="ti ti-truck"></i>
                    <h6>بيانات المورد</h6>
                </div>
                <div class="purchase-card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">المورد <span class="text-warning">*</span></label>
                            <select class="form-select" id="supplier_id" name="supplier_id" required>
                                <option value="">اختر المورد...</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" data-balance="{{ $supplier->current_balance }}" data-phone="{{ $supplier->phone }}">
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">يرجى اختيار المورد</div>
                        </div>
                        <div class="col-md-4">
                            <div class="supplier-balance" id="supplierBalanceBox" style="background-color: #0d6efd;">
                                <div class="supplier-balance-label" style="color: #fff;">الرصيد الحالي</div>
                                <div class="supplier-balance-value" id="supplierBalanceValue" style="color: #fff;">0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Info Card -->
            <div class="purchase-card">
                <div class="purchase-card-header">
                    <i class="ti ti-file-invoice"></i>
                    <h6>بيانات الفاتورة</h6>
                </div>
                <div class="purchase-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">رقم فاتورة المورد</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number"
                                   placeholder="اختياري">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">تاريخ الشراء <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="purchase_date" name="purchase_date"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">طريقة الدفع <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_type" name="payment_type" required>
                                <option value="credit" selected>آجل (على الحساب)</option>
                                <option value="cash">نقدي</option>
                                <option value="bank">تحويل بنكي</option>
                            </select>
                        </div>
                        <div class="col-md-4" id="cashboxField" style="display: none;">
                            <label class="form-label">الخزينة <span class="text-danger">*</span></label>
                            <select class="form-select" id="cashbox_id" name="cashbox_id">
                                <option value="">اختر الخزينة...</option>
                                @foreach($cashboxes as $cashbox)
                                    <option value="{{ $cashbox->id }}" data-balance="{{ $cashbox->current_balance }}">
                                        {{ $cashbox->name }} ({{ number_format($cashbox->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">يرجى اختيار الخزينة</div>
                        </div>
                        <div class="col-md-4" id="paidAmountField" style="display: none;">
                            <label class="form-label">المبلغ المدفوع</label>
                            <input type="number" class="form-control" id="paid_amount" name="paid_amount" value="0" min="0" step="0.01">
                            <small class="text-muted">اتركه 0 للدفع الكامل آجلاً</small>
                        </div>
                        <div class="col-md-4" id="remainingAmountField" style="display: none;">
                            <label class="form-label">المتبقي (يضاف لحساب المورد)</label>
                            <div class="form-control bg-warning-subtle text-warning-emphasis fw-bold" id="remainingAmountDisplay">0.00</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"
                                      placeholder="ملاحظات إضافية على الفاتورة..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Card -->
            <div class="purchase-card">
                <div class="purchase-card-header">
                    <i class="ti ti-list-details"></i>
                    <h6>أصناف الفاتورة</h6>
                </div>
                <div class="purchase-card-body">
                    <!-- Search & Add -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <div class="product-search-box">
                                <i class="ti ti-search product-search-icon"></i>
                                <input type="text" class="form-control product-search-input" id="productSearch"
                                       placeholder="ابحث عن صنف بالاسم أو الباركود...">
                                <div class="product-search-results" id="productSearchResults"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="barcode-input-wrapper" id="barcodeWrapper">
                                <input type="text" class="form-control" id="barcodeInput"
                                       placeholder="مسح الباركود...">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="items-table-wrapper">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>الصنف</th>
                                    <th style="width: 120px;">الوحدة</th>
                                    <th style="width: 100px;">الكمية</th>
                                    <th style="width: 110px;">السعر</th>
                                    <th style="width: 120px;">الإجمالي</th>
                                    <th style="width: 130px;">تاريخ الانتهاء</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyRow">
                                    <td colspan="8">
                                        <div class="empty-items">
                                            <i class="ti ti-package-off d-block"></i>
                                            <p class="mb-1 fw-medium">لم يتم إضافة أصناف بعد</p>
                                            <small>ابحث عن صنف أعلاه أو امسح الباركود</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="purchase-card summary-card">
                <div class="purchase-card-header">
                    <i class="ti ti-calculator"></i>
                    <h6>ملخص الفاتورة</h6>
                </div>
                <div class="purchase-card-body">
                    <div class="summary-row">
                        <span class="summary-label">عدد الأصناف:</span>
                        <span class="summary-value" id="itemsCountDisplay">0</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">المجموع الفرعي:</span>
                        <span class="summary-value" id="subtotalDisplay">0.00</span>
                    </div>

                    <div class="discount-tax-group">
                        <label class="form-label">الخصم</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select" id="discount_type" name="discount_type" style="max-width: 90px;">
                                <option value="">بدون</option>
                                <option value="fixed">مبلغ</option>
                                <option value="percentage">%</option>
                            </select>
                            <input type="number" class="form-control" id="discount_value" name="discount_value"
                                   value="0" min="0" step="0.01" disabled>
                        </div>
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">الخصم:</span>
                        <span class="summary-value text-danger" id="discountDisplay">- 0.00</span>
                    </div>

                    <div class="discount-tax-group">
                        <label class="form-label">الضريبة (%)</label>
                        <input type="number" class="form-control form-control-sm" id="tax_rate" name="tax_rate"
                               value="0" min="0" max="100" step="0.01" placeholder="0">
                    </div>

                    <div class="summary-row">
                        <span class="summary-label">الضريبة:</span>
                        <span class="summary-value" id="taxDisplay">+ 0.00</span>
                    </div>

                    <div class="summary-row total">
                        <span>الإجمالي:</span>
                        <span id="totalDisplay">0.00</span>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn btn-primary btn-lg" id="saveAndApproveBtn">
                            <i class="ti ti-check me-2"></i>حفظ واعتماد الفاتورة
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="saveAsDraftBtn">
                            <i class="ti ti-device-floppy me-2"></i>حفظ كمسودة
                        </button>
                        <a href="{{ route('purchases.index') }}" class="btn btn-outline-danger">
                            <i class="ti ti-x me-2"></i>إلغاء
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let items = [];
    let itemIndex = 0;
    let searchTimeout = null;

    // Initialize Tom Select for Supplier
    const supplierSelect = new TomSelect('#supplier_id', {
        placeholder: 'ابحث عن مورد...',
        allowEmptyOption: true,
        render: {
            option: function(data, escape) {
                const balance = parseFloat(data.balance || 0);
                const balanceClass = balance > 0 ? 'text-danger' : (balance < 0 ? 'text-success' : '');
                return `<div class="d-flex justify-content-between align-items-center">
                    <span>${escape(data.text)}</span>
                    <small class="${balanceClass} fw-bold">${balance.toFixed(2)}</small>
                </div>`;
            },
            item: function(data, escape) {
                return `<div>${escape(data.text)}</div>`;
            }
        },
        onItemAdd: function(value) {
            const option = this.options[value];
            if (option) {
                const balance = parseFloat(option.balance || 0);
                document.getElementById('supplierBalanceValue').textContent = balance.toFixed(2);
                document.getElementById('supplierBalanceBox').classList.add('show');
            }
        },
        onItemRemove: function() {
            document.getElementById('supplierBalanceBox').classList.remove('show');
        }
    });

    // Product Search
    const productSearch = document.getElementById('productSearch');
    const productSearchResults = document.getElementById('productSearchResults');
    const barcodeInput = document.getElementById('barcodeInput');
    const barcodeWrapper = document.getElementById('barcodeWrapper');

    productSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            productSearchResults.classList.remove('show');
            return;
        }

        searchTimeout = setTimeout(() => searchProducts(query), 300);
    });

    productSearch.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) {
            productSearchResults.classList.add('show');
        }
    });

    document.addEventListener('click', function(e) {
        if (!productSearch.contains(e.target) && !productSearchResults.contains(e.target)) {
            productSearchResults.classList.remove('show');
        }
    });

    async function searchProducts(query) {
        try {
            const response = await fetch(`{{ route('purchases.search-products') }}?q=${encodeURIComponent(query)}`);
            const result = await response.json();

            if (!result.success) {
                console.error('Server error:', result.message);
                productSearchResults.innerHTML = `<div class="p-3 text-center text-danger"><i class="ti ti-alert-circle me-1"></i>${result.message || 'حدث خطأ'}</div>`;
                productSearchResults.classList.add('show');
                return;
            }

            if (result.products.length > 0) {
                let html = '';
                result.products.forEach(product => {
                    const baseUnit = product.base_unit;
                    const costPrice = parseFloat(baseUnit?.cost_price) || 0;
                    html += `
                        <div class="product-search-item" data-product='${JSON.stringify(product)}'>
                            <div class="product-name">${product.name}</div>
                            <div class="product-meta">
                                <span><i class="ti ti-barcode me-1"></i>${product.barcode || '-'}</span>
                                <span><i class="ti ti-package me-1"></i>${product.current_stock}</span>
                                <span><i class="ti ti-currency-riyal me-1"></i>${costPrice.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                });
                productSearchResults.innerHTML = html;
                productSearchResults.classList.add('show');

                productSearchResults.querySelectorAll('.product-search-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const product = JSON.parse(this.dataset.product);
                        addProductToItems(product);
                        productSearch.value = '';
                        productSearchResults.classList.remove('show');
                    });
                });
            } else {
                productSearchResults.innerHTML = '<div class="p-3 text-center text-muted"><i class="ti ti-search-off me-1"></i>لا توجد نتائج</div>';
                productSearchResults.classList.add('show');
            }
        } catch (error) {
            console.error('Search error:', error);
            productSearchResults.innerHTML = `<div class="p-3 text-center text-danger"><i class="ti ti-alert-circle me-1"></i>${error.message || 'حدث خطأ في البحث'}</div>`;
            productSearchResults.classList.add('show');
        }
    }

    // Barcode scanning
    barcodeInput.addEventListener('keypress', async function(e) {
        if (e.key == 'Enter') {
            e.preventDefault();
            const barcode = this.value.trim();
            if (!barcode) return;

            barcodeWrapper.classList.add('loading');

            try {
                const response = await fetch(`{{ route('purchases.product-by-barcode') }}?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();

                if (result.success) {
                    addProductToItems(result.product);
                    this.value = '';
                } else {
                    showToast(result.message || 'لم يتم العثور على المنتج', 'warning');
                }
            } catch (error) {
                showToast('حدث خطأ في البحث', 'danger');
            }

            barcodeWrapper.classList.remove('loading');
        }
    });

    function addProductToItems(product) {
        const existingItem = items.find(item => item.product_id == product.id);
        if (existingItem) {
            const row = document.querySelector(`tr[data-index="${existingItem.index}"]`);
            if (row) {
                const qtyInput = row.querySelector('.item-quantity');
                qtyInput.value = parseFloat(qtyInput.value) + 1;
                updateItemTotal(existingItem.index);
                showToast('تم زيادة الكمية', 'info');
                return;
            }
        }

        const baseUnit = product.base_unit || product.units?.find(u => u.is_base) || product.units?.find(u => u.multiplier == 1);
        const baseCostPrice = parseFloat(baseUnit?.cost_price) || 0;
        const item = {
            index: itemIndex,
            product_id: product.id,
            product_name: product.name,
            product_unit_id: baseUnit?.id || null,
            units: product.units,
            baseCostPrice: baseCostPrice,
            quantity: 1,
            unit_price: baseCostPrice,
            unit_multiplier: 1,
            total_price: baseCostPrice,
            expiry_date: ''
        };

        items.push(item);
        renderItemRow(item);
        itemIndex++;
        updateSummary();
        hideEmptyRow();
    }

    function renderItemRow(item) {
        const tbody = document.getElementById('itemsTableBody');
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.dataset.index = item.index;

        let unitsOptions = '';
        if (item.units && item.units.length > 0) {
            const baseCostPrice = parseFloat(item.baseCostPrice) || 0;

            item.units.forEach(unit => {
                const selected = unit.id == item.product_unit_id ? 'selected' : '';
                const multiplier = parseFloat(unit.multiplier) || 1;
                const isBase = unit.is_base || multiplier == 1;
                const costPrice = isBase ? baseCostPrice : (baseCostPrice * multiplier);
                unitsOptions += `<option value="${unit.id}" data-multiplier="${multiplier}" data-cost="${costPrice.toFixed(2)}" ${selected}>
                    ${unit.name}
                </option>`;
            });
        }

        const unitPrice = parseFloat(item.unit_price) || 0;
        const totalPrice = parseFloat(item.total_price) || 0;

        row.innerHTML = `
            <td class="text-center fw-medium">${items.length}</td>
            <td>
                <div class="fw-medium">${item.product_name}</div>
                <input type="hidden" class="item-product-id" value="${item.product_id}">
            </td>
            <td>
                <select class="form-select form-select-sm item-unit" onchange="updateUnit(${item.index})">
                    ${unitsOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-quantity text-center"
                       value="${item.quantity}" min="0.0001" step="0.0001"
                       onchange="updateItemTotal(${item.index})" onfocus="this.select()">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm item-price text-center"
                       value="${unitPrice}" min="0" step="0.01"
                       onchange="updateItemTotal(${item.index})" onfocus="this.select()">
            </td>
            <td class="text-center">
                <span class="item-total fw-bold text-primary">${totalPrice.toFixed(2)}</span>
            </td>
            <td>
                <input type="date" class="form-control form-control-sm item-expiry"
                       value="${item.expiry_date}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${item.index})" title="حذف">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
    }

    window.updateUnit = function(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        const unitSelect = row.querySelector('.item-unit');
        const priceInput = row.querySelector('.item-price');
        const selectedOption = unitSelect.options[unitSelect.selectedIndex];

        const multiplier = parseFloat(selectedOption.dataset.multiplier) || 1;
        const costPrice = parseFloat(selectedOption.dataset.cost) || 0;

        const item = items.find(i => i.index == index);
        if (item) {
            item.product_unit_id = parseInt(unitSelect.value);
            item.unit_multiplier = multiplier;
            item.unit_price = costPrice;
            priceInput.value = costPrice;
        }

        updateItemTotal(index);
    };

    window.updateItemTotal = function(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const total = quantity * price;

        row.querySelector('.item-total').textContent = total.toFixed(2);

        const item = items.find(i => i.index == index);
        if (item) {
            item.quantity = quantity;
            item.unit_price = price;
            item.total_price = total;
        }

        updateSummary();
    };

    window.removeItem = function(index) {
        const row = document.querySelector(`tr[data-index="${index}"]`);
        row.remove();

        items = items.filter(i => i.index != index);

        if (items.length == 0) {
            showEmptyRow();
        }

        document.querySelectorAll('.item-row').forEach((row, i) => {
            row.querySelector('td:first-child').textContent = i + 1;
        });

        updateSummary();
    };

    function hideEmptyRow() {
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.style.display = 'none';
    }

    function showEmptyRow() {
        const emptyRow = document.getElementById('emptyRow');
        if (emptyRow) emptyRow.style.display = '';
    }

    function updateSummary() {
        const subtotal = items.reduce((sum, item) => sum + item.total_price, 0);
        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2);
        document.getElementById('itemsCountDisplay').textContent = items.length;

        const discountType = document.getElementById('discount_type').value;
        const discountValue = parseFloat(document.getElementById('discount_value').value) || 0;

        let discountAmount = 0;
        if (discountType == 'percentage') {
            discountAmount = (subtotal * discountValue) / 100;
        } else if (discountType == 'fixed') {
            discountAmount = discountValue;
        }
        document.getElementById('discountDisplay').textContent = '- ' + discountAmount.toFixed(2);

        const afterDiscount = subtotal - discountAmount;
        const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
        const taxAmount = (afterDiscount * taxRate) / 100;
        document.getElementById('taxDisplay').textContent = '+ ' + taxAmount.toFixed(2);

        const total = afterDiscount + taxAmount;
        document.getElementById('totalDisplay').textContent = total.toFixed(2);

        updateRemainingAmount();
    }

    function updateRemainingAmount() {
        const total = parseFloat(document.getElementById('totalDisplay').textContent) || 0;
        const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
        const remaining = Math.max(0, total - paidAmount);
        document.getElementById('remainingAmountDisplay').textContent = remaining.toFixed(2);
    }

    document.getElementById('discount_type').addEventListener('change', function() {
        const discountInput = document.getElementById('discount_value');
        discountInput.disabled = !this.value;
        if (!this.value) discountInput.value = 0;
        updateSummary();
    });

    document.getElementById('discount_value').addEventListener('input', updateSummary);
    document.getElementById('tax_rate').addEventListener('input', updateSummary);
    document.getElementById('paid_amount').addEventListener('input', updateRemainingAmount);

    document.getElementById('payment_type').addEventListener('change', function() {
        const cashboxField = document.getElementById('cashboxField');
        const cashboxSelect = document.getElementById('cashbox_id');
        const paidAmountField = document.getElementById('paidAmountField');
        const remainingAmountField = document.getElementById('remainingAmountField');
        const paidAmountInput = document.getElementById('paid_amount');

        if (this.value == 'cash' || this.value == 'bank') {
            cashboxField.style.display = 'block';
            paidAmountField.style.display = 'block';
            remainingAmountField.style.display = 'block';
            cashboxSelect.required = true;
            updateRemainingAmount();
        } else {
            cashboxField.style.display = 'none';
            paidAmountField.style.display = 'none';
            remainingAmountField.style.display = 'none';
            cashboxSelect.required = false;
            cashboxSelect.value = '';
            paidAmountInput.value = 0;
        }
    });

    async function submitForm(saveAsDraft) {
        const supplierId = supplierSelect.getValue();
        const purchaseDate = document.getElementById('purchase_date').value;
        const paymentType = document.getElementById('payment_type').value;
        const cashboxId = document.getElementById('cashbox_id').value;

        if (!supplierId) {
            showToast('يرجى اختيار المورد', 'warning');
            supplierSelect.focus();
            return;
        }

        if (!purchaseDate) {
            document.getElementById('purchase_date').classList.add('is-invalid');
            showToast('يرجى تحديد تاريخ الشراء', 'warning');
            return;
        }

        if ((paymentType == 'cash' || paymentType == 'bank') && !saveAsDraft && !cashboxId) {
            document.getElementById('cashbox_id').classList.add('is-invalid');
            showToast('يرجى اختيار الخزينة للدفع النقدي', 'warning');
            return;
        }

        if (items.length == 0) {
            showToast('يرجى إضافة صنف واحد على الأقل', 'warning');
            productSearch.focus();
            return;
        }

        const itemsData = [];
        document.querySelectorAll('.item-row').forEach(row => {
            itemsData.push({
                product_id: row.querySelector('.item-product-id').value,
                product_unit_id: row.querySelector('.item-unit').value,
                quantity: row.querySelector('.item-quantity').value,
                unit_price: row.querySelector('.item-price').value,
                expiry_date: row.querySelector('.item-expiry').value || null
            });
        });

        const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;

        const data = {
            supplier_id: supplierId,
            invoice_number: document.getElementById('invoice_number').value,
            purchase_date: purchaseDate,
            payment_type: paymentType,
            cashbox_id: cashboxId || null,
            paid_amount: paidAmount,
            discount_type: document.getElementById('discount_type').value || null,
            discount_value: document.getElementById('discount_value').value,
            tax_rate: document.getElementById('tax_rate').value,
            notes: document.getElementById('notes').value,
            items: itemsData,
            save_as_draft: saveAsDraft
        };

        const btn = saveAsDraft
            ? document.getElementById('saveAsDraftBtn')
            : document.getElementById('saveAndApproveBtn');

        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';

        try {
            const response = await fetch('{{ route('purchases.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحفظ',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    window.location.href = result.redirect;
                });
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }

    document.getElementById('saveAndApproveBtn').addEventListener('click', () => submitForm(false));
    document.getElementById('saveAsDraftBtn').addEventListener('click', () => submitForm(true));

    document.getElementById('purchase_date').addEventListener('change', function() {
        this.classList.remove('is-invalid');
    });

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        toastContainer.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
});
</script>
@endpush
