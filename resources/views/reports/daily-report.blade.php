@extends('layouts.app')

@section('title', 'التقرير اليومي')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">التقرير اليومي</li>
@endsection

@push('styles')
<style>
    .report-wrapper {
        background: var(--bs-body-bg);
    }

    .main-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    [data-bs-theme="dark"] .main-card {
        background: var(--bs-card-bg);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .main-card-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        background: var(--bs-tertiary-bg);
    }
    .main-card-header h5 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .main-card-body {
        padding: 1.5rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.25rem;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
    [data-bs-theme="dark"] .stat-card {
        background: var(--bs-tertiary-bg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .stat-card .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-card .stat-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    .stat-card.success .stat-icon { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success); }
    .stat-card.primary .stat-icon { background: rgba(var(--bs-primary-rgb), 0.15); color: var(--bs-primary); }
    .stat-card.info .stat-icon { background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info); }
    .stat-card.warning .stat-icon { background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning); }
    .stat-card.danger .stat-icon { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger); }

    .section-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        margin-bottom: 1.5rem;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    [data-bs-theme="dark"] .section-card {
        background: var(--bs-card-bg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .section-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bs-tertiary-bg);
    }
    .section-header h6 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-body {
        padding: 0;
    }

    .report-table {
        width: 100%;
        margin: 0;
    }
    .report-table thead {
        background: var(--bs-tertiary-bg);
    }
    .report-table th {
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        white-space: nowrap;
    }
    .report-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .report-table tbody tr:last-child td {
        border-bottom: none;
    }
    .report-table tbody tr:hover {
        background: var(--bs-tertiary-bg);
    }
    .report-table tfoot {
        background: var(--bs-tertiary-bg);
        font-weight: 600;
    }
    .report-table tfoot td {
        border-top: 2px solid var(--bs-border-color);
        border-bottom: none;
    }

    .amount-positive { color: var(--bs-success); font-weight: 600; }
    .amount-negative { color: var(--bs-danger); font-weight: 600; }
    .amount-neutral { color: var(--bs-primary); font-weight: 600; }

    .status-badge {
        padding: 0.3rem 0.6rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-ok { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success); }
    .status-low_stock { background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning); }
    .status-out_of_stock { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger); }
    .status-expiring_soon { background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning); }
    .status-expired { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger); }

    .payment-method-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    .payment-method-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    [data-bs-theme="dark"] .payment-method-card {
        background: var(--bs-tertiary-bg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }
    .payment-method-card .method-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin: 0 auto 0.75rem;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
    }
    .payment-method-card .method-name {
        font-size: 0.9rem;
        color: var(--bs-body-color);
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .payment-method-card .method-amount {
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--bs-success);
    }
    .payment-method-card .method-count {
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
        margin-top: 0.25rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--bs-secondary-color);
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    [data-bs-theme="dark"] .empty-state {
        background: var(--bs-card-bg);
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }
    .empty-state h5 {
        color: var(--bs-body-color);
        margin-bottom: 0.5rem;
    }

    .filter-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    [data-bs-theme="dark"] .filter-card {
        background: var(--bs-card-bg);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .tabs-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    }
    [data-bs-theme="dark"] .tabs-card {
        background: var(--bs-card-bg);
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    .nav-tabs-report {
        border-bottom: 1px solid var(--bs-border-color);
        gap: 0;
        background: var(--bs-tertiary-bg);
        padding: 0.5rem 0.5rem 0;
    }
    .nav-tabs-report .nav-link {
        border: none;
        background: transparent;
        color: var(--bs-secondary-color);
        padding: 0.85rem 1.5rem;
        font-weight: 500;
        position: relative;
        border-radius: 8px 8px 0 0;
        margin-bottom: -1px;
    }
    .nav-tabs-report .nav-link:hover {
        color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.05);
    }
    .nav-tabs-report .nav-link.active {
        color: var(--bs-primary);
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-bottom-color: var(--bs-body-bg);
    }
    [data-bs-theme="dark"] .nav-tabs-report .nav-link.active {
        background: var(--bs-card-bg);
        border-bottom-color: var(--bs-card-bg);
    }

    .tab-content-wrapper {
        padding: 1.5rem;
    }

    @media print {
        .no-print { display: none !important; }
        .stat-card, .section-card, .filter-card, .tabs-card, .payment-method-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>
@endpush

@section('content')
<div class="report-wrapper">
    <div class="filter-card no-print">
        <div class="d-flex align-items-center gap-2 mb-3">
            <i class="ti ti-filter fs-5 text-primary"></i>
            <h6 class="mb-0 fw-semibold">فلترة التقرير</h6>
        </div>
        <form id="reportForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-medium">التاريخ</label>
                <input type="date" class="form-control" id="reportDate" name="date" value="{{ $date }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">الكاشير</label>
                <select class="form-select" id="cashierSelect" name="cashier_id">
                    <option value="">الكل</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" {{ $cashierId == $cashier->id ? 'selected' : '' }}>{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary px-4" id="generateBtn">
                    <i class="ti ti-report-analytics me-1"></i>عرض التقرير
                </button>
            </div>
            <div class="col-md-auto">
                <a href="#" class="btn btn-outline-secondary px-4" id="printBtn" target="_blank">
                    <i class="ti ti-printer me-1"></i>طباعة
                </a>
            </div>
        </form>
    </div>

    <div id="reportContent">
        @if($reportData)
            @include('reports.partials.daily-report-content', ['data' => $reportData])
        @else
            <div class="empty-state">
                <i class="ti ti-report-off d-block"></i>
                <h5>اختر التاريخ لعرض التقرير</h5>
                <p class="mb-0">حدد التاريخ واضغط على "عرض التقرير" لإنشاء التقرير اليومي</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reportForm');
    const dateInput = document.getElementById('reportDate');
    const cashierSelect = document.getElementById('cashierSelect');
    const generateBtn = document.getElementById('generateBtn');
    const printBtn = document.getElementById('printBtn');
    const reportContent = document.getElementById('reportContent');

    function updatePrintLink() {
        const date = dateInput.value;
        const cashierId = cashierSelect.value;
        let url = `{{ route('reports.daily-report.print') }}?date=${date}`;
        if (cashierId) {
            url += `&cashier_id=${cashierId}`;
        }
        printBtn.href = url;
    }

    updatePrintLink();
    dateInput.addEventListener('change', updatePrintLink);
    cashierSelect.addEventListener('change', updatePrintLink);

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const date = dateInput.value;
        const cashierId = cashierSelect.value;

        generateBtn.disabled = true;
        generateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري التحميل...';

        try {
            const params = new URLSearchParams({ date });
            if (cashierId) params.append('cashier_id', cashierId);

            const response = await fetch(`{{ route('reports.daily-report.generate') }}?${params}`);
            const result = await response.json();

            if (result.success) {
                renderReport(result.data);
            } else {
                reportContent.innerHTML = `
                    <div class="alert alert-danger text-center">
                        <i class="ti ti-alert-circle me-2"></i>حدث خطأ في تحميل التقرير
                    </div>
                `;
            }
        } catch (error) {
            reportContent.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="ti ti-alert-circle me-2"></i>حدث خطأ في الاتصال
                </div>
            `;
        }

        generateBtn.disabled = false;
        generateBtn.innerHTML = '<i class="ti ti-report-analytics me-1"></i>عرض التقرير';
    });

    function renderReport(data) {
        const summary = data.summary;
        const soldItems = data.sold_items;
        const paymentMethods = data.payment_methods;
        const inventory = data.inventory_status;
        const returns = data.returns;

        let html = `
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card success">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="stat-label">إجمالي المبيعات</div>
                                <div class="stat-value">${formatNumber(summary.total_sales)}</div>
                            </div>
                            <div class="stat-icon"><i class="ti ti-cash"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card warning">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="stat-label">إجمالي التكلفة</div>
                                <div class="stat-value">${formatNumber(summary.total_cost)}</div>
                            </div>
                            <div class="stat-icon"><i class="ti ti-receipt"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card primary">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="stat-label">إجمالي الربح</div>
                                <div class="stat-value">${formatNumber(summary.total_profit)}</div>
                                <div class="text-muted small">${summary.profit_margin}%</div>
                            </div>
                            <div class="stat-icon"><i class="ti ti-trending-up"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card info">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="stat-label">عدد الفواتير</div>
                                <div class="stat-value">${summary.invoice_count}</div>
                                <div class="text-muted small">${formatNumber(summary.items_count)} قطعة</div>
                            </div>
                            <div class="stat-icon"><i class="ti ti-file-invoice"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: rgba(var(--bs-secondary-rgb), 0.15); color: var(--bs-secondary);">
                                <i class="ti ti-discount-2"></i>
                            </div>
                            <div>
                                <div class="stat-label">إجمالي الخصم</div>
                                <div class="fs-4 fw-bold">${formatNumber(summary.total_discount)}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info);">
                                <i class="ti ti-calculator"></i>
                            </div>
                            <div>
                                <div class="stat-label">متوسط الفاتورة</div>
                                <div class="fs-4 fw-bold">${formatNumber(summary.average_invoice)}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card ${returns.count > 0 ? 'danger' : ''}">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger);">
                                <i class="ti ti-receipt-refund"></i>
                            </div>
                            <div>
                                <div class="stat-label">المرتجعات</div>
                                <div class="fs-4 fw-bold">${formatNumber(returns.total_amount)}</div>
                                <div class="text-muted small">${returns.count} عملية</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        html += `
            <div class="tabs-card">
                <ul class="nav nav-tabs nav-tabs-report" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPayments">
                            <i class="ti ti-credit-card me-1"></i>طرق الدفع
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabItems">
                            <i class="ti ti-package me-1"></i>الأصناف المباعة
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInventory">
                            <i class="ti ti-alert-triangle me-1"></i>تنبيهات المخزون
                        </button>
                    </li>
                    ${returns.count > 0 ? `
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabReturns">
                            <i class="ti ti-receipt-refund me-1"></i>المرتجعات
                        </button>
                    </li>
                    ` : ''}
                </ul>

                <div class="tab-content tab-content-wrapper">
        `;

        html += `
            <div class="tab-pane fade show active" id="tabPayments">
                <div class="row g-3">
                    ${paymentMethods.map(method => `
                        <div class="col-md-3 col-6">
                            <div class="payment-method-card">
                                <div class="method-icon"><i class="ti ti-credit-card"></i></div>
                                <div class="method-name">${method.name}</div>
                                <div class="method-amount">${formatNumber(method.total_amount)}</div>
                                <div class="method-count">${method.transaction_count} عملية</div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        html += `
            <div class="tab-pane fade" id="tabItems">
                <div class="section-card">
                    <div class="section-body">
                        ${soldItems.length > 0 ? `
                        <div class="table-responsive">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الصنف</th>
                                        <th>الباركود</th>
                                        <th>الكمية</th>
                                        <th>إجمالي البيع</th>
                                        <th>التكلفة</th>
                                        <th>الربح</th>
                                        <th>هامش الربح</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${soldItems.map((item, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td class="fw-medium">${item.product_name}</td>
                                        <td class="text-muted">${item.barcode}</td>
                                        <td>${item.quantity}</td>
                                        <td class="amount-neutral">${formatNumber(item.total_revenue)}</td>
                                        <td>${formatNumber(item.total_cost)}</td>
                                        <td class="${item.profit >= 0 ? 'amount-positive' : 'amount-negative'}">${formatNumber(item.profit)}</td>
                                        <td>${item.profit_margin}%</td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">المجموع</td>
                                        <td class="amount-neutral">${formatNumber(summary.total_sales)}</td>
                                        <td>${formatNumber(summary.total_cost)}</td>
                                        <td class="amount-positive">${formatNumber(summary.total_profit)}</td>
                                        <td>${summary.profit_margin}%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        ` : `
                        <div class="empty-state">
                            <i class="ti ti-package-off d-block"></i>
                            <p class="mb-0">لا توجد مبيعات في هذا اليوم</p>
                        </div>
                        `}
                    </div>
                </div>
            </div>
        `;

        html += `
            <div class="tab-pane fade" id="tabInventory">
                <div class="section-card">
                    <div class="section-body">
                        ${inventory.length > 0 ? `
                        <div class="table-responsive">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>الصنف</th>
                                        <th>الباركود</th>
                                        <th>المخزون الحالي</th>
                                        <th>تاريخ الانتهاء</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${inventory.map(item => `
                                    <tr>
                                        <td class="fw-medium">${item.product_name}</td>
                                        <td class="text-muted">${item.barcode || '-'}</td>
                                        <td>${item.current_stock}</td>
                                        <td>${item.expiry_date || '-'}</td>
                                        <td><span class="status-badge status-${item.status}">${getStatusArabic(item.status)}</span></td>
                                    </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                        ` : `
                        <div class="empty-state">
                            <i class="ti ti-check d-block" style="color: var(--bs-success);"></i>
                            <p class="mb-0">لا توجد تنبيهات للمخزون</p>
                        </div>
                        `}
                    </div>
                </div>
            </div>
        `;

        if (returns.count > 0) {
            html += `
                <div class="tab-pane fade" id="tabReturns">
                    <div class="section-card">
                        <div class="section-body">
                            <div class="table-responsive">
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>رقم المرتجع</th>
                                            <th>رقم الفاتورة</th>
                                            <th>الصنف</th>
                                            <th>الكمية</th>
                                            <th>المبلغ</th>
                                            <th>السبب</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${returns.items.map(item => `
                                        <tr>
                                            <td class="fw-medium">${item.return_number}</td>
                                            <td>${item.invoice_number}</td>
                                            <td>${item.product_name}</td>
                                            <td>${item.quantity}</td>
                                            <td class="amount-negative">${formatNumber(item.amount)}</td>
                                            <td>${item.reason}</td>
                                        </tr>
                                        `).join('')}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4">إجمالي المرتجعات</td>
                                            <td class="amount-negative">${formatNumber(returns.total_amount)}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        html += '</div></div>'; // Close tab-content and tabs-card

        reportContent.innerHTML = html;
    }

    function formatNumber(num) {
        return parseFloat(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getStatusArabic(status) {
        const statuses = {
            'ok': 'طبيعي',
            'low_stock': 'مخزون منخفض',
            'out_of_stock': 'نفد المخزون',
            'expiring_soon': 'قارب على الانتهاء',
            'expired': 'منتهي الصلاحية'
        };
        return statuses[status] || status;
    }
});
</script>
@endpush
