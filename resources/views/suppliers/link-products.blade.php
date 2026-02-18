@extends('layouts.app')

@section('title', 'ربط موردين بالمنتجات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">الموردين</a></li>
    <li class="breadcrumb-item active">ربط منتجات</li>
@endsection

@push('styles')
<style>
    :root {
        --filter-bg: var(--bs-tertiary-bg);
        --skeleton-bg: var(--bs-secondary-bg);
        --empty-icon: var(--bs-secondary-color);
    }

    .filter-section {
        background: var(--filter-bg);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .supplier-select-section {
        background: var(--filter-bg);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .table thead {
        background: var(--bs-tertiary-bg);
        position: sticky;
        top: 0;
        z-index: 1;
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
    .product-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .product-row:hover {
        background-color: var(--bs-tertiary-bg) !important;
    }
    .product-row.selected {
        background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
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
    .skeleton-text {
        height: 16px;
        width: 80%;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    .empty-state i {
        font-size: 4rem;
        color: var(--empty-icon);
    }
    .toast-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
    }
    .selection-bar {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        position: sticky;
        bottom: 1rem;
        z-index: 10;
        display: none;
    }
    .selection-bar.active {
        display: flex;
    }
    .table-container {
        max-height: 60vh;
        overflow-y: auto;
    }
    .form-check-input {
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">ربط موردين بالمنتجات</h5>
    </div>
    <div class="card-body">
        <div class="supplier-select-section">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-medium">اختر المورد <span class="text-danger">*</span></label>
                    <select class="form-select" id="supplierSelect">
                        <option value="">-- اختر مورد --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 align-items-center h-100 pt-4">
                        <span class="badge bg-primary fs-6" id="selectedSupplierBadge" style="display:none;"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم أو الباركود...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="supplierFilter">
                        <option value="">كل المنتجات</option>
                        <option value="none">بدون مورد</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="selectAllBtn">
                        <i class="ti ti-checks me-1"></i>تحديد الكل
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearSelection">
                        <i class="ti ti-x me-1"></i>إلغاء التحديد
                    </button>
                </div>
            </div>
        </div>

        <div class="table-container">
            <table class="table table-hover mb-0" id="productsTable">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="checkAll">
                        </th>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الباركود</th>
                        <th>المورد الحالي</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2" id="paginationContainer">
            <div class="text-muted small" id="paginationInfo"></div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
            </nav>
        </div>

        <div class="selection-bar justify-content-between align-items-center mt-3" id="selectionBar">
            <div>
                <span class="fw-medium">تم تحديد <span class="badge bg-primary" id="selectedCount">0</span> منتج</span>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="saveBtn" disabled>
                <i class="ti ti-check me-1"></i>حفظ الربط
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let selectedProducts = new Set();
    let searchTimeout = null;
    let currentPage = 1;
    let allVisibleIds = [];

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
        const tbody = document.getElementById('productsTableBody');
        let html = '';
        for (let i = 0; i < 8; i++) {
            html += `
                <tr>
                    <td><div class="skeleton" style="width:18px;height:18px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:20px;"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width:80px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
    }

    function loadProducts(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);

        const search = document.getElementById('searchInput').value;
        const supplierFilter = document.getElementById('supplierFilter').value;

        if (search) params.append('search', search);
        if (supplierFilter) params.append('supplier_filter', supplierFilter);

        fetch(`{{ route('suppliers.search-products') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderProducts(result.data, result.meta);
        })
        .catch(() => {
            showToast('حدث خطأ في تحميل المنتجات', 'danger');
        });
    }

    function renderProducts(products, meta) {
        const tbody = document.getElementById('productsTableBody');
        allVisibleIds = products.map(p => p.id);

        if (products.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="ti ti-package-off d-block mb-2"></i>
                            <p class="text-muted mb-0">لا توجد منتجات</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            updateCheckAll();
            return;
        }

        let html = '';
        products.forEach((product, index) => {
            const isSelected = selectedProducts.has(product.id);
            const rowNum = (meta.from || 0) + index;
            html += `
                <tr class="product-row ${isSelected ? 'selected' : ''}" data-id="${product.id}">
                    <td>
                        <input type="checkbox" class="form-check-input product-check"
                               value="${product.id}" ${isSelected ? 'checked' : ''}>
                    </td>
                    <td>${rowNum}</td>
                    <td class="fw-medium">${product.name}</td>
                    <td dir="ltr" class="text-end">${product.barcode || '-'}</td>
                    <td>
                        ${product.supplier_name
                            ? `<span class="badge bg-secondary">${product.supplier_name}</span>`
                            : '<span class="text-muted">-</span>'}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        bindRowEvents();
        updateCheckAll();
        renderPagination(meta);
    }

    function bindRowEvents() {
        document.querySelectorAll('.product-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.form-check-input')) return;
                const checkbox = this.querySelector('.product-check');
                checkbox.checked = !checkbox.checked;
                toggleProduct(parseInt(this.dataset.id), checkbox.checked);
                this.classList.toggle('selected', checkbox.checked);
            });
        });

        document.querySelectorAll('.product-check').forEach(cb => {
            cb.addEventListener('change', function() {
                const row = this.closest('.product-row');
                toggleProduct(parseInt(this.value), this.checked);
                row.classList.toggle('selected', this.checked);
            });
        });
    }

    function toggleProduct(id, selected) {
        if (selected) {
            selectedProducts.add(id);
        } else {
            selectedProducts.delete(id);
        }
        updateSelectionBar();
        updateCheckAll();
    }

    function updateSelectionBar() {
        const bar = document.getElementById('selectionBar');
        const count = selectedProducts.size;
        const supplier = document.getElementById('supplierSelect').value;

        document.getElementById('selectedCount').textContent = count;

        if (count > 0) {
            bar.classList.add('active');
        } else {
            bar.classList.remove('active');
        }

        document.getElementById('saveBtn').disabled = count === 0 || !supplier;
    }

    function updateCheckAll() {
        const checkAll = document.getElementById('checkAll');
        const allChecked = allVisibleIds.length > 0 && allVisibleIds.every(id => selectedProducts.has(id));
        const someChecked = allVisibleIds.some(id => selectedProducts.has(id));
        checkAll.checked = allChecked;
        checkAll.indeterminate = someChecked && !allChecked;
    }

    function renderPagination(meta) {
        document.getElementById('paginationInfo').textContent =
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} منتج`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${meta.current_page - 1}">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${meta.last_page}">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${meta.current_page + 1}">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;

        paginationLinks.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadProducts(parseInt(this.dataset.page));
            });
        });
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadProducts(1), 400);
    });

    document.getElementById('supplierFilter').addEventListener('change', () => loadProducts(1));

    document.getElementById('checkAll').addEventListener('change', function() {
        const checked = this.checked;
        allVisibleIds.forEach(id => {
            if (checked) {
                selectedProducts.add(id);
            } else {
                selectedProducts.delete(id);
            }
        });

        document.querySelectorAll('.product-check').forEach(cb => {
            cb.checked = checked;
            cb.closest('.product-row').classList.toggle('selected', checked);
        });

        updateSelectionBar();
    });

    document.getElementById('selectAllBtn').addEventListener('click', function() {
        allVisibleIds.forEach(id => selectedProducts.add(id));
        document.querySelectorAll('.product-check').forEach(cb => {
            cb.checked = true;
            cb.closest('.product-row').classList.toggle('selected', true);
        });
        updateSelectionBar();
        updateCheckAll();
    });

    document.getElementById('clearSelection').addEventListener('click', function() {
        selectedProducts.clear();
        document.querySelectorAll('.product-check').forEach(cb => {
            cb.checked = false;
            cb.closest('.product-row').classList.toggle('selected', false);
        });
        updateSelectionBar();
        updateCheckAll();
    });

    document.getElementById('supplierSelect').addEventListener('change', function() {
        const badge = document.getElementById('selectedSupplierBadge');
        if (this.value) {
            badge.textContent = this.options[this.selectedIndex].text;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
        updateSelectionBar();
    });

    document.getElementById('saveBtn').addEventListener('click', function() {
        const supplierId = document.getElementById('supplierSelect').value;
        const productIds = Array.from(selectedProducts);

        if (!supplierId) {
            showToast('اختر مورد أولاً', 'warning');
            return;
        }

        if (productIds.length === 0) {
            showToast('اختر منتجات أولاً', 'warning');
            return;
        }

        const supplierName = document.getElementById('supplierSelect').options[
            document.getElementById('supplierSelect').selectedIndex
        ].text;

        Swal.fire({
            title: 'تأكيد الربط',
            html: `سيتم ربط <strong>${productIds.length}</strong> منتج بالمورد <strong>${supplierName}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'تأكيد',
            cancelButtonText: 'إلغاء',
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

            try {
                const response = await fetch('{{ route("suppliers.assign-supplier") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        supplier_id: supplierId,
                        product_ids: productIds,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    selectedProducts.clear();
                    updateSelectionBar();
                    loadProducts(currentPage);
                } else {
                    showToast(data.message || 'حدث خطأ', 'danger');
                }
            } catch {
                showToast('حدث خطأ في الاتصال', 'danger');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ الربط';
        });
    });

    loadProducts(1);
});
</script>
@endpush
