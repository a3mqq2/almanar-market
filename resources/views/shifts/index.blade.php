@extends('layouts.app')

@section('title', 'تقارير الورديات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">تقارير الورديات</li>
@endsection

@push('styles')
<style>
    .stat-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stat-card .stat-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
    }
    .filter-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .clickable-row {
        cursor: pointer;
        transition: background 0.15s;
    }
    .clickable-row:hover {
        background: var(--bs-tertiary-bg) !important;
    }
    .sort-header {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .sort-header:hover {
        color: var(--bs-primary);
    }
    .sort-header .sort-icon {
        opacity: 0.3;
        margin-right: 0.25rem;
    }
    .sort-header.active .sort-icon {
        opacity: 1;
        color: var(--bs-primary);
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--bs-secondary-color);
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(var(--bs-body-bg-rgb), 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }

    [data-bs-theme="dark"] .stat-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .stat-card .stat-value {
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .filter-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .table-light,
    [data-bs-theme="dark"] .table-light th,
    [data-bs-theme="dark"] .table-light td {
        background-color: #2b3035 !important;
        color: #e9ecef !important;
        border-color: #373b3e !important;
    }
    [data-bs-theme="dark"] .table {
        --bs-table-bg: transparent;
        --bs-table-color: #e9ecef;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .table td,
    [data-bs-theme="dark"] .table th {
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .clickable-row:hover {
        background: #2b3035 !important;
    }
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background: #1a1d21;
        border-color: #373b3e;
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .loading-overlay {
        background: rgba(26, 29, 33, 0.8);
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="ti ti-download me-1"></i>تصدير
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="exportData('excel')"><i class="ti ti-file-spreadsheet me-2"></i>Excel</a></li>
                    <li><a class="dropdown-item" href="#" onclick="exportData('pdf')"><i class="ti ti-file-text me-2"></i>PDF</a></li>
                    <li><a class="dropdown-item" href="#" onclick="printReport()"><i class="ti ti-printer me-2"></i>طباعة</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-card">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">الكاشير</label>
                    <select class="form-select form-select-sm" id="filterUser">
                        <option value="">الكل</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">الخزينة</label>
                    <select class="form-select form-select-sm" id="filterCashbox">
                        <option value="">الكل</option>
                        @foreach($cashboxes as $cashbox)
                            <option value="{{ $cashbox->id }}">{{ $cashbox->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">الجهاز</label>
                    <select class="form-select form-select-sm" id="filterTerminal">
                        <option value="">الكل</option>
                        @foreach($terminals as $terminal)
                            <option value="{{ $terminal }}">{{ $terminal }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">الحالة</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">الكل</option>
                        <option value="open">مفتوحة</option>
                        <option value="closed">مغلقة</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">الفروقات</label>
                    <select class="form-select form-select-sm" id="filterDifference">
                        <option value="">الكل</option>
                        <option value="yes">يوجد فرق</option>
                        <option value="no">لا يوجد</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateFrom">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" class="form-control form-control-sm" id="filterDateTo">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">بحث</label>
                    <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="اسم، رقم، جهاز...">
                </div>
            </div>
        </div>

        <div class="position-relative">
            <div class="loading-overlay d-none" id="loadingOverlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="sort-header" data-sort="id">
                                <i class="ti ti-arrows-sort sort-icon"></i>#
                            </th>
                            <th>الكاشير</th>
                            <th>الجهاز</th>
                            <th>الخزينة</th>
                            <th class="sort-header" data-sort="opened_at">
                                <i class="ti ti-arrows-sort sort-icon"></i>تاريخ الفتح
                            </th>
                            <th>تاريخ الإغلاق</th>
                            <th class="sort-header" data-sort="status">
                                <i class="ti ti-arrows-sort sort-icon"></i>الحالة
                            </th>
                            <th class="sort-header text-end" data-sort="total_sales">
                                <i class="ti ti-arrows-sort sort-icon"></i>المبيعات
                            </th>
                            <th class="text-end">الكاش</th>
                            <th class="sort-header text-end" data-sort="difference">
                                <i class="ti ti-arrows-sort sort-icon"></i>الفرق
                            </th>
                            <th>الحالة المالية</th>
                        </tr>
                    </thead>
                    <tbody id="shiftsTableBody">
                    </tbody>
                </table>
            </div>

            <div id="emptyState" class="empty-state d-none">
                <i class="ti ti-clock-off d-block"></i>
                <h5>لا توجد ورديات</h5>
                <p>لم يتم العثور على أي ورديات تطابق معايير البحث</p>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3" id="paginationContainer">
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
const filterUrl = '{{ route("shift-reports.filter") }}';
const showUrl = '{{ route("shift-reports.show", ":id") }}';
const exportUrl = '{{ route("shift-reports.export") }}';

let currentSort = 'opened_at';
let currentDirection = 'desc';
let currentPage = 1;
let debounceTimer;

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 show`;
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

function getFilters() {
    return {
        user_id: document.getElementById('filterUser').value,
        cashbox_id: document.getElementById('filterCashbox').value,
        terminal_id: document.getElementById('filterTerminal').value,
        status: document.getElementById('filterStatus').value,
        has_difference: document.getElementById('filterDifference').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        search: document.getElementById('searchInput').value,
        sort: currentSort,
        direction: currentDirection,
        page: currentPage,
    };
}

async function loadShifts() {
    const overlay = document.getElementById('loadingOverlay');
    overlay.classList.remove('d-none');

    const filters = getFilters();
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });

    try {
        const response = await fetch(`${filterUrl}?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            renderStats(data.stats);
            renderTable(data.shifts);
            renderPagination(data.pagination);
        } else {
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        }
    } catch (error) {
        showToast('خطأ في الاتصال بالخادم', 'danger');
    }

    overlay.classList.add('d-none');
}

function renderStats(stats) {
    document.getElementById('statTotalShifts').textContent = stats.total_shifts.toLocaleString();
    document.getElementById('statTotalSales').textContent = parseFloat(stats.total_sales).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('statTotalDifference').textContent = parseFloat(stats.total_difference).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('statOpenShifts').textContent = stats.open_shifts.toLocaleString();
}

function renderTable(shifts) {
    const tbody = document.getElementById('shiftsTableBody');
    const emptyState = document.getElementById('emptyState');

    if (shifts.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('d-none');
        return;
    }

    emptyState.classList.add('d-none');

    tbody.innerHTML = shifts.map(shift => `
        <tr class="clickable-row" onclick="viewShift(${shift.id})">
            <td class="fw-medium">${shift.id}</td>
            <td>${shift.user_name}</td>
            <td class="text-muted">${shift.terminal_id}</td>
            <td class="text-muted">${shift.cashbox_names}</td>
            <td>${shift.opened_at}</td>
            <td>${shift.closed_at}</td>
            <td><span class="badge bg-${shift.status_color}">${shift.status_arabic}</span></td>
            <td class="text-end fw-medium">${parseFloat(shift.total_sales).toLocaleString('en', {minimumFractionDigits: 2})}</td>
            <td class="text-end">${parseFloat(shift.total_cash).toLocaleString('en', {minimumFractionDigits: 2})}</td>
            <td class="text-end ${shift.difference > 0 ? 'text-success' : (shift.difference < 0 ? 'text-danger' : '')}">${parseFloat(shift.difference).toLocaleString('en', {minimumFractionDigits: 2})}</td>
            <td><span class="badge bg-${shift.financial_status_color}">${shift.financial_status}</span></td>
        </tr>
    `).join('');
}

function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const links = document.getElementById('paginationLinks');

    if (pagination.total === 0) {
        info.textContent = '';
        links.innerHTML = '';
        return;
    }

    info.textContent = `عرض ${pagination.from} إلى ${pagination.to} من ${pagination.total}`;

    let html = '';

    if (pagination.current_page > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1})"><i class="ti ti-chevron-right"></i></a></li>`;
    }

    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="goToPage(${i})">${i}</a></li>`;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    if (pagination.current_page < pagination.last_page) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1})"><i class="ti ti-chevron-left"></i></a></li>`;
    }

    links.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadShifts();
}

function viewShift(id) {
    window.location.href = showUrl.replace(':id', id);
}

function setupSortHeaders() {
    document.querySelectorAll('.sort-header').forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;

            if (currentSort === sortField) {
                currentDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sortField;
                currentDirection = 'desc';
            }

            document.querySelectorAll('.sort-header').forEach(h => {
                h.classList.remove('active');
                h.querySelector('.sort-icon').className = 'ti ti-arrows-sort sort-icon';
            });

            this.classList.add('active');
            this.querySelector('.sort-icon').className = `ti ti-sort-${currentDirection === 'asc' ? 'ascending' : 'descending'} sort-icon`;

            currentPage = 1;
            loadShifts();
        });
    });
}

function setupFilterListeners() {
    const filterElements = ['filterUser', 'filterCashbox', 'filterTerminal', 'filterStatus', 'filterDifference', 'filterDateFrom', 'filterDateTo'];

    filterElements.forEach(id => {
        document.getElementById(id).addEventListener('change', function() {
            currentPage = 1;
            loadShifts();
        });
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentPage = 1;
            loadShifts();
        }, 300);
    });
}

function exportData(format) {
    const filters = getFilters();
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value && key !== 'page') params.append(key, value);
    });

    params.append('format', format);
    window.location.href = `${exportUrl}?${params.toString()}`;
}

function printReport() {
    exportData('pdf');
}

document.addEventListener('DOMContentLoaded', function() {
    setupSortHeaders();
    setupFilterListeners();
    loadShifts();
});
</script>
@endpush
