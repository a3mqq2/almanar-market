@extends('layouts.app')

@section('title', 'التقارير')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">التقارير</li>
@endsection

@push('styles')
<style>
    :root {
        --card-bg: #ffffff;
        --card-border: #e5e7eb;
    }
    [data-bs-theme="dark"] {
        --card-bg: #1e1e1e;
        --card-border: #2d2d2d;
    }

    .report-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        background: var(--card-bg);
        padding: 1rem;
        border-radius: 10px;
        border: 1px solid var(--card-border);
    }
    .report-tab {
        padding: 0.6rem 1.2rem;
        border: 1px solid var(--card-border);
        border-radius: 8px;
        background: transparent;
        color: var(--bs-body-color);
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .report-tab:hover {
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-color: var(--bs-primary);
    }
    .report-tab.active {
        background: var(--bs-primary, #0d6efd);
        color: #fff !important;
        border-color: var(--bs-primary, #0d6efd);
    }
    .report-tab i {
        font-size: 1.1rem;
    }

    .filters-section {
        background: var(--card-bg);
        padding: 1.25rem;
        border-radius: 10px;
        border: 1px solid var(--card-border);
        margin-bottom: 1.5rem;
    }
    .filters-section .form-label {
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
        color: var(--bs-secondary-color);
    }

    .report-content {
        background: var(--card-bg);
        border-radius: 10px;
        border: 1px solid var(--card-border);
        min-height: 400px;
    }

    .stat-card {
        background: var(--card-bg);
        border: 1px solid var(--card-border);
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
        height: 100%;
    }
    .stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        font-size: 1.5rem;
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--bs-body-color);
    }
    .stat-card .stat-label {
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
    }

    .stat-card.primary .stat-icon { background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary); }
    .stat-card.success .stat-icon { background: rgba(var(--bs-success-rgb), 0.12); color: var(--bs-success); }
    .stat-card.info .stat-icon { background: rgba(var(--bs-info-rgb), 0.12); color: var(--bs-info); }
    .stat-card.warning .stat-icon { background: rgba(var(--bs-warning-rgb), 0.12); color: var(--bs-warning); }
    .stat-card.danger .stat-icon { background: rgba(var(--bs-danger-rgb), 0.12); color: var(--bs-danger); }

    .export-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .report-table {
        width: 100%;
    }
    .report-table th {
        background: var(--bs-tertiary-bg);
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.75rem;
        white-space: nowrap;
        color: var(--bs-body-color);
    }
    .report-table td {
        padding: 0.65rem 0.75rem;
        font-size: 0.9rem;
        vertical-align: middle;
        color: var(--bs-body-color);
    }
    .report-table tbody tr:hover {
        background: rgba(var(--bs-primary-rgb), 0.05);
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(var(--bs-body-bg-rgb), 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        z-index: 10;
    }

    .empty-state {
        padding: 3rem;
        text-align: center;
        color: var(--bs-secondary-color);
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>
@endpush

@section('content')
<!-- Report Tabs -->
<div class="report-tabs">
    <button class="report-tab active" data-report="sales">
        <i class="ti ti-shopping-cart"></i>المبيعات
    </button>
    <button class="report-tab" data-report="profit">
        <i class="ti ti-chart-line"></i>الأرباح
    </button>
    <button class="report-tab" data-report="payment-methods">
        <i class="ti ti-credit-card"></i>طرق الدفع
    </button>
    <button class="report-tab" data-report="inventory">
        <i class="ti ti-packages"></i>المخزون
    </button>
    <button class="report-tab" data-report="expenses">
        <i class="ti ti-receipt"></i>المصروفات
    </button>
    <button class="report-tab" data-report="shifts">
        <i class="ti ti-clock"></i>الشيفتات
    </button>
    <button class="report-tab" data-report="customers">
        <i class="ti ti-users"></i>الزبائن
    </button>
    <button class="report-tab" data-report="suppliers">
        <i class="ti ti-truck"></i>الموردين
    </button>
</div>

<!-- Global Filters -->
<div class="filters-section">
    <form id="filtersForm">
        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label">من تاريخ</label>
                <input type="date" class="form-control" id="date_from" name="date_from"
                       value="{{ today()->subDays(30)->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">إلى تاريخ</label>
                <input type="date" class="form-control" id="date_to" name="date_to"
                       value="{{ today()->format('Y-m-d') }}">
            </div>
            <div class="col-md-2" id="shiftFilterGroup">
                <label class="form-label">الشيفت</label>
                <select class="form-select" id="shift_id" name="shift_id">
                    <option value="">الكل</option>
                    @foreach($shifts as $shift)
                        <option value="{{ $shift->id }}">
                            #{{ $shift->id }} - {{ $shift->user->name ?? '-' }}
                            ({{ $shift->opened_at->format('m/d H:i') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="cashierFilterGroup">
                <label class="form-label">الكاشير</label>
                <select class="form-select" id="cashier_id" name="cashier_id">
                    <option value="">الكل</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="cashboxFilterGroup">
                <label class="form-label">الخزينة</label>
                <select class="form-select" id="cashbox_id" name="cashbox_id">
                    <option value="">الكل</option>
                    @foreach($cashboxes as $cashbox)
                        <option value="{{ $cashbox->id }}">{{ $cashbox->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2" id="paymentMethodFilterGroup">
                <label class="form-label">طريقة الدفع</label>
                <select class="form-select" id="payment_method_id" name="payment_method_id">
                    <option value="">الكل</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <!-- Additional filters per report type -->
            <div class="col-md-2 d-none" id="stockStatusFilterGroup">
                <label class="form-label">حالة المخزون</label>
                <select class="form-select" id="stock_status" name="stock_status">
                    <option value="">الكل</option>
                    <option value="low_stock">مخزون منخفض</option>
                    <option value="out_of_stock">نفد المخزون</option>
                    <option value="expiring">قارب انتهاء الصلاحية</option>
                    <option value="expired">منتهي الصلاحية</option>
                </select>
            </div>
            <div class="col-md-2 d-none" id="balanceStatusFilterGroup">
                <label class="form-label">حالة الرصيد</label>
                <select class="form-select" id="balance_status" name="balance_status">
                    <option value="">الكل</option>
                    <option value="with_balance">لديه رصيد</option>
                    <option value="no_balance">بدون رصيد</option>
                </select>
            </div>
            <div class="col-md-3 d-none" id="searchFilterGroup">
                <label class="form-label">بحث</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="اسم أو رقم هاتف...">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="ti ti-search me-1"></i>عرض التقرير
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Report Content -->
<div class="report-content position-relative" id="reportContent">
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
        </div>
    </div>

    <div class="p-4">
        <!-- Export Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0" id="reportTitle">تقرير المبيعات</h5>
            <div class="export-buttons">
                <button class="btn btn-outline-secondary btn-sm" id="btnPrint">
                    <i class="ti ti-printer me-1"></i>طباعة
                </button>
                <button class="btn btn-outline-success btn-sm" id="btnExportExcel">
                    <i class="ti ti-file-type-xls me-1"></i>Excel
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4" id="statsCards">
            <!-- Filled dynamically -->
        </div>

        <!-- Report Data -->
        <div id="reportData">
            <div class="empty-state">
                <i class="ti ti-report-analytics"></i>
                <h5>اختر التقرير واضغط "عرض التقرير"</h5>
                <p class="text-muted">حدد الفلاتر المطلوبة ثم اضغط على زر عرض التقرير</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentReport = 'sales';
    let reportData = null;

    const reportTitles = {
        'sales': 'تقرير المبيعات',
        'profit': 'تقرير الأرباح',
        'payment-methods': 'تقرير طرق الدفع',
        'inventory': 'تقرير المخزون',
        'expenses': 'تقرير المصروفات',
        'shifts': 'تقرير الشيفتات',
        'customers': 'تقرير الزبائن',
        'suppliers': 'تقرير الموردين'
    };

    // Report tab switching
    document.querySelectorAll('.report-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.report-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentReport = this.dataset.report;
            document.getElementById('reportTitle').textContent = reportTitles[currentReport];
            updateFiltersVisibility();
            clearReportData();
        });
    });

    // Update filters visibility based on report type
    function updateFiltersVisibility() {
        // Reset all
        document.querySelectorAll('#shiftFilterGroup, #cashierFilterGroup, #cashboxFilterGroup, #paymentMethodFilterGroup').forEach(el => {
            el.classList.remove('d-none');
        });
        document.getElementById('stockStatusFilterGroup').classList.add('d-none');
        document.getElementById('balanceStatusFilterGroup').classList.add('d-none');
        document.getElementById('searchFilterGroup').classList.add('d-none');

        switch(currentReport) {
            case 'inventory':
                document.getElementById('shiftFilterGroup').classList.add('d-none');
                document.getElementById('cashierFilterGroup').classList.add('d-none');
                document.getElementById('cashboxFilterGroup').classList.add('d-none');
                document.getElementById('paymentMethodFilterGroup').classList.add('d-none');
                document.getElementById('stockStatusFilterGroup').classList.remove('d-none');
                document.getElementById('searchFilterGroup').classList.remove('d-none');
                break;
            case 'customers':
            case 'suppliers':
                document.getElementById('shiftFilterGroup').classList.add('d-none');
                document.getElementById('cashierFilterGroup').classList.add('d-none');
                document.getElementById('cashboxFilterGroup').classList.add('d-none');
                document.getElementById('paymentMethodFilterGroup').classList.add('d-none');
                document.getElementById('balanceStatusFilterGroup').classList.remove('d-none');
                document.getElementById('searchFilterGroup').classList.remove('d-none');
                break;
            case 'expenses':
                document.getElementById('paymentMethodFilterGroup').classList.add('d-none');
                document.getElementById('shiftFilterGroup').classList.add('d-none');
                break;
        }
    }

    function clearReportData() {
        document.getElementById('statsCards').innerHTML = '';
        document.getElementById('reportData').innerHTML = `
            <div class="empty-state">
                <i class="ti ti-report-analytics"></i>
                <h5>اختر التقرير واضغط "عرض التقرير"</h5>
                <p class="text-muted">حدد الفلاتر المطلوبة ثم اضغط على زر عرض التقرير</p>
            </div>
        `;
    }

    // Form submission
    document.getElementById('filtersForm').addEventListener('submit', function(e) {
        e.preventDefault();
        loadReport();
    });

    function getFilters() {
        const form = document.getElementById('filtersForm');
        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
            if (value) params.append(key, value);
        }

        return params.toString();
    }

    function loadReport() {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.remove('d-none');

        const filters = getFilters();
        const url = `/reports/${currentReport}/generate?${filters}`;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            overlay.classList.add('d-none');
            if (data.success) {
                reportData = data.data;
                renderReport(currentReport, data.data);
            } else {
                showToast('حدث خطأ في تحميل التقرير', 'error');
            }
        })
        .catch(error => {
            overlay.classList.add('d-none');
            console.error('Error:', error);
            showToast('حدث خطأ في الاتصال', 'error');
        });
    }

    function renderReport(type, data) {
        switch(type) {
            case 'sales':
                renderSalesReport(data);
                break;
            case 'profit':
                renderProfitReport(data);
                break;
            case 'payment-methods':
                renderPaymentMethodsReport(data);
                break;
            case 'inventory':
                renderInventoryReport(data);
                break;
            case 'expenses':
                renderExpensesReport(data);
                break;
            case 'shifts':
                renderShiftsReport(data);
                break;
            case 'customers':
                renderCustomersReport(data);
                break;
            case 'suppliers':
                renderSuppliersReport(data);
                break;
        }
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
    }

    function formatInteger(num) {
        return new Intl.NumberFormat('en-US').format(num || 0);
    }

    // Sales Report
    function renderSalesReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-2 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-currency-dollar"></i></div>
                    <div class="stat-value">${formatNumber(s.total_sales)}</div>
                    <div class="stat-label">إجمالي المبيعات</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                    <div class="stat-value">${formatInteger(s.invoice_count)}</div>
                    <div class="stat-label">عدد الفواتير</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-package"></i></div>
                    <div class="stat-value">${formatInteger(s.items_count)}</div>
                    <div class="stat-label">المنتجات المباعة</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-calculator"></i></div>
                    <div class="stat-value">${formatNumber(s.average_invoice)}</div>
                    <div class="stat-label">متوسط الفاتورة</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-discount"></i></div>
                    <div class="stat-value">${formatNumber(s.total_discount)}</div>
                    <div class="stat-label">الخصومات</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-arrow-back-up"></i></div>
                    <div class="stat-value">${formatNumber(s.total_returns || 0)}</div>
                    <div class="stat-label">المرتجعات</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // By Date Table
        if (data.by_date && data.by_date.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-calendar me-2"></i>المبيعات حسب التاريخ</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الفواتير</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_date.map(row => `
                                    <tr>
                                        <td>${row.date}</td>
                                        <td>${formatInteger(row.invoice_count)}</td>
                                        <td>${formatNumber(row.total_sales)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Top Products
        if (data.top_products && data.top_products.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-star me-2"></i>أكثر المنتجات مبيعاً</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.top_products.map(row => `
                                    <tr>
                                        <td>${row.product_name}</td>
                                        <td>${formatInteger(row.quantity)}</td>
                                        <td>${formatNumber(row.revenue)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Profit Report
    function renderProfitReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-2 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-currency-dollar"></i></div>
                    <div class="stat-value">${formatNumber(s.total_revenue)}</div>
                    <div class="stat-label">الإيرادات</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-shopping-cart"></i></div>
                    <div class="stat-value">${formatNumber(s.total_cost)}</div>
                    <div class="stat-label">التكلفة</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-trending-up"></i></div>
                    <div class="stat-value">${formatNumber(s.gross_profit)}</div>
                    <div class="stat-label">الربح الإجمالي</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                    <div class="stat-value">${formatNumber(s.total_expenses)}</div>
                    <div class="stat-label">المصروفات</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card ${s.net_profit >= 0 ? 'success' : 'danger'}">
                    <div class="stat-icon"><i class="ti ti-chart-line"></i></div>
                    <div class="stat-value">${formatNumber(s.net_profit)}</div>
                    <div class="stat-label">صافي الربح</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-percentage"></i></div>
                    <div class="stat-value">${formatNumber(s.profit_margin)}%</div>
                    <div class="stat-label">هامش الربح</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // By Date
        if (data.by_date && data.by_date.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-calendar me-2"></i>الأرباح حسب التاريخ</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الإيرادات</th>
                                    <th>المصروفات</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_date.map(row => `
                                    <tr>
                                        <td>${row.date}</td>
                                        <td>${formatNumber(row.revenue)}</td>
                                        <td>${formatNumber(row.expenses)}</td>
                                        <td class="${row.profit >= 0 ? 'text-success' : 'text-danger'}">${formatNumber(row.profit)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Top Profitable Products
        if (data.by_product && data.by_product.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-star me-2"></i>أكثر المنتجات ربحية</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الكمية</th>
                                    <th>الربح</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_product.map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td>${formatInteger(row.quantity)}</td>
                                        <td class="text-success">${formatNumber(row.profit)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Payment Methods Report
    function renderPaymentMethodsReport(data) {
        const s = data.summary;
        const methodCount = data.by_method ? data.by_method.filter(m => m.total_amount > 0).length : 0;
        const avgTransaction = s.total_count > 0 ? s.total_amount / s.total_count : 0;

        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-3 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-currency-dollar"></i></div>
                    <div class="stat-value">${formatNumber(s.total_amount)}</div>
                    <div class="stat-label">إجمالي المبلغ</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                    <div class="stat-value">${formatInteger(s.total_count)}</div>
                    <div class="stat-label">عدد المعاملات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-credit-card"></i></div>
                    <div class="stat-value">${formatInteger(methodCount)}</div>
                    <div class="stat-label">طرق الدفع المستخدمة</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-calculator"></i></div>
                    <div class="stat-value">${formatNumber(avgTransaction)}</div>
                    <div class="stat-label">متوسط المعاملة</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // By Method
        if (data.by_method && data.by_method.length > 0) {
            const totalAmount = s.total_amount || 1;
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-credit-card me-2"></i>حسب طريقة الدفع</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>طريقة الدفع</th>
                                    <th>المعاملات</th>
                                    <th>المبلغ</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_method.map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td>${formatInteger(row.transaction_count)}</td>
                                        <td>${formatNumber(row.total_amount)}</td>
                                        <td>${formatNumber((row.total_amount / totalAmount) * 100)}%</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // By Cashbox
        if (data.by_cashbox && data.by_cashbox.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-building-bank me-2"></i>حسب الخزينة</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>الخزينة</th>
                                    <th>المعاملات</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_cashbox.map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td>${formatInteger(row.transaction_count)}</td>
                                        <td>${formatNumber(row.total_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Inventory Report
    function renderInventoryReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-2 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-package"></i></div>
                    <div class="stat-value">${formatInteger(s.total_products)}</div>
                    <div class="stat-label">إجمالي المنتجات</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-currency-dollar"></i></div>
                    <div class="stat-value">${formatNumber(s.total_stock_value)}</div>
                    <div class="stat-label">قيمة المخزون</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-alert-triangle"></i></div>
                    <div class="stat-value">${formatInteger(s.low_stock_count)}</div>
                    <div class="stat-label">مخزون منخفض</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-package-off"></i></div>
                    <div class="stat-value">${formatInteger(s.out_of_stock_count)}</div>
                    <div class="stat-label">نفد المخزون</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-clock"></i></div>
                    <div class="stat-value">${formatInteger(s.expiring_count)}</div>
                    <div class="stat-label">قارب انتهاء الصلاحية</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-calendar-off"></i></div>
                    <div class="stat-value">${formatInteger(s.expired_count)}</div>
                    <div class="stat-label">منتهي الصلاحية</div>
                </div>
            </div>
        `;

        let html = '';
        if (data.products && data.products.length > 0) {
            html = `
                <h6 class="mb-3"><i class="ti ti-list me-2"></i>قائمة المنتجات</h6>
                <div class="table-responsive">
                    <table class="table report-table table-bordered">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الباركود</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>متوسط التكلفة</th>
                                <th>القيمة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.products.map(row => `
                                <tr>
                                    <td>${row.name}</td>
                                    <td>${row.barcode || '-'}</td>
                                    <td>${row.unit || '-'}</td>
                                    <td>${formatNumber(row.stock)}</td>
                                    <td>${formatNumber(row.avg_cost)}</td>
                                    <td>${formatNumber(row.value)}</td>
                                    <td>${getStockStatusBadge(row.status)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html = '<div class="empty-state"><i class="ti ti-package-off"></i><h5>لا توجد منتجات</h5></div>';
        }

        document.getElementById('reportData').innerHTML = html;
    }

    function getStockStatusBadge(status) {
        const badges = {
            'ok': '<span class="badge bg-success">متوفر</span>',
            'low_stock': '<span class="badge bg-warning">منخفض</span>',
            'out_of_stock': '<span class="badge bg-danger">نفد</span>',
            'expiring': '<span class="badge bg-warning">قارب الانتهاء</span>',
            'expired': '<span class="badge bg-danger">منتهي</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">-</span>';
    }

    // Expenses Report
    function renderExpensesReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-3 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                    <div class="stat-value">${formatNumber(s.total_amount)}</div>
                    <div class="stat-label">إجمالي المصروفات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-list"></i></div>
                    <div class="stat-value">${formatInteger(s.expense_count)}</div>
                    <div class="stat-label">عدد المصروفات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-category"></i></div>
                    <div class="stat-value">${formatInteger(s.category_count)}</div>
                    <div class="stat-label">عدد التصنيفات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-calculator"></i></div>
                    <div class="stat-value">${formatNumber(s.average_expense)}</div>
                    <div class="stat-label">متوسط المصروف</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // By Category
        if (data.by_category && data.by_category.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-category me-2"></i>حسب التصنيف</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>التصنيف</th>
                                    <th>العدد</th>
                                    <th>المبلغ</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_category.map(row => `
                                    <tr>
                                        <td>${row.category}</td>
                                        <td>${formatInteger(row.count)}</td>
                                        <td>${formatNumber(row.total)}</td>
                                        <td>${formatNumber(row.percentage)}%</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // By Date
        if (data.by_date && data.by_date.length > 0) {
            html += `
                <div class="col-md-6">
                    <h6 class="mb-3"><i class="ti ti-calendar me-2"></i>حسب التاريخ</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>العدد</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.by_date.map(row => `
                                    <tr>
                                        <td>${row.date}</td>
                                        <td>${formatInteger(row.count)}</td>
                                        <td>${formatNumber(row.total)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Shifts Report
    function renderShiftsReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-3 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-clock"></i></div>
                    <div class="stat-value">${formatInteger(s.total_shifts)}</div>
                    <div class="stat-label">إجمالي الشيفتات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-currency-dollar"></i></div>
                    <div class="stat-value">${formatNumber(s.total_sales)}</div>
                    <div class="stat-label">إجمالي المبيعات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                    <div class="stat-value">${formatNumber(s.total_expenses)}</div>
                    <div class="stat-label">إجمالي المصروفات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-calculator"></i></div>
                    <div class="stat-value">${formatNumber(s.average_sales_per_shift)}</div>
                    <div class="stat-label">متوسط المبيعات/شيفت</div>
                </div>
            </div>
        `;

        let html = '';
        if (data.shifts && data.shifts.length > 0) {
            html = `
                <h6 class="mb-3"><i class="ti ti-list me-2"></i>قائمة الشيفتات</h6>
                <div class="table-responsive">
                    <table class="table report-table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الكاشير</th>
                                <th>البداية</th>
                                <th>النهاية</th>
                                <th>المبيعات</th>
                                <th>المصروفات</th>
                                <th>الفرق</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.shifts.map(row => `
                                <tr>
                                    <td>${row.id}</td>
                                    <td>${row.cashier}</td>
                                    <td>${row.opened_at}</td>
                                    <td>${row.closed_at || '-'}</td>
                                    <td class="text-success">${formatNumber(row.total_sales)}</td>
                                    <td class="text-danger">${formatNumber(row.total_expenses)}</td>
                                    <td class="${row.difference >= 0 ? 'text-success' : 'text-danger'}">${formatNumber(row.difference)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html = '<div class="empty-state"><i class="ti ti-clock-off"></i><h5>لا توجد شيفتات</h5></div>';
        }

        document.getElementById('reportData').innerHTML = html;
    }

    // Customers Report
    function renderCustomersReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-3 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-users"></i></div>
                    <div class="stat-value">${formatInteger(s.total_customers)}</div>
                    <div class="stat-label">إجمالي الزبائن</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-wallet"></i></div>
                    <div class="stat-value">${formatNumber(s.total_balance)}</div>
                    <div class="stat-label">إجمالي الأرصدة</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card success">
                    <div class="stat-icon"><i class="ti ti-shopping-cart"></i></div>
                    <div class="stat-value">${formatNumber(s.total_purchases)}</div>
                    <div class="stat-label">إجمالي المشتريات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-user-check"></i></div>
                    <div class="stat-value">${formatInteger(s.with_balance_count)}</div>
                    <div class="stat-label">لديهم رصيد</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // Customers List
        if (data.customers && data.customers.length > 0) {
            html += `
                <div class="col-md-8">
                    <h6 class="mb-3"><i class="ti ti-list me-2"></i>قائمة الزبائن</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>الرصيد</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المشتريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.customers.slice(0, 50).map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td>${row.phone || '-'}</td>
                                        <td class="${row.current_balance != 0 ? 'text-warning fw-bold' : ''}">${formatNumber(row.current_balance)}</td>
                                        <td>${formatInteger(row.invoice_count)}</td>
                                        <td>${formatNumber(row.total_purchases)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Top Customers
        if (data.top_customers && data.top_customers.length > 0) {
            html += `
                <div class="col-md-4">
                    <h6 class="mb-3"><i class="ti ti-star me-2"></i>أفضل الزبائن</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>المشتريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.top_customers.map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td class="text-success">${formatNumber(row.total_purchases)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Suppliers Report
    function renderSuppliersReport(data) {
        const s = data.summary;
        document.getElementById('statsCards').innerHTML = `
            <div class="col-md-3 col-6">
                <div class="stat-card primary">
                    <div class="stat-icon"><i class="ti ti-truck"></i></div>
                    <div class="stat-value">${formatInteger(s.total_suppliers)}</div>
                    <div class="stat-label">إجمالي الموردين</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card danger">
                    <div class="stat-icon"><i class="ti ti-wallet"></i></div>
                    <div class="stat-value">${formatNumber(s.total_balance)}</div>
                    <div class="stat-label">إجمالي الأرصدة</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="ti ti-shopping-cart"></i></div>
                    <div class="stat-value">${formatNumber(s.total_purchases)}</div>
                    <div class="stat-label">إجمالي المشتريات</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card info">
                    <div class="stat-icon"><i class="ti ti-user-check"></i></div>
                    <div class="stat-value">${formatInteger(s.with_balance_count)}</div>
                    <div class="stat-label">لديهم رصيد</div>
                </div>
            </div>
        `;

        let html = '<div class="row g-4">';

        // Suppliers List
        if (data.suppliers && data.suppliers.length > 0) {
            html += `
                <div class="col-md-8">
                    <h6 class="mb-3"><i class="ti ti-list me-2"></i>قائمة الموردين</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>الهاتف</th>
                                    <th>الرصيد</th>
                                    <th>عدد المشتريات</th>
                                    <th>إجمالي المشتريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.suppliers.slice(0, 50).map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td>${row.phone || '-'}</td>
                                        <td class="${row.current_balance != 0 ? 'text-danger fw-bold' : ''}">${formatNumber(row.current_balance)}</td>
                                        <td>${formatInteger(row.purchase_count)}</td>
                                        <td>${formatNumber(row.total_purchases)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Top Suppliers
        if (data.top_suppliers && data.top_suppliers.length > 0) {
            html += `
                <div class="col-md-4">
                    <h6 class="mb-3"><i class="ti ti-star me-2"></i>أكبر الموردين</h6>
                    <div class="table-responsive">
                        <table class="table report-table table-bordered">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>المشتريات</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.top_suppliers.map(row => `
                                    <tr>
                                        <td>${row.name}</td>
                                        <td class="text-warning">${formatNumber(row.total_purchases)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        html += '</div>';
        document.getElementById('reportData').innerHTML = html;
    }

    // Export buttons
    document.getElementById('btnPrint').addEventListener('click', function() {
        const filters = getFilters();
        window.open(`${window.__baseUrl}/reports/${currentReport}/print?${filters}`, '_blank');
    });

    document.getElementById('btnExportPdf').addEventListener('click', function() {
        const filters = getFilters();
        window.location.href = `${window.__baseUrl}/reports/${currentReport}/export/pdf?${filters}`;
    });

    document.getElementById('btnExportExcel').addEventListener('click', function() {
        const filters = getFilters();
        window.location.href = `${window.__baseUrl}/reports/${currentReport}/export/excel?${filters}`;
    });

    // Toast notification
    function showToast(message, type = 'success') {
        if (typeof Swal != 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            alert(message);
        }
    }

    // Initialize
    updateFiltersVisibility();
});
</script>
@endpush
