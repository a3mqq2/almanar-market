@extends('layouts.app')

@section('title', 'إدارة الخزائن')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">الخزائن</li>
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
    .clickable-row:focus {
        outline: 2px solid var(--bs-primary);
        outline-offset: -2px;
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
    .sort-icon {
        opacity: 0.3;
        font-size: 0.7rem;
        margin-right: 0.25rem;
    }
    .sort-icon.active {
        opacity: 1;
    }
    th.sortable {
        cursor: pointer;
        user-select: none;
    }
    th.sortable:hover {
        background: var(--sortable-hover);
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">قائمة الخزائن</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#cashboxModal" onclick="openCreateModal()">
                <i class="ti ti-plus me-1"></i>إضافة خزينة
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">كل الحالات</option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="typeFilter">
                        <option value="">كل الأنواع</option>
                        <option value="cash">نقدي</option>
                        <option value="card">بطاقة</option>
                        <option value="wallet">محفظة</option>
                        <option value="bank">مصرفي</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select form-select-sm" id="perPageSelect">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearFilters">
                        <i class="ti ti-x me-1"></i>مسح
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0" id="cashboxesTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th class="sortable" data-sort="name">
                            الاسم
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="type">
                            النوع
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th>طريقة الدفع</th>
                        <th class="sortable" data-sort="current_balance">
                            الرصيد الحالي
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="status">
                            الحالة
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="created_at">
                            تاريخ الإضافة
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="cashboxesTableBody">
                </tbody>
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

<div class="modal fade" id="cashboxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalTitle"><i class="ti ti-plus me-1"></i>إضافة خزينة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cashboxForm" novalidate>
                <input type="hidden" id="cashboxId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الخزينة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cashboxName" name="name" required>
                        <div class="invalid-feedback" id="nameFeedback">اسم الخزينة مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نوع الخزينة</label>
                        <select class="form-select" id="cashboxType" name="type">
                            <option value="cash">نقدي</option>
                            <option value="card">بطاقة</option>
                            <option value="wallet">محفظة</option>
                            <option value="bank">مصرفي</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">طريقة الدفع المرتبطة</label>
                        <select class="form-select" id="cashboxPaymentMethod" name="payment_method_id">
                            <option value="">-- غير مرتبط --</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">ربط طريقة دفع بهذه الخزينة للتسجيل التلقائي</div>
                    </div>
                    <div class="mb-3" id="openingBalanceGroup">
                        <label class="form-label">الرصيد الافتتاحي</label>
                        <input type="number" class="form-control" id="cashboxOpeningBalance" name="opening_balance" value="0" min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="cashboxStatus" name="status" checked>
                            <label class="form-check-label" for="cashboxStatus">نشط</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="submitBtn">
                        <i class="ti ti-check me-1"></i>حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let currentPage = 1;
    let currentSort = 'created_at';
    let currentDirection = 'desc';
    let searchTimeout = null;
    let editMode = false;
    let nameCheckTimeout = null;
    let isNameValid = true;

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
        const tbody = document.getElementById('cashboxesTableBody');
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function loadCashboxes(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', document.getElementById('perPageSelect').value);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const type = document.getElementById('typeFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (type) params.append('type', type);

        fetch(`{{ route('cashboxes.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderCashboxes(result.data, result.meta);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderCashboxes(cashboxes, meta) {
        const tbody = document.getElementById('cashboxesTableBody');

        if (cashboxes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="ti ti-building-bank d-block mb-2"></i>
                            <p class="text-muted mb-0">لا يوجد خزائن</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        const typeColors = { 'cash': 'success', 'card': 'primary', 'wallet': 'info', 'bank': 'secondary' };

        let html = '';
        cashboxes.forEach((cashbox, index) => {
            const rowNum = meta.from + index;
            const balance = parseFloat(cashbox.current_balance || 0);
            const typeColor = typeColors[cashbox.type] || 'secondary';
            const linkedMethod = cashbox.linked_payment_method
                ? `<span class="badge bg-primary-subtle text-primary">${cashbox.linked_payment_method.name}</span>`
                : '<span class="text-muted">-</span>';

            html += `
                <tr class="clickable-row" data-href="/cashboxes/${cashbox.id}" tabindex="0">
                    <td>${rowNum}</td>
                    <td class="fw-medium">${cashbox.name}</td>
                    <td><span class="badge bg-${typeColor}-subtle text-${typeColor}">${cashbox.type_arabic}</span></td>
                    <td>${linkedMethod}</td>
                    <td class="fw-bold text-primary">${balance.toFixed(2)}</td>
                    <td>
                        <span class="badge bg-${cashbox.status ? 'success' : 'secondary'}">
                            ${cashbox.status_arabic}
                        </span>
                    </td>
                    <td>${cashbox.created_at}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        tbody.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
            row.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.location.href = this.dataset.href;
                }
            });
        });

        renderPagination(meta);
    }

    function renderPagination(meta) {
        document.getElementById('paginationInfo').textContent =
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} خزينة`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadCashboxes(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadCashboxes(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadCashboxes(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadCashboxes(${meta.last_page}); return false;">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadCashboxes(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadCashboxes(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadCashboxes(1));
    document.getElementById('typeFilter').addEventListener('change', () => loadCashboxes(1));
    document.getElementById('perPageSelect').addEventListener('change', () => loadCashboxes(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('typeFilter').value = '';
        document.getElementById('perPageSelect').value = '15';
        loadCashboxes(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadCashboxes(currentPage));

    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            if (currentSort === sortField) {
                currentDirection = currentDirection === 'asc' ? 'desc' : 'asc';
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
            icon.className = `ti ti-arrow-${currentDirection === 'asc' ? 'up' : 'down'} sort-icon active`;

            loadCashboxes(1);
        });
    });

    window.openCreateModal = function() {
        editMode = false;
        document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-1"></i>إضافة خزينة';
        document.getElementById('cashboxId').value = '';
        document.getElementById('cashboxForm').reset();
        document.getElementById('cashboxStatus').checked = true;
        document.getElementById('cashboxType').value = 'cash';
        document.getElementById('cashboxPaymentMethod').value = '';
        document.getElementById('cashboxName').classList.remove('is-invalid', 'is-valid');
        document.getElementById('openingBalanceGroup').style.display = '';
        isNameValid = true;
    };

    document.getElementById('cashboxName').addEventListener('input', function() {
        clearTimeout(nameCheckTimeout);
        const name = this.value.trim();

        if (!name) {
            this.classList.remove('is-invalid', 'is-valid');
            isNameValid = true;
            return;
        }

        nameCheckTimeout = setTimeout(async () => {
            const cashboxId = document.getElementById('cashboxId').value;
            let url = `{{ route("cashboxes.check-name") }}?name=${encodeURIComponent(name)}`;
            if (cashboxId) {
                url += `&exclude_id=${cashboxId}`;
            }

            try {
                const response = await fetch(url);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('nameFeedback').textContent = 'اسم الخزينة مستخدم بالفعل';
                    isNameValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    isNameValid = true;
                }
            } catch (error) {
                console.error('Error checking name:', error);
            }
        }, 300);
    });

    document.getElementById('cashboxForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('cashboxName').value.trim();
        const type = document.getElementById('cashboxType').value;
        const paymentMethodId = document.getElementById('cashboxPaymentMethod').value;
        const openingBalance = parseFloat(document.getElementById('cashboxOpeningBalance').value) || 0;
        const status = document.getElementById('cashboxStatus').checked;
        const cashboxId = document.getElementById('cashboxId').value;

        if (!name) {
            document.getElementById('cashboxName').classList.add('is-invalid');
            document.getElementById('nameFeedback').textContent = 'اسم الخزينة مطلوب';
            return;
        }

        if (!isNameValid) {
            document.getElementById('cashboxName').focus();
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const url = editMode ? `/cashboxes/${cashboxId}` : '{{ route("cashboxes.store") }}';
            const method = editMode ? 'PUT' : 'POST';

            const body = { name, type, status, payment_method_id: paymentMethodId || null };
            if (!editMode) {
                body.opening_balance = openingBalance;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('cashboxModal')).hide();
                loadCashboxes(currentPage);
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });

    window.loadCashboxes = loadCashboxes;
    loadCashboxes(1);
});
</script>
@endpush
