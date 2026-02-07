@extends('layouts.app')

@section('title', 'إدارة المشتريات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">المشتريات</li>
@endsection

@push('styles')
<style>
    :root {
        --stats-border: var(--bs-border-color);
        --stats-bg-hover: var(--bs-tertiary-bg);
        --stats-label: var(--bs-secondary-color);
        --filter-bg: var(--bs-tertiary-bg);
    }

    .stats-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .stats-card {
        flex: 1;
        min-width: 150px;
        border: 1px solid var(--stats-border);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        transition: all 0.2s;
        background: var(--bs-body-bg);
    }
    .stats-card:hover {
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
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    .empty-state i {
        font-size: 4rem;
        color: var(--bs-secondary-color);
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">فواتير المشتريات</h5>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i>فاتورة جديدة
        </a>
    </div>
    <div class="card-body">
        <div class="filter-section">
            <form method="GET" action="{{ route('purchases.index') }}" id="filterForm">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="search"
                               value="{{ request('search') }}" placeholder="بحث برقم الفاتورة...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="supplier_id">
                            <option value="">كل الموردين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">كل الحالات</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>معتمدة</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="date_from"
                               value="{{ request('date_from') }}" placeholder="من تاريخ">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="date_to"
                               value="{{ request('date_to') }}" placeholder="إلى تاريخ">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-secondary btn-sm w-100">
                            <i class="ti ti-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رقم الفاتورة</th>
                        <th>المورد</th>
                        <th>التاريخ</th>
                        <th>نوع الدفع</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                        <th>بواسطة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $purchase)
                        <tr class="clickable-row" data-href="{{ route('purchases.show', $purchase) }}">
                            <td>{{ $purchase->id }}</td>
                            <td>
                                <span class="fw-medium">{{ $purchase->invoice_number ?: '-' }}</span>
                            </td>
                            <td>{{ $purchase->supplier->name }}</td>
                            <td>{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                            <td>{{ $purchase->payment_type_arabic }}</td>
                            <td class="fw-bold">{{ number_format($purchase->total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $purchase->status_color }}">
                                    {{ $purchase->status_arabic }}
                                </span>
                            </td>
                            <td>{{ $purchase->creator?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="ti ti-file-invoice d-block mb-2"></i>
                                    <p class="text-muted mb-0">لا توجد فواتير مشتريات</p>
                                    <a href="{{ route('purchases.create') }}" class="btn btn-primary btn-sm mt-3">
                                        <i class="ti ti-plus me-1"></i>إنشاء فاتورة جديدة
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted small">
                    عرض {{ $purchases->firstItem() }} إلى {{ $purchases->lastItem() }} من {{ $purchases->total() }}
                </div>
                {{ $purchases->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            window.location.href = this.dataset.href;
        });
    });
});
</script>
@endpush
