@extends('layouts.app')

@section('title', 'إدارة المستخدمين')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">المستخدمين</li>
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
    .cashbox-badge {
        font-size: 0.7rem;
        margin: 0.1rem;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">قائمة المستخدمين</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="openCreateModal()">
                <i class="ti ti-plus me-1"></i>إضافة مستخدم
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم أو اسم المستخدم...">
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
                    <select class="form-select form-select-sm" id="roleFilter">
                        <option value="">كل الأدوار</option>
                        <option value="manager">مدير</option>
                        <option value="cashier">كاشير</option>
                        <option value="price_checker">جهاز الأسعار</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select class="form-select form-select-sm" id="perPageSelect">
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearFilters">
                        <i class="ti ti-x me-1"></i>مسح الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th class="sortable" data-sort="name">
                            الاسم
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="username">
                            اسم المستخدم
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="role">
                            الدور
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th>الخزائن</th>
                        <th class="sortable" data-sort="status">
                            الحالة
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="last_login_at">
                            آخر دخول
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
            <div class="text-muted small" id="paginationInfo"></div>
            <nav id="paginationLinks"></nav>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="ti ti-plus me-1"></i>إضافة مستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="userForm" method="POST" action="javascript:void(0);" novalidate>
                <input type="hidden" id="userId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="userUsername" name="username" required>
                        <div class="invalid-feedback" id="usernameFeedback">اسم المستخدم مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="userEmail" name="email">
                    </div>
                    <div class="mb-3" id="passwordGroup">
                        <label class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="userPassword" name="password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الدور</label>
                        <select class="form-select" id="userRole" name="role">
                            <option value="cashier">كاشير</option>
                            <option value="manager">مدير</option>
                            <option value="price_checker">جهاز الأسعار</option>
                        </select>
                    </div>
                    <div class="mb-3" id="cashboxesGroup">
                        <label class="form-label">الخزائن المسموحة</label>
                        <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto;">
                            @foreach($cashboxes as $cashbox)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input cashbox-checkbox" value="{{ $cashbox->id }}" id="cashbox{{ $cashbox->id }}">
                                    <label class="form-check-label" for="cashbox{{ $cashbox->id }}">{{ $cashbox->name }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">حدد الخزائن التي يمكن للكاشير استخدامها</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="userStatus" name="status" checked>
                            <label class="form-check-label" for="userStatus">نشط</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-check me-1"></i>حفظ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let currentPage = 1;
    let currentSort = 'created_at';
    let currentDirection = 'desc';
    let searchTimeout;
    let usernameCheckTimeout;
    let editMode = false;
    let isUsernameValid = true;

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
        const tbody = document.getElementById('usersTableBody');
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function loadUsers(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', document.getElementById('perPageSelect').value);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const role = document.getElementById('roleFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (role) params.append('role', role);

        fetch(`{{ route('users.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderUsers(result.data, result.meta);
        })
        .catch(error => showToast('حدث خطأ في تحميل البيانات', 'danger'));
    }

    function renderUsers(users, meta) {
        const tbody = document.getElementById('usersTableBody');

        if (users.length == 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="ti ti-users d-block mb-2"></i>
                            <p class="text-muted mb-0">لا يوجد مستخدمين</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        const roleColors = { 'manager': 'info', 'cashier': 'warning', 'price_checker': 'secondary' };

        let html = '';
        users.forEach((user, index) => {
            const rowNum = meta.from + index;
            const roleColor = roleColors[user.role] || 'secondary';

            let cashboxesHtml = '-';
            if (user.role == 'manager') {
                cashboxesHtml = '<span class="badge bg-info-subtle text-info">جميع الخزائن</span>';
            } else if (user.cashboxes && user.cashboxes.length > 0) {
                cashboxesHtml = user.cashboxes.map(cb =>
                    `<span class="badge bg-secondary-subtle text-secondary cashbox-badge">${cb.name}</span>`
                ).join('');
            }

            html += `
                <tr class="clickable-row" data-href="/users/${user.id}" tabindex="0">
                    <td>${rowNum}</td>
                    <td class="fw-medium">${user.name}</td>
                    <td><code>${user.username}</code></td>
                    <td><span class="badge bg-${roleColor}-subtle text-${roleColor}">${user.role_arabic}</span></td>
                    <td>${cashboxesHtml}</td>
                    <td>
                        <span class="badge bg-${user.status ? 'success' : 'secondary'}">
                            ${user.status_arabic}
                        </span>
                    </td>
                    <td>${user.last_login_at || '-'}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        document.getElementById('paginationInfo').textContent = `عرض ${meta.from} - ${meta.to} من ${meta.total}`;
        renderPagination(meta);
        attachRowClick();
    }

    function renderPagination(meta) {
        const container = document.getElementById('paginationLinks');
        if (meta.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination pagination-sm mb-0">';

        html += `<li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${meta.current_page - 1}"><i class="ti ti-chevron-right"></i></a>
        </li>`;

        for (let i = 1; i <= meta.last_page; i++) {
            if (i == 1 || i == meta.last_page || (i >= meta.current_page - 1 && i <= meta.current_page + 1)) {
                html += `<li class="page-item ${i == meta.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            } else if (i == meta.current_page - 2 || i == meta.current_page + 2) {
                html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        html += `<li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${meta.current_page + 1}"><i class="ti ti-chevron-left"></i></a>
        </li>`;

        html += '</ul>';
        container.innerHTML = html;

        container.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page >= 1) loadUsers(page);
            });
        });
    }

    function attachRowClick() {
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
            row.addEventListener('keypress', function(e) {
                if (e.key == 'Enter') window.location.href = this.dataset.href;
            });
        });
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadUsers(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadUsers(1));
    document.getElementById('roleFilter').addEventListener('change', () => loadUsers(1));
    document.getElementById('perPageSelect').addEventListener('change', () => loadUsers(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('roleFilter').value = '';
        document.getElementById('perPageSelect').value = '15';
        loadUsers(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadUsers(currentPage));

    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sort = this.dataset.sort;
            if (currentSort == sort) {
                currentDirection = currentDirection == 'asc' ? 'desc' : 'asc';
            } else {
                currentSort = sort;
                currentDirection = 'asc';
            }

            document.querySelectorAll('.sort-icon').forEach(icon => icon.classList.remove('active'));
            this.querySelector('.sort-icon').classList.add('active');
            this.querySelector('.sort-icon').className = `ti ti-arrow-${currentDirection == 'asc' ? 'up' : 'down'} sort-icon active`;

            loadUsers(1);
        });
    });

    document.getElementById('userRole').addEventListener('change', function() {
        const cashboxesGroup = document.getElementById('cashboxesGroup');
        cashboxesGroup.style.display = (this.value == 'manager' || this.value == 'price_checker') ? 'none' : 'block';
    });

    window.openCreateModal = function() {
        editMode = false;
        document.getElementById('modalTitle').innerHTML = '<i class="ti ti-plus me-1"></i>إضافة مستخدم';
        document.getElementById('userId').value = '';
        document.getElementById('userForm').reset();
        document.getElementById('userStatus').checked = true;
        document.getElementById('userRole').value = 'cashier';
        document.getElementById('cashboxesGroup').style.display = 'block';
        document.getElementById('passwordGroup').style.display = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('userUsername').classList.remove('is-invalid', 'is-valid');
        document.querySelectorAll('.cashbox-checkbox').forEach(cb => cb.checked = false);
        isUsernameValid = true;
    };

    document.getElementById('userUsername').addEventListener('input', function() {
        clearTimeout(usernameCheckTimeout);
        const username = this.value.trim();

        if (!username) {
            this.classList.remove('is-valid', 'is-invalid');
            isUsernameValid = false;
            return;
        }

        usernameCheckTimeout = setTimeout(async () => {
            try {
                const userId = document.getElementById('userId').value;
                const params = new URLSearchParams({ username });
                if (userId) params.append('exclude_id', userId);

                const response = await fetch(`{{ route('users.check-username') }}?${params}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                    document.getElementById('usernameFeedback').textContent = 'اسم المستخدم مستخدم بالفعل';
                    isUsernameValid = false;
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                    isUsernameValid = true;
                }
            } catch (e) {
                console.error(e);
            }
        }, 300);
    });

    document.getElementById('userForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        const name = document.getElementById('userName').value.trim();
        const username = document.getElementById('userUsername').value.trim();
        const email = document.getElementById('userEmail').value.trim();
        const password = document.getElementById('userPassword').value;
        const role = document.getElementById('userRole').value;
        const status = document.getElementById('userStatus').checked;
        const userId = document.getElementById('userId').value;

        if (!name || !username) {
            showToast('يرجى ملء الحقول المطلوبة', 'warning');
            return;
        }

        if (!editMode && !password) {
            showToast('كلمة المرور مطلوبة', 'warning');
            return;
        }

        if (!isUsernameValid) {
            showToast('اسم المستخدم غير صالح', 'warning');
            return;
        }

        const cashboxIds = [];
        if (role != 'manager') {
            document.querySelectorAll('.cashbox-checkbox:checked').forEach(cb => {
                cashboxIds.push(parseInt(cb.value));
            });
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const url = editMode ? `/users/${userId}` : '{{ route("users.store") }}';
            const method = editMode ? 'PUT' : 'POST';

            const body = { name, username, email: email || null, role, status, cashbox_ids: cashboxIds };
            if (!editMode || password) {
                body.password = password;
            }

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const result = await response.json();

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
                showToast(result.message, 'success');
                loadUsers(currentPage);
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الحفظ', 'danger');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });

    window.loadUsers = loadUsers;
    loadUsers(1);
});
</script>
@endpush
