@extends('layouts.app')

@section('title', 'إدارة الزبائن')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">الزبائن</li>
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
        <h5 class="card-title mb-0">قائمة الزبائن</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#customerModal" onclick="openCreateModal()">
                <i class="ti ti-plus me-1"></i>إضافة زبون
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم أو الهاتف...">
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
                    <select class="form-select form-select-sm" id="creditFilter">
                        <option value="">الكل</option>
                        <option value="yes">مسموح له بالآجل</option>
                        <option value="no">غير مسموح</option>
                    </select>
                </div>
                <div class="col-md-2">
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
            <table class="table table-hover mb-0" id="customersTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th class="sortable" data-sort="name">الاسم <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th class="sortable" data-sort="phone">الهاتف <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th class="sortable" data-sort="current_balance">الرصيد <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th class="sortable" data-sort="credit_limit">الحد الائتماني <i class="ti ti-arrows-sort sort-icon"></i></th>
                        <th>آجل</th>
                        <th class="sortable" data-sort="status">الحالة <i class="ti ti-arrows-sort sort-icon"></i></th>
                    </tr>
                </thead>
                <tbody id="customersTableBody"></tbody>
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

<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalTitle"><i class="ti ti-plus me-1"></i>إضافة زبون</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm" novalidate>
                <input type="hidden" id="customerId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الزبون <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customerName" name="name" required>
                        <div class="invalid-feedback">اسم الزبون مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="customerPhone" name="phone" required>
                        <div class="invalid-feedback" id="phoneFeedback">رقم الهاتف مطلوب</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الحد الائتماني</label>
                            <input type="number" class="form-control" id="customerCreditLimit" name="credit_limit" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" id="customerAllowCredit" name="allow_credit">
                                <label class="form-check-label" for="customerAllowCredit">السماح بالبيع الآجل</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="customerNotes" name="notes" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="customerStatus" name="status" checked>
                            <label class="form-check-label" for="customerStatus">نشط</label>
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
    let phoneCheckTimeout = null;
    let isPhoneValid = true;

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
        const tbody = document.getElementById('customersTableBody');
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function loadCustomers(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', document.getElementById('perPageSelect').value);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const credit = document.getElementById('creditFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (credit) params.append('allow_credit', credit);

        fetch(`{{ route('customers.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderCustomers(result.data, result.meta);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderCustomers(customers, meta) {
        const tbody = document.getElementById('customersTableBody');

        if (customers.length == 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="ti ti-users d-block mb-2"></i>
                            <p class="text-muted mb-0">لا يوجد زبائن</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        customers.forEach((customer, index) => {
            const rowNum = meta.from + index;
            const balance = parseFloat(customer.current_balance || 0);
            const balanceClass = balance > 0 ? 'text-danger' : (balance < 0 ? 'text-success' : '');
            html += `
                <tr class="clickable-row" data-href="/customers/${customer.id}/account" tabindex="0">
                    <td>${rowNum}</td>
                    <td class="fw-medium">${customer.name}</td>
                    <td dir="ltr" class="text-end">${customer.phone}</td>
                    <td class="fw-bold ${balanceClass}">${balance.toFixed(2)}</td>
                    <td>${parseFloat(customer.credit_limit || 0).toFixed(2)}</td>
                    <td>
                        ${customer.allow_credit
                            ? '<span class="badge bg-info">مسموح</span>'
                            : '<span class="badge bg-secondary">غير مسموح</span>'
                        }
                    </td>
                    <td>
                        <span class="badge bg-${customer.status ? 'success' : 'secondary'}">
                            ${customer.status ? 'نشط' : 'غير نشط'}
                        </span>
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
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} زبون`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadCustomers(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadCustomers(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i == meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadCustomers(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadCustomers(${meta.last_page}); return false;">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadCustomers(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadCustomers(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadCustomers(1));
    document.getElementById('creditFilter').addEventListener('change', () => loadCustomers(1));
    document.getElementById('perPageSelect').addEventListener('change', () => loadCustomers(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('creditFilter').value = '';
        document.getElementById('perPageSelect').value = '15';
        loadCustomers(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadCustomers(currentPage));

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

            loadCustomers(1);
        });
    });

    window.openCreateModal = function() {
        editMode = false;
        document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-1"></i>إضافة زبون';
        document.getElementById('customerId').value = '';
        document.getElementById('customerForm').reset();
        document.getElementById('customerStatus').checked = true;
        document.getElementById('customerName').classList.remove('is-invalid');
        document.getElementById('customerPhone').classList.remove('is-invalid', 'is-valid');
        isPhoneValid = true;
    };

    document.getElementById('customerPhone').addEventListener('input', function() {
        clearTimeout(phoneCheckTimeout);
        const phone = this.value.trim();

        if (!phone) {
            this.classList.remove('is-invalid', 'is-valid');
            isPhoneValid = true;
            return;
        }

        phoneCheckTimeout = setTimeout(async () => {
            const customerId = document.getElementById('customerId').value;
            let url = `{{ route("customers.check-phone") }}?phone=${encodeURIComponent(phone)}`;
            if (customerId) {
                url += `&exclude_id=${customerId}`;
            }

            try {
                const response = await fetch(url);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('phoneFeedback').textContent = 'رقم الهاتف مستخدم بالفعل';
                    isPhoneValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    isPhoneValid = true;
                }
            } catch (error) {
                console.error('Error checking phone:', error);
            }
        }, 300);
    });

    document.getElementById('customerForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('customerName').value.trim();
        const phone = document.getElementById('customerPhone').value.trim();
        const creditLimit = document.getElementById('customerCreditLimit').value;
        const allowCredit = document.getElementById('customerAllowCredit').checked;
        const notes = document.getElementById('customerNotes').value;
        const status = document.getElementById('customerStatus').checked;
        const customerId = document.getElementById('customerId').value;

        if (!name) {
            document.getElementById('customerName').classList.add('is-invalid');
            return;
        }

        if (!phone) {
            document.getElementById('customerPhone').classList.add('is-invalid');
            document.getElementById('phoneFeedback').textContent = 'رقم الهاتف مطلوب';
            return;
        }

        if (!isPhoneValid) {
            document.getElementById('customerPhone').focus();
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const url = editMode ? `/customers/${customerId}` : '{{ route("customers.store") }}';
            const method = editMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, phone, credit_limit: creditLimit, allow_credit: allowCredit, notes, status })
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
                loadCustomers(currentPage);
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });

    document.getElementById('customerName').addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });

    window.loadCustomers = loadCustomers;
    loadCustomers(1);
});
</script>
@endpush
