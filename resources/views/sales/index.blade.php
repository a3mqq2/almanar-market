@extends('layouts.app')

@section('title', 'المبيعات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">المبيعات</li>
@endsection

@push('styles')
<style>
    :root {
        --stats-border: var(--bs-border-color);
        --stats-bg-hover: var(--bs-tertiary-bg);
        --stats-label: var(--bs-secondary-color);
        --filter-bg: var(--bs-tertiary-bg);
        --skeleton-bg: var(--bs-secondary-bg);
        --empty-icon: var(--bs-secondary-color);
        --sortable-hover: var(--bs-tertiary-bg);
    }
    .stats-card {
        border: 1px solid var(--stats-border);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: all 0.2s;
        background: var(--bs-body-bg);
    }
    .stats-card:hover {
        border-color: var(--bs-border-color-translucent);
        background: var(--stats-bg-hover);
    }
    .stats-card .stats-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stats-card .stats-label {
        font-size: 0.8rem;
        color: var(--stats-label);
    }
    .filter-section {
        background: var(--filter-bg);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .table thead {
        background: var(--bs-tertiary-bg);
    }
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .clickable-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .clickable-row:hover {
        background-color: var(--bs-tertiary-bg) !important;
    }
    .skeleton {
        background: var(--skeleton-bg);
        animation: skeleton-pulse 1.5s ease-in-out infinite;
        border-radius: 4px;
    }
    @keyframes skeleton-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    .skeleton-row td {
        padding: 0.75rem;
    }
    .skeleton-text {
        height: 16px;
        width: 80%;
    }
    .toast-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    .empty-state i {
        font-size: 4rem;
        color: var(--empty-icon);
    }
    th.sortable {
        cursor: pointer;
        user-select: none;
    }
    th.sortable:hover {
        background: var(--sortable-hover);
    }
    .sort-icon {
        opacity: 0.3;
        font-size: 0.7rem;
        margin-right: 0.25rem;
    }
    .sort-icon.active {
        opacity: 1;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">سجل المبيعات</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="printListBtn" style="display: none;">
                <i class="ti ti-printer me-1"></i>طباعة
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <a href="{{ route('pos.screen') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-cash-register me-1"></i>نقطة البيع
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث برقم الفاتورة أو الزبون...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="cashierFilter">
                        <option value="">كل الكاشيرات</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}">{{ $cashier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="paymentMethodFilter">
                        <option value="">طريقة الدفع</option>
                        <option value="cash">نقداً</option>
                        <option value="bank">خدمات مصرفية</option>
                        <option value="credit">آجل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">كل الحالات</option>
                        <option value="completed">مكتملة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control" id="dateFromFilter">
                        <input type="date" class="form-control" id="dateToFilter">
                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0" id="salesTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th class="sortable" data-sort="invoice_number">رقم الفاتورة <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th class="sortable" data-sort="sale_date">التاريخ <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th>الزبون</th>
                        <th>الكاشير</th>
                        <th class="sortable" data-sort="total">الإجمالي <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th>المدفوع</th>
                        <th>آجل</th>
                        <th class="sortable" data-sort="status">الحالة <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th>حالة الدفع</th>
                        <th width="80">إجراءات</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody"></tbody>
            </table>
        </div>

        <div class="row g-3 mt-3" id="summarySection" style="display: none;">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value" id="summaryTotal">0</div>
                    <div class="stats-label">إجمالي المبيعات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-success" id="summaryCash">0</div>
                    <div class="stats-label">نقداً</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-primary" id="summaryBank">0</div>
                    <div class="stats-label">خدمات مصرفية</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-value text-danger" id="summaryCredit">0</div>
                    <div class="stats-label">آجل</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSort = 'created_at';
    let currentDirection = 'desc';
    let searchTimeout = null;

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
        setTimeout(() => toast.remove(), 4000);
    }

    function showSkeleton() {
        const tbody = document.getElementById('salesTableBody');
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function showEmptyPrompt() {
        document.getElementById('salesTableBody').innerHTML = `
            <tr>
                <td colspan="11">
                    <div class="empty-state">
                        <i class="ti ti-filter-search d-block mb-2"></i>
                        <p class="text-muted mb-0">حدد تاريخ البداية والنهاية لعرض المبيعات</p>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('summarySection').style.display = 'none';
    }

    function hasActiveFilters() {
        return document.getElementById('dateFromFilter').value
            && document.getElementById('dateToFilter').value;
    }

    function loadSales() {
        const printBtn = document.getElementById('printListBtn');
        if (!hasActiveFilters()) {
            showEmptyPrompt();
            printBtn.style.display = 'none';
            return;
        }

        printBtn.style.display = '';
        showSkeleton();

        const params = new URLSearchParams();
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const cashierId = document.getElementById('cashierFilter').value;
        const status = document.getElementById('statusFilter').value;
        const paymentMethod = document.getElementById('paymentMethodFilter').value;
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;

        if (search) params.append('search', search);
        if (cashierId) params.append('cashier_id', cashierId);
        if (status) params.append('status', status);
        if (paymentMethod) params.append('payment_method', paymentMethod);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        fetch(`{{ route('sales.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderSales(result.data);
            renderSummary(result.summary);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderSales(sales) {
        const tbody = document.getElementById('salesTableBody');

        if (sales.length == 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11">
                        <div class="empty-state">
                            <i class="ti ti-receipt-off d-block mb-2"></i>
                            <p class="text-muted mb-0">لا توجد مبيعات</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('summarySection').style.display = 'none';
            return;
        }

        let html = '';
        sales.forEach((sale, index) => {
            html += `
                <tr class="clickable-row" data-href="${window.__baseUrl}/sales/${sale.id}" tabindex="0">
                    <td>${index + 1}</td>
                    <td class="fw-medium">${sale.invoice_number}</td>
                    <td>${sale.sale_date}</td>
                    <td>${sale.customer_name}</td>
                    <td>${sale.cashier_name}</td>
                    <td class="fw-bold">${parseFloat(sale.total).toFixed(2)}</td>
                    <td class="text-success">${parseFloat(sale.paid_amount).toFixed(2)}</td>
                    <td class="text-danger">${parseFloat(sale.credit_amount).toFixed(2)}</td>
                    <td>
                        <span class="badge bg-${sale.status_color}">${sale.status_arabic}</span>
                    </td>
                    <td>
                        <span class="badge bg-${sale.payment_status == 'paid' ? 'success' : (sale.payment_status == 'partial' ? 'warning' : 'danger')}">${sale.payment_status_arabic}</span>
                    </td>
                    <td>
                        <a href="/sales/${sale.id}/print" target="_blank" class="btn btn-sm btn-outline-secondary" title="طباعة" onclick="event.stopPropagation();">
                            <i class="ti ti-printer"></i>
                        </a>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        tbody.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
            row.addEventListener('keydown', function(e) {
                if (e.key == 'Enter' || e.key == ' ') {
                    e.preventDefault();
                    window.location.href = this.dataset.href;
                }
            });
        });
    }

    function renderSummary(summary) {
        const section = document.getElementById('summarySection');
        section.style.display = '';
        document.getElementById('summaryTotal').textContent = parseFloat(summary.total_amount).toFixed(2);
        document.getElementById('summaryCash').textContent = parseFloat(summary.total_cash).toFixed(2);
        document.getElementById('summaryBank').textContent = parseFloat(summary.total_bank).toFixed(2);
        document.getElementById('summaryCredit').textContent = parseFloat(summary.total_credit).toFixed(2);
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadSales(), 400);
    });

    document.getElementById('cashierFilter').addEventListener('change', () => loadSales());
    document.getElementById('statusFilter').addEventListener('change', () => loadSales());
    document.getElementById('paymentMethodFilter').addEventListener('change', () => loadSales());
    document.getElementById('dateFromFilter').addEventListener('change', () => loadSales());
    document.getElementById('dateToFilter').addEventListener('change', () => loadSales());

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('cashierFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('paymentMethodFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        loadSales();
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadSales());

    document.getElementById('printListBtn').addEventListener('click', function() {
        const params = new URLSearchParams();
        const search = document.getElementById('searchInput').value;
        const cashierId = document.getElementById('cashierFilter').value;
        const status = document.getElementById('statusFilter').value;
        const paymentMethod = document.getElementById('paymentMethodFilter').value;
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;

        if (search) params.append('search', search);
        if (cashierId) params.append('cashier_id', cashierId);
        if (status) params.append('status', status);
        if (paymentMethod) params.append('payment_method', paymentMethod);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        window.open(`{{ route('sales.print-list') }}?${params}`, '_blank');
    });

    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            if (currentSort == sortField) {
                currentDirection = currentDirection == 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sortField;
                currentDirection = 'asc';
            }

            document.querySelectorAll('th.sortable .sort-icon').forEach(icon => {
                icon.classList.remove('active');
                icon.className = 'ti ti-arrows-sort sort-icon';
            });

            const icon = this.querySelector('.sort-icon');
            icon.classList.add('active');
            icon.className = `ti ti-arrow-${currentDirection == 'asc' ? 'up' : 'down'} sort-icon active`;

            loadSales();
        });
    });

    showEmptyPrompt();
});
</script>
@endpush
