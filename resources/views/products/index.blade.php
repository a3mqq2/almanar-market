@extends('layouts.app')

@section('title', 'إدارة الأصناف')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">الأصناف</li>
@endsection

@push('styles')
<style>
    :root {
        --stats-border: var(--bs-border-color);
        --stats-bg-hover: var(--bs-tertiary-bg);
        --stats-label: var(--bs-secondary-color);
        --filter-bg: var(--bs-tertiary-bg);
        --placeholder-bg: var(--bs-secondary-bg);
        --placeholder-color: var(--bs-secondary-color);
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
    .product-img {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        object-fit: cover;
    }
    .product-img-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        background: var(--placeholder-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--placeholder-color);
    }
    .badge-stock {
        font-size: 0.7rem;
        padding: 0.25em 0.5em;
    }
    .table thead {
        background: var(--bs-tertiary-bg);
    }
    .table tfoot {
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
    .skeleton-img {
        width: 40px;
        height: 40px;
    }
    .profit-positive { color: var(--bs-success); }
    .profit-negative { color: var(--bs-danger); }
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
    .validation-feedback {
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
    .is-validating {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24'%3E%3Cpath fill='%236c757d' d='M12,4V2A10,10 0 0,0 2,12H4A8,8 0 0,1 12,4Z'%3E%3CanimateTransform attributeName='transform' type='rotate' from='0 12 12' to='360 12 12' dur='1s' repeatCount='indefinite'/%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 0.75rem center;
        background-size: 1rem;
        padding-left: 2.5rem;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">قائمة الأصناف</h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshBtn">
                <i class="ti ti-refresh"></i>
            </button>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createProductModal">
                <i class="ti ti-plus me-1"></i>إضافة صنف
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchInput" placeholder="بحث بالاسم أو الباركود...">
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
                    <select class="form-select form-select-sm" id="stockFilter">
                        <option value="">كل المخزون</option>
                        <option value="low">مخزون منخفض</option>
                        <option value="out">نفذ المخزون</option>
                        <option value="expiring">قريب الانتهاء</option>
                        <option value="expired">منتهي الصلاحية</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="perPageSelect">
                        <option value="15">15 صنف</option>
                        <option value="25">25 صنف</option>
                        <option value="50">50 صنف</option>
                        <option value="100">100 صنف</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="clearFilters">
                        <i class="ti ti-x me-1"></i>مسح الفلاتر
                    </button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0" id="productsTable">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th width="60">الصورة</th>
                        <th class="sortable" data-sort="name">
                            الاسم
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th class="sortable" data-sort="barcode">
                            الباركود
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
                        <th>الوحدة</th>
                        <th>المخزون</th>
                        <th>التكلفة</th>
                        <th>البيع</th>
                        <th>الربح</th>
                        <th>الهامش</th>
                        <th class="sortable" data-sort="status">
                            الحالة
                            <i class="ti ti-arrows-sort sort-icon"></i>
                        </th>
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
    </div>
</div>

<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-plus me-1"></i>إضافة صنف جديد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createProductForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الصنف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createProductName" name="name" required autofocus>
                        <div class="invalid-feedback">اسم الصنف مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الباركود</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="createProductBarcode" name="barcode" placeholder="اتركه فارغاً للتخطي">
                            <button type="button" class="btn btn-outline-secondary" id="generateBarcodeBtn">
                                <i class="ti ti-refresh me-1"></i>توليد
                            </button>
                        </div>
                        <div class="validation-feedback text-danger" id="barcodeFeedback" style="display: none;"></div>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم توجيهك لصفحة الصنف لإكمال بيانات الوحدات والأسعار
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="createProductSubmit">
                        <i class="ti ti-check me-1"></i>حفظ ومتابعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-package me-1"></i>إدارة سريعة للمخزون</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <h6 id="quickStockProductName"></h6>
                    <div class="text-muted small">المخزون الحالي: <span id="quickStockCurrentQty" class="fw-bold"></span></div>
                </div>
                <ul class="nav nav-tabs nav-fill mb-3" id="quickStockTabs">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#quickAdd">إضافة</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#quickRemove">خصم</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="quickAdd">
                        <form id="quickAddForm">
                            <input type="hidden" id="quickAddProductId">
                            <div class="mb-3">
                                <label class="form-label">الكمية</label>
                                <input type="number" class="form-control" name="quantity" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">السبب</label>
                                <select class="form-select" name="reason" required>
                                    <option value="شراء">شراء</option>
                                    <option value="مرتجع من عميل">مرتجع من عميل</option>
                                    <option value="تعديل جرد">تعديل جرد</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="ti ti-plus me-1"></i>إضافة
                            </button>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="quickRemove">
                        <form id="quickRemoveForm">
                            <input type="hidden" id="quickRemoveProductId">
                            <div class="mb-3">
                                <label class="form-label">الكمية</label>
                                <input type="number" class="form-control" name="quantity" min="0.0001" step="0.0001" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">نوع العملية</label>
                                <select class="form-select" name="type" required>
                                    <option value="sale">بيع</option>
                                    <option value="damage">تالف</option>
                                    <option value="loss">فقدان</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">السبب</label>
                                <input type="text" class="form-control" name="reason" required>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="ti ti-minus me-1"></i>خصم
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger"><i class="ti ti-alert-triangle me-1"></i>تأكيد الحذف</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>هل أنت متأكد من حذف:</p>
                <p class="fw-bold" id="deleteProductName"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">
                    <i class="ti ti-trash me-1"></i>حذف
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';
    let currentPage = 1;
    let currentSort = 'created_at';
    let currentDirection = 'desc';
    let searchTimeout = null;
    let deleteProductId = null;
    let barcodeCheckTimeout = null;
    let isBarcodeValid = true;

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
        let skeletonHtml = '';
        for (let i = 0; i < 5; i++) {
            skeletonHtml += `
                <tr class="skeleton-row">
                    <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                    <td><div class="skeleton skeleton-img"></div></td>
                    <td><div class="skeleton skeleton-text"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 40px;"></div></td>
                    <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                </tr>
            `;
        }
        tbody.innerHTML = skeletonHtml;
    }

    function loadProducts(page = 1) {
        showSkeleton();
        currentPage = page;

        const params = new URLSearchParams();
        params.append('page', page);
        params.append('per_page', document.getElementById('perPageSelect').value);
        params.append('sort', currentSort);
        params.append('direction', currentDirection);

        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const stockFilter = document.getElementById('stockFilter').value;

        if (search) params.append('search', search);
        if (status) params.append('status', status);
        if (stockFilter) params.append('stock_filter', stockFilter);

        fetch(`{{ route('products.index') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            renderProducts(result.data, result.meta);
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تحميل البيانات', 'danger');
        });
    }

    function renderProducts(products, meta) {
        const tbody = document.getElementById('productsTableBody');

        if (products.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11">
                        <div class="empty-state">
                            <i class="ti ti-package-off d-block mb-2"></i>
                            <p class="text-muted mb-0">لا توجد أصناف مطابقة للبحث</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('paginationInfo').textContent = '';
            document.getElementById('paginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        products.forEach((product, index) => {
            const rowNum = meta.from + index;

            let badges = '';
            if (product.is_out_of_stock) {
                badges += '<span class="badge bg-danger badge-stock me-1">نفذ</span>';
            } else if (product.is_low_stock) {
                badges += '<span class="badge bg-warning badge-stock me-1">منخفض</span>';
            }
            if (product.has_expired) {
                badges += '<span class="badge bg-dark badge-stock me-1">منتهي</span>';
            } else if (product.has_expiring) {
                badges += '<span class="badge bg-info badge-stock me-1">قريب الانتهاء</span>';
            }

            const profitClass = product.profit >= 0 ? 'profit-positive' : 'profit-negative';
            const marginClass = product.margin >= 0 ? 'profit-positive' : 'profit-negative';

            html += `
                <tr class="clickable-row" data-href="/products/${product.id}" tabindex="0">
                    <td>${rowNum}</td>
                    <td>
                        ${product.image
                            ? `<img src="${product.image}" alt="${product.name}" class="product-img" loading="lazy">`
                            : `<div class="product-img-placeholder"><i class="ti ti-package"></i></div>`
                        }
                    </td>
                    <td>
                        <div class="fw-medium">${product.name}</div>
                        ${badges}
                    </td>
                    <td><code>${product.barcode || '-'}</code></td>
                    <td>${product.unit_name}</td>
                    <td class="fw-bold ${product.is_out_of_stock ? 'text-danger' : (product.is_low_stock ? 'text-warning' : '')}">
                        ${parseFloat(product.stock).toFixed(2)}
                    </td>
                    <td>${parseFloat(product.cost_price).toFixed(2)}</td>
                    <td class="fw-medium">${parseFloat(product.sell_price).toFixed(2)}</td>
                    <td class="${profitClass}">${parseFloat(product.profit).toFixed(2)}</td>
                    <td class="${marginClass}">${product.margin}%</td>
                    <td>
                        <span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">
                            ${product.status === 'active' ? 'نشط' : 'غير نشط'}
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
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} صنف`;

        const paginationLinks = document.getElementById('paginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadProducts(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            let startPage = Math.max(1, meta.current_page - 2);
            let endPage = Math.min(meta.last_page, meta.current_page + 2);

            if (startPage > 1) {
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadProducts(1); return false;">1</a></li>`;
                if (startPage > 2) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadProducts(${i}); return false;">${i}</a>
                    </li>
                `;
            }

            if (endPage < meta.last_page) {
                if (endPage < meta.last_page - 1) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
                html += `<li class="page-item"><a class="page-link" href="#" onclick="loadProducts(${meta.last_page}); return false;">${meta.last_page}</a></li>`;
            }

            html += `
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadProducts(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadProducts(1), 400);
    });

    document.getElementById('statusFilter').addEventListener('change', () => loadProducts(1));
    document.getElementById('stockFilter').addEventListener('change', () => loadProducts(1));
    document.getElementById('perPageSelect').addEventListener('change', () => loadProducts(1));

    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('searchInput').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('stockFilter').value = '';
        document.getElementById('perPageSelect').value = '15';
        loadProducts(1);
    });

    document.getElementById('refreshBtn').addEventListener('click', () => loadProducts(currentPage));

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

            loadProducts(1);
        });
    });

    const createProductModal = document.getElementById('createProductModal');
    const createProductForm = document.getElementById('createProductForm');
    const createProductName = document.getElementById('createProductName');
    const createProductBarcode = document.getElementById('createProductBarcode');
    const barcodeFeedback = document.getElementById('barcodeFeedback');
    const createProductSubmit = document.getElementById('createProductSubmit');

    createProductModal.addEventListener('shown.bs.modal', function() {
        createProductName.focus();
        createProductForm.reset();
        createProductName.classList.remove('is-invalid');
        createProductBarcode.classList.remove('is-invalid', 'is-valid');
        barcodeFeedback.style.display = 'none';
        isBarcodeValid = true;
    });

    document.getElementById('generateBarcodeBtn').addEventListener('click', async function() {
        try {
            const response = await fetch('{{ route("products.generate-barcode") }}');
            const result = await response.json();
            createProductBarcode.value = result.barcode;
            createProductBarcode.classList.remove('is-invalid');
            createProductBarcode.classList.add('is-valid');
            barcodeFeedback.style.display = 'none';
            isBarcodeValid = true;
        } catch (error) {
            showToast('حدث خطأ في توليد الباركود', 'danger');
        }
    });

    createProductBarcode.addEventListener('input', function() {
        clearTimeout(barcodeCheckTimeout);
        const barcode = this.value.trim();

        if (!barcode) {
            this.classList.remove('is-invalid', 'is-valid', 'is-validating');
            barcodeFeedback.style.display = 'none';
            isBarcodeValid = true;
            return;
        }

        this.classList.add('is-validating');

        barcodeCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("products.check-barcode") }}?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();
                this.classList.remove('is-validating');

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    barcodeFeedback.textContent = 'هذا الباركود مستخدم بالفعل';
                    barcodeFeedback.style.display = 'block';
                    isBarcodeValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    barcodeFeedback.style.display = 'none';
                    isBarcodeValid = true;
                }
            } catch (error) {
                this.classList.remove('is-validating');
            }
        }, 300);
    });

    createProductForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = createProductName.value.trim();
        const barcode = createProductBarcode.value.trim();

        if (!name) {
            createProductName.classList.add('is-invalid');
            createProductName.focus();
            return;
        }

        if (!isBarcodeValid) {
            createProductBarcode.focus();
            return;
        }

        createProductSubmit.disabled = true;
        createProductSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch('{{ route("products.quick-store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ name, barcode })
            });

            const result = await response.json();

            if (result.success) {
                window.location.href = `/products/${result.product_id}#units`;
            } else {
                showToast(result.message, 'danger');
                createProductSubmit.disabled = false;
                createProductSubmit.innerHTML = '<i class="ti ti-check me-1"></i>حفظ ومتابعة';
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
            createProductSubmit.disabled = false;
            createProductSubmit.innerHTML = '<i class="ti ti-check me-1"></i>حفظ ومتابعة';
        }
    });

    createProductName.addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });

    window.openQuickStock = function(id, name, stock) {
        document.getElementById('quickStockProductName').textContent = name;
        document.getElementById('quickStockCurrentQty').textContent = parseFloat(stock).toFixed(2);
        document.getElementById('quickAddProductId').value = id;
        document.getElementById('quickRemoveProductId').value = id;
        document.getElementById('quickAddForm').reset();
        document.getElementById('quickRemoveForm').reset();
        new bootstrap.Modal(document.getElementById('quickStockModal')).show();
    };

    document.getElementById('quickAddForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const productId = document.getElementById('quickAddProductId').value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`/products/${productId}/inventory/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickStockModal')).hide();
                loadProducts(currentPage);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    document.getElementById('quickRemoveForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const productId = document.getElementById('quickRemoveProductId').value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`/products/${productId}/inventory/remove`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('quickStockModal')).hide();
                loadProducts(currentPage);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    window.duplicateProduct = async function(id) {
        try {
            const response = await fetch(`/products/${id}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                loadProducts(currentPage);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في نسخ الصنف', 'danger');
        }
    };

    window.confirmDelete = function(id, name) {
        deleteProductId = id;
        document.getElementById('deleteProductName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    };

    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!deleteProductId) return;

        const btn = this;
        btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('_token', csrfToken);

            const response = await fetch(`/products/${deleteProductId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
                loadProducts(currentPage);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في حذف الصنف', 'danger');
        }

        btn.disabled = false;
    });

    window.loadProducts = loadProducts;
    loadProducts(1);
});
</script>
@endpush
