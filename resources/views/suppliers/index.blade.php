@extends('layouts.app')

@section('title', 'إدارة الموردين')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">الموردين</li>
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
        <h5 class="card-title mb-0">قائمة الموردين</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#supplierModal" onclick="openCreateModal()">
                <i class="ti ti-plus me-1"></i>إضافة مورد
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم أو الهاتف...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="statusFilter">
                        <option value="">كل الحالات</option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
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
            <table class="table table-hover mb-0" id="suppliersTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th class="sortable" data-sort="name">
                            الاسم
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="phone">
                            الهاتف
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th>الرصيد</th>
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
                <tbody id="suppliersTableBody">
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

<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modalTitle"><i class="ti ti-plus me-1"></i>إضافة مورد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="supplierForm" novalidate>
                <input type="hidden" id="supplierId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المورد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplierName" name="name" required>
                        <div class="invalid-feedback">اسم المورد مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="supplierPhone" name="phone" required>
                        <div class="invalid-feedback" id="phoneFeedback">رقم الهاتف مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="supplierStatus" name="status" checked>
                            <label class="form-check-label" for="supplierStatus">نشط</label>
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
        const tbody = document.getElementById('suppliersTableBody');
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 70px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function loadSuppliers(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', document.getElementById('perPageSelect').value);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);

        fetch(`{{ route('suppliers.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderSuppliers(result.data, result.meta);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderSuppliers(suppliers, meta) {
        const tbody = document.getElementById('suppliersTableBody');

        if (suppliers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="ti ti-users-group d-block mb-2"></i>
                            <p class="text-muted mb-0">لا يوجد موردين</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        suppliers.forEach((supplier, index) => {
            const rowNum = meta.from + index;
            const balance = parseFloat(supplier.current_balance || 0);
            const balanceClass = balance > 0 ? 'text-danger' : (balance < 0 ? 'text-success' : '');
            html += `
                <tr class="clickable-row" data-href="/suppliers/${supplier.id}/account" tabindex="0">
                    <td>${rowNum}</td>
                    <td class="fw-medium">${supplier.name}</td>
                    <td dir="ltr" class="text-end">${supplier.phone}</td>
                    <td class="fw-bold ${balanceClass}">${balance.toFixed(2)}</td>
                    <td>
                        <span class="badge bg-${supplier.status ? 'success' : 'secondary'}">
                            ${supplier.status ? 'نشط' : 'غير نشط'}
                        </span>
                    </td>
                    <td>${supplier.created_at}</td>
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
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} مورد`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSuppliers(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadSuppliers(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadSuppliers(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadSuppliers(${meta.last_page}); return false;">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadSuppliers(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadSuppliers(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadSuppliers(1));
    document.getElementById('perPageSelect').addEventListener('change', () => loadSuppliers(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('perPageSelect').value = '15';
        loadSuppliers(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadSuppliers(currentPage));

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

            loadSuppliers(1);
        });
    });

    window.openCreateModal = function() {
        editMode = false;
        document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-1"></i>إضافة مورد';
        document.getElementById('supplierId').value = '';
        document.getElementById('supplierForm').reset();
        document.getElementById('supplierStatus').checked = true;
        document.getElementById('supplierName').classList.remove('is-invalid');
        document.getElementById('supplierPhone').classList.remove('is-invalid', 'is-valid');
        isPhoneValid = true;
    };

    window.openEditModal = function(id, name, phone, status) {
        editMode = true;
        document.getElementById('modalTitle').innerHTML = '<i class="ti ti-edit me-1"></i>تعديل مورد';
        document.getElementById('supplierId').value = id;
        document.getElementById('supplierName').value = name;
        document.getElementById('supplierPhone').value = phone;
        document.getElementById('supplierStatus').checked = status;
        document.getElementById('supplierName').classList.remove('is-invalid');
        document.getElementById('supplierPhone').classList.remove('is-invalid', 'is-valid');
        isPhoneValid = true;
        new bootstrap.Modal(document.getElementById('supplierModal')).show();
    };

    document.getElementById('supplierPhone').addEventListener('input', function() {
        clearTimeout(phoneCheckTimeout);
        const phone = this.value.trim();

        if (!phone) {
            this.classList.remove('is-invalid', 'is-valid');
            isPhoneValid = true;
            return;
        }

        phoneCheckTimeout = setTimeout(async () => {
            const supplierId = document.getElementById('supplierId').value;
            let url = `{{ route("suppliers.check-phone") }}?phone=${encodeURIComponent(phone)}`;
            if (supplierId) {
                url += `&exclude_id=${supplierId}`;
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

    document.getElementById('supplierForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('supplierName').value.trim();
        const phone = document.getElementById('supplierPhone').value.trim();
        const status = document.getElementById('supplierStatus').checked;
        const supplierId = document.getElementById('supplierId').value;

        if (!name) {
            document.getElementById('supplierName').classList.add('is-invalid');
            return;
        }

        if (!phone) {
            document.getElementById('supplierPhone').classList.add('is-invalid');
            document.getElementById('phoneFeedback').textContent = 'رقم الهاتف مطلوب';
            return;
        }

        if (!isPhoneValid) {
            document.getElementById('supplierPhone').focus();
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const url = editMode ? `/suppliers/${supplierId}` : '{{ route("suppliers.store") }}';
            const method = editMode ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, phone, status })
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('supplierModal')).hide();
                loadSuppliers(currentPage);
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });

    document.getElementById('supplierName').addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });

    window.confirmDelete = function(id, name) {
        Swal.fire({
            title: 'تأكيد الحذف',
            html: `هل أنت متأكد من حذف المورد:<br><strong>${name}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('_token', csrfToken);

                    const response = await fetch(`/suppliers/${id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast(data.message, 'success');
                        loadSuppliers(currentPage);
                    } else {
                        showToast(data.message || 'حدث خطأ', 'danger');
                    }
                } catch (error) {
                    showToast('حدث خطأ في حذف المورد', 'danger');
                }
            }
        });
    };

    window.loadSuppliers = loadSuppliers;
    loadSuppliers(1);
});
</script>
@endpush
