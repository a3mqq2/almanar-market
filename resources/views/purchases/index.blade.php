@extends('layouts.app')

@section('title', 'سجل المشتريات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">المشتريات</li>
@endsection

@push('styles')
<style>
    .filter-section {
        background: var(--bs-tertiary-bg);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .table thead { background: var(--bs-tertiary-bg); }
    .table th { font-weight: 600; font-size: 0.85rem; white-space: nowrap; }
    .table td { vertical-align: middle; font-size: 0.9rem; }
    .clickable-row { cursor: pointer; transition: background-color 0.15s ease; }
    .clickable-row:hover { background-color: var(--bs-tertiary-bg) !important; }
    .empty-state { padding: 3rem; text-align: center; }
    .empty-state i { font-size: 4rem; color: var(--bs-secondary-color); }
    .product-tag {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        margin: 0.1rem;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
    }
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: var(--bs-body-bg);
        opacity: 0.7;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5;
        border-radius: 8px;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="ti ti-shopping-cart me-1"></i>سجل المشتريات</h5>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="clearFiltersBtn">
            <i class="ti ti-x me-1"></i>مسح الفلاتر
        </button>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small mb-1">بحث</label>
                    <input type="text" class="form-control form-control-sm" id="fSearch" placeholder="اسم منتج، مورد، رقم فاتورة...">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">المورد</label>
                    <select class="form-select form-select-sm" id="fSupplier">
                        <option value="">الكل</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">الدفع</label>
                    <select class="form-select form-select-sm" id="fPayment">
                        <option value="">الكل</option>
                        <option value="cash">نقدي</option>
                        <option value="credit">آجل</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">الحالة</label>
                    <select class="form-select form-select-sm" id="fStatus">
                        <option value="">الكل</option>
                        <option value="approved">معتمدة</option>
                        <option value="cancelled">ملغاة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">الفترة</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control form-control-sm" id="fDateFrom">
                        <span class="input-group-text">إلى</span>
                        <input type="date" class="form-control form-control-sm" id="fDateTo">
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="searchBtn">
                        <i class="ti ti-search"></i>
                    </button>
                </div>
            </div>
        </div>


        <div class="position-relative">
            <div class="loading-overlay d-none" id="loading">
                <div class="spinner-border text-primary"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>المنتج</th>
                            <th>المورد</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                            <th>الدفع</th>
                            <th>المدفوع</th>
                            <th>المتبقي</th>
                            <th>الحالة</th>
                            <th>بواسطة</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="12" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                جاري التحميل...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3 d-none" id="paginationRow">
                <div class="text-muted small" id="paginationInfo"></div>
                <nav><ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul></nav>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let debounceTimer = null;

    const els = {
        search: document.getElementById('fSearch'),
        supplier: document.getElementById('fSupplier'),
        payment: document.getElementById('fPayment'),
        status: document.getElementById('fStatus'),
        dateFrom: document.getElementById('fDateFrom'),
        dateTo: document.getElementById('fDateTo'),
    };

    function getFilters() {
        return {
            search: els.search.value.trim(),
            supplier_id: els.supplier.value,
            payment_type: els.payment.value,
            status: els.status.value,
            date_from: els.dateFrom.value,
            date_to: els.dateTo.value,
        };
    }

    function hasActiveFilters() {
        const f = getFilters();
        return Object.values(f).some(v => v !== '');
    }

    function updateClearBtn() {
        document.getElementById('clearFiltersBtn').classList.toggle('d-none', !hasActiveFilters());
    }

    async function loadData(page) {
        currentPage = page || 1;
        const filters = getFilters();
        const params = new URLSearchParams();

        Object.entries(filters).forEach(([k, v]) => { if (v) params.set(k, v); });
        params.set('page', currentPage);

        document.getElementById('loading').classList.remove('d-none');
        updateClearBtn();

        try {
            const response = await fetch(`{{ route('purchases.index') }}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.success) {
                renderTable(data.purchases);
                renderPagination(data.purchases);
            }
        } catch (e) {
            document.getElementById('tableBody').innerHTML = `
                <tr><td colspan="12" class="text-center text-danger py-4">حدث خطأ في تحميل البيانات</td></tr>
            `;
        }

        document.getElementById('loading').classList.add('d-none');
    }

    function renderTable(paginated) {
        const tbody = document.getElementById('tableBody');
        const rows = paginated.data;

        if (!rows.length) {
            tbody.innerHTML = `
                <tr><td colspan="12">
                    <div class="empty-state">
                        <i class="ti ti-shopping-cart-off d-block mb-2"></i>
                        <p class="text-muted mb-1">لا توجد عمليات شراء</p>
                        <small class="text-muted">يمكنك الشراء من صفحة أي منتج عبر تاب المخزون</small>
                    </div>
                </td></tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map(p => {
            const items = p.items || [];
            const productTags = items.map(i =>
                `<span class="product-tag">${esc(i.product_name)} <small class="text-muted">(${num(i.quantity)} ${esc(i.unit_name || '')})</small></span>`
            ).join('');

            const qtyCell = items.length === 1
                ? num(items[0].quantity)
                : `<span class="text-muted">${items.length} أصناف</span>`;

            const priceCell = items.length === 1 ? num(items[0].unit_price) : '-';

            const payBadge = p.payment_type === 'credit'
                ? '<span class="badge bg-warning text-dark">آجل</span>'
                : '<span class="badge bg-success">نقدي</span>';

            const remClass = parseFloat(p.remaining_amount) > 0 ? 'text-danger fw-bold' : 'text-muted';

            return `<tr class="clickable-row" data-href="${p.show_url}">
                <td class="text-muted">${p.id}</td>
                <td>${p.purchase_date}</td>
                <td style="max-width:250px">${productTags}</td>
                <td>${esc(p.supplier_name)}</td>
                <td>${qtyCell}</td>
                <td>${priceCell}</td>
                <td class="fw-bold">${num(p.total)}</td>
                <td>${payBadge}</td>
                <td class="text-success">${num(p.paid_amount)}</td>
                <td class="${remClass}">${num(p.remaining_amount)}</td>
                <td><span class="badge bg-${p.status_color}">${esc(p.status_arabic)}</span></td>
                <td class="text-muted small">${esc(p.creator_name || '-')}</td>
            </tr>`;
        }).join('');

        tbody.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });
    }

    function renderPagination(paginated) {
        const row = document.getElementById('paginationRow');
        const info = document.getElementById('paginationInfo');
        const links = document.getElementById('paginationLinks');

        if (paginated.last_page <= 1) {
            row.classList.add('d-none');
            return;
        }

        row.classList.remove('d-none');
        info.textContent = `عرض ${paginated.from} إلى ${paginated.to} من ${paginated.total}`;

        let html = '';
        const cp = paginated.current_page;
        const lp = paginated.last_page;

        html += `<li class="page-item ${cp === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${cp - 1}">‹</a></li>`;

        for (let i = 1; i <= lp; i++) {
            if (i === 1 || i === lp || (i >= cp - 2 && i <= cp + 2)) {
                html += `<li class="page-item ${i === cp ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            } else if (i === cp - 3 || i === cp + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        html += `<li class="page-item ${cp === lp ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${cp + 1}">›</a></li>`;

        links.innerHTML = html;

        links.querySelectorAll('a[data-page]').forEach(a => {
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.dataset.page);
                if (page >= 1 && page <= lp) loadData(page);
            });
        });
    }

    function num(v) {
        return parseFloat(v || 0).toFixed(2);
    }

    function esc(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    document.getElementById('searchBtn').addEventListener('click', () => loadData(1));

    els.search.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); loadData(1); }
    });

    els.search.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadData(1), 400);
    });

    [els.supplier, els.payment, els.status, els.dateFrom, els.dateTo].forEach(el => {
        el.addEventListener('change', () => loadData(1));
    });

    document.getElementById('clearFiltersBtn').addEventListener('click', function() {
        Object.values(els).forEach(el => { el.value = ''; });
        loadData(1);
    });

    loadData(1);
});
</script>
@endpush
