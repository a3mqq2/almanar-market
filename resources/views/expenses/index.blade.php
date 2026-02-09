@extends('layouts.app')

@section('title', 'المصروفات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">المصروفات</li>
@endsection

@push('styles')
<style>
    .stat-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
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
    }
    .clickable-row {
        cursor: pointer;
    }
    .clickable-row:hover {
        background: var(--bs-tertiary-bg) !important;
    }
    .sort-header {
        cursor: pointer;
        user-select: none;
    }
    .sort-header:hover {
        background: var(--bs-tertiary-bg);
    }
    .sort-header .sort-icon {
        opacity: 0.3;
        font-size: 0.75rem;
    }
    .sort-header.active .sort-icon {
        opacity: 1;
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
    [data-bs-theme="dark"] .table-hover tbody tr:hover {
        background-color: #2b3035;
    }
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background-color: #2b3035;
        border-color: #373b3e;
        color: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-2">
        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>مصروف جديد
        </a>
        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#categoriesModal">
            <i class="ti ti-category me-1"></i>التصنيفات
        </button>
    </div>
</div>



<div class="filter-card mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <select class="form-select form-select-sm" id="filterCategory">
                <option value="">كل التصنيفات</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-sm" id="filterCashbox">
                <option value="">كل الخزائن</option>
                @foreach($cashboxes as $cashbox)
                    <option value="{{ $cashbox->id }}">{{ $cashbox->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control form-control-sm" id="filterDateFrom" placeholder="من تاريخ">
        </div>
        <div class="col-md-3">
            <input type="date" class="form-control form-control-sm" id="filterDateTo" placeholder="إلى تاريخ">
        </div>
    </div>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="بحث بالرقم أو العنوان أو التصنيف...">
            </div>
        </div>
        <div class="col-md-8 text-end">
            <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                <i class="ti ti-refresh me-1"></i>إعادة تعيين
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="sort-header" data-sort="id" onclick="sortBy('id')">#<i class="ti ti-arrows-sort sort-icon ms-1"></i></th>
                        <th>الرقم المرجعي</th>
                        <th>العنوان</th>
                        <th>التصنيف</th>
                        <th class="sort-header" data-sort="amount" onclick="sortBy('amount')">المبلغ<i class="ti ti-arrows-sort sort-icon ms-1"></i></th>
                        <th>الخزينة</th>
                        <th class="sort-header" data-sort="expense_date" onclick="sortBy('expense_date')">التاريخ<i class="ti ti-arrows-sort sort-icon ms-1"></i></th>
                        <th>بواسطة</th>
                    </tr>
                </thead>
                <tbody id="expensesTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <span class="spinner-border spinner-border-sm me-2"></span>جاري التحميل...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3" id="paginationContainer">
    <div class="text-muted" id="paginationInfo"></div>
    <nav>
        <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </nav>
</div>

<div class="modal fade" id="categoriesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-category me-2"></i>تصنيفات المصروفات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="newCategoryName" placeholder="اسم التصنيف الجديد">
                        <button class="btn btn-primary" onclick="addCategory()">
                            <i class="ti ti-plus"></i>
                        </button>
                    </div>
                </div>
                <div id="categoriesList"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
let currentSort = 'expense_date';
let currentDirection = 'desc';

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
        category_id: document.getElementById('filterCategory').value,
        cashbox_id: document.getElementById('filterCashbox').value,
        date_from: document.getElementById('filterDateFrom').value,
        date_to: document.getElementById('filterDateTo').value,
        search: document.getElementById('searchInput').value,
        sort: currentSort,
        direction: currentDirection,
        page: currentPage,
    };
}

async function loadExpenses() {
    const filters = getFilters();
    const params = new URLSearchParams(filters);

    try {
        const response = await fetch(`{{ route('expenses.filter') }}?${params}`);
        const data = await response.json();

        if (data.success) {
            renderExpenses(data.expenses);
            renderStats(data.stats);
            renderPagination(data.pagination);
        }
    } catch (error) {
        showToast('حدث خطأ في تحميل البيانات', 'danger');
    }
}

function renderExpenses(expenses) {
    const tbody = document.getElementById('expensesTableBody');

    if (expenses.length == 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">
                    <i class="ti ti-receipt-off fs-1 d-block mb-2 opacity-50"></i>
                    لا توجد مصروفات
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = expenses.map(expense => `
        <tr class="clickable-row" onclick="window.location='{{ url('expenses') }}/${expense.id}'">
            <td>${expense.id}</td>
            <td class="fw-medium">${expense.reference_number}</td>
            <td>${expense.title}</td>
            <td><span class="badge bg-secondary">${expense.category}</span></td>
            <td class="fw-medium">${Number(expense.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td>${expense.cashbox}</td>
            <td>${expense.expense_date}</td>
            <td>${expense.creator}</td>
        </tr>
    `).join('');
}

function renderStats(stats) {
    document.getElementById('statTotal').textContent = stats.total_count.toLocaleString();
    document.getElementById('statAmount').textContent = Number(stats.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2});
}

function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const nav = document.getElementById('pagination');

    if (pagination.total == 0) {
        info.textContent = '';
        nav.innerHTML = '';
        return;
    }

    info.textContent = `عرض ${pagination.from} - ${pagination.to} من ${pagination.total}`;

    let pages = '';
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i == 1 || i == pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            pages += `<li class="page-item ${i == pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
            </li>`;
        } else if (i == pagination.current_page - 3 || i == pagination.current_page + 3) {
            pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    nav.innerHTML = `
        <li class="page-item ${pagination.current_page == 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1})"><i class="ti ti-chevron-right"></i></a>
        </li>
        ${pages}
        <li class="page-item ${pagination.current_page == pagination.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1})"><i class="ti ti-chevron-left"></i></a>
        </li>
    `;
}

function goToPage(page) {
    currentPage = page;
    loadExpenses();
}

function sortBy(field) {
    if (currentSort == field) {
        currentDirection = currentDirection == 'asc' ? 'desc' : 'asc';
    } else {
        currentSort = field;
        currentDirection = 'desc';
    }

    document.querySelectorAll('.sort-header').forEach(el => {
        el.classList.remove('active');
        el.querySelector('.sort-icon').className = 'ti ti-arrows-sort sort-icon ms-1';
    });

    const header = document.querySelector(`[data-sort="${field}"]`);
    if (header) {
        header.classList.add('active');
        header.querySelector('.sort-icon').className = `ti ti-sort-${currentDirection == 'asc' ? 'ascending' : 'descending'} sort-icon ms-1`;
    }

    currentPage = 1;
    loadExpenses();
}

function resetFilters() {
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterCashbox').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('searchInput').value = '';
    currentPage = 1;
    currentSort = 'expense_date';
    currentDirection = 'desc';
    loadExpenses();
}

let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadExpenses();
    }, 300);
});

['filterCategory', 'filterCashbox', 'filterDateFrom', 'filterDateTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        currentPage = 1;
        loadExpenses();
    });
});

async function loadCategories() {
    try {
        const response = await fetch('{{ route('expense-categories.index') }}');
        const data = await response.json();

        if (data.success) {
            const list = document.getElementById('categoriesList');
            list.innerHTML = data.categories.map(cat => `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <span class="fw-medium">${cat.name}</span>
                        <small class="text-muted ms-2">(${cat.expenses_count} مصروف)</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary me-1" onclick="editCategory(${cat.id}, '${cat.name}')">
                            <i class="ti ti-edit"></i>
                        </button>
                        ${cat.expenses_count == 0 ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(${cat.id})">
                            <i class="ti ti-trash"></i>
                        </button>
                        ` : ''}
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        showToast('حدث خطأ في تحميل التصنيفات', 'danger');
    }
}

async function addCategory() {
    const name = document.getElementById('newCategoryName').value.trim();
    if (!name) {
        showToast('يرجى إدخال اسم التصنيف', 'warning');
        return;
    }

    try {
        const response = await fetch('{{ route('expense-categories.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ name }),
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            document.getElementById('newCategoryName').value = '';
            loadCategories();
            location.reload();
        } else {
            showToast(data.message, 'danger');
        }
    } catch (error) {
        showToast('حدث خطأ', 'danger');
    }
}

async function editCategory(id, currentName) {
    const { value: newName } = await Swal.fire({
        title: 'تعديل التصنيف',
        input: 'text',
        inputValue: currentName,
        showCancelButton: true,
        confirmButtonText: 'حفظ',
        cancelButtonText: 'إلغاء',
        inputValidator: (value) => {
            if (!value) return 'يرجى إدخال الاسم';
        }
    });

    if (newName) {
        try {
            const response = await fetch(`{{ url('expense-categories') }}/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ name: newName }),
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                loadCategories();
                location.reload();
            } else {
                showToast(data.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }
    }
}

async function deleteCategory(id) {
    const result = await Swal.fire({
        title: 'تأكيد الحذف',
        text: 'هل أنت متأكد من حذف هذا التصنيف؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'حذف',
        cancelButtonText: 'إلغاء',
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`{{ url('expense-categories') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                loadCategories();
                location.reload();
            } else {
                showToast(data.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }
    }
}

document.getElementById('categoriesModal').addEventListener('show.bs.modal', loadCategories);

loadExpenses();
</script>
@endpush
