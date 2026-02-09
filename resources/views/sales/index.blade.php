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
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">كل الحالات</option>
                        <option value="completed">مكتملة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="paymentStatusFilter">
                        <option value="">حالة الدفع</option>
                        <option value="paid">مدفوعة</option>
                        <option value="partial">جزئي</option>
                        <option value="credit">آجل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-sm" id="dateFromFilter" placeholder="من تاريخ">
                </div>
                <div class="col-md-2">
                    <input type="date" class="form-control form-control-sm" id="dateToFilter" placeholder="إلى تاريخ">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearFilters">
                        <i class="ti ti-x"></i>
                    </button>
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

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2" id="paginationContainer">
            <div class="text-muted small" id="paginationInfo"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
            </nav>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
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

    function loadSales(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const paymentStatus = document.getElementById('paymentStatusFilter').value;
        const dateFrom = document.getElementById('dateFromFilter').value;
        const dateTo = document.getElementById('dateToFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (paymentStatus) params.append('payment_status', paymentStatus);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        fetch(`{{ route('sales.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderSales(result.data, result.meta);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderSales(sales, meta) {
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
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        sales.forEach((sale, index) => {
            const rowNum = meta.from + index;
            html += `
                <tr class="clickable-row" data-href="/sales/${sale.id}" tabindex="0">
                    <td>${rowNum}</td>
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

        renderPagination(meta);
    }

    function renderPagination(meta) {
        document.getElementById('paginationInfo').textContent =
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} فاتورة`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSales(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadSales(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i == meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadSales(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadSales(${meta.last_page}); return false;">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSales(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadSales(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadSales(1));
    document.getElementById('paymentStatusFilter').addEventListener('change', () => loadSales(1));
    document.getElementById('dateFromFilter').addEventListener('change', () => loadSales(1));
    document.getElementById('dateToFilter').addEventListener('change', () => loadSales(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('paymentStatusFilter').value = '';
        document.getElementById('dateFromFilter').value = '';
        document.getElementById('dateToFilter').value = '';
        loadSales(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadSales(currentPage));

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

            loadSales(1);
        });
    });

    window.loadSales = loadSales;
    loadSales(1);
});
</script>
@endpush
