@extends('layouts.app')

@section('title', 'مراجعة الجرد')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory-counts.index') }}">جرد المخزون</a></li>
    <li class="breadcrumb-item active">مراجعة {{ $inventoryCount->reference_number }}</li>
@endsection

@push('styles')
<style>
    .summary-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
    .summary-card .summary-icon {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
    }
    .summary-card .summary-value {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .summary-card .summary-label {
        color: var(--bs-secondary-color);
        font-size: 0.9rem;
    }
    .variance-row.surplus {
        background: rgba(var(--bs-success-rgb), 0.05);
    }
    .variance-row.shortage {
        background: rgba(var(--bs-danger-rgb), 0.05);
    }
    .value-positive {
        color: var(--bs-success);
        font-weight: 600;
    }
    .value-negative {
        color: var(--bs-danger);
        font-weight: 600;
    }
    .approval-card {
        background: linear-gradient(135deg, rgba(var(--bs-success-rgb), 0.1), rgba(var(--bs-success-rgb), 0.05));
        border: 2px solid var(--bs-success);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
    }

    /* Dark mode styles */
    [data-bs-theme="dark"] .summary-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .summary-card .summary-value {
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .variance-row.surplus {
        background: rgba(25, 135, 84, 0.15);
    }
    [data-bs-theme="dark"] .variance-row.shortage {
        background: rgba(220, 53, 69, 0.15);
    }
    [data-bs-theme="dark"] .approval-card {
        background: linear-gradient(135deg, rgba(25, 135, 84, 0.2), rgba(25, 135, 84, 0.1));
        border-color: #198754;
    }
    [data-bs-theme="dark"] .approval-card h4 {
        color: #e9ecef;
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
        --bs-table-hover-bg: #2b3035;
        background-color: #2b3035;
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">مراجعة الجرد</h4>
        <span class="text-muted">{{ $inventoryCount->reference_number }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory-counts.count', $inventoryCount) }}" class="btn btn-outline-secondary">
            <i class="ti ti-pencil me-1"></i>تعديل الجرد
        </a>
        <a href="{{ route('inventory-counts.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="summary-card">
            <div class="summary-icon" style="background: rgba(var(--bs-secondary-rgb), 0.15); color: var(--bs-secondary);">
                <i class="ti ti-equal"></i>
            </div>
            <div class="summary-value">{{ $summary['match_items'] }}</div>
            <div class="summary-label">مطابق</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card">
            <div class="summary-icon" style="background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success);">
                <i class="ti ti-trending-up"></i>
            </div>
            <div class="summary-value text-success">{{ $summary['surplus_items'] }}</div>
            <div class="summary-label">فائض</div>
            <div class="text-success fw-bold mt-1">+{{ number_format($summary['surplus_value'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="summary-card">
            <div class="summary-icon" style="background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger);">
                <i class="ti ti-trending-down"></i>
            </div>
            <div class="summary-value text-danger">{{ $summary['shortage_items'] }}</div>
            <div class="summary-label">عجز</div>
            <div class="text-danger fw-bold mt-1">-{{ number_format($summary['shortage_value'], 2) }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="text-muted mb-1">قيمة النظام</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['total_system_value'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="text-muted mb-1">القيمة المجرودة</div>
                <div class="fs-4 fw-bold">{{ number_format($summary['total_counted_value'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="text-muted mb-1">صافي الفرق</div>
                <div class="fs-4 fw-bold {{ $summary['variance_value'] > 0 ? 'text-success' : ($summary['variance_value'] < 0 ? 'text-danger' : '') }}">
                    {{ $summary['variance_value'] > 0 ? '+' : '' }}{{ number_format($summary['variance_value'], 2) }}
                </div>
            </div>
        </div>
    </div>
</div>

@if($varianceItems->count() > 0)
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-alert-triangle text-warning me-2"></i>الأصناف ذات الفروقات ({{ $varianceItems->count() }})</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الصنف</th>
                        <th>الباركود</th>
                        <th class="text-center">كمية النظام</th>
                        <th class="text-center">الكمية المجرودة</th>
                        <th class="text-center">الفرق</th>
                        <th class="text-end">قيمة الفرق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($varianceItems as $item)
                    <tr class="variance-row {{ $item->difference > 0 ? 'surplus' : 'shortage' }}">
                        <td>{{ $item->product->name }}</td>
                        <td class="text-muted">{{ $item->product->barcode ?? '-' }}</td>
                        <td class="text-center">{{ number_format($item->system_qty, 2) }}</td>
                        <td class="text-center">{{ number_format($item->counted_qty, 2) }}</td>
                        <td class="text-center {{ $item->difference > 0 ? 'value-positive' : 'value-negative' }}">
                            {{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 2) }}
                        </td>
                        <td class="text-end {{ $item->variance_value > 0 ? 'value-positive' : 'value-negative' }}">
                            {{ $item->variance_value > 0 ? '+' : '' }}{{ number_format($item->variance_value, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="5" class="text-end fw-bold">المجموع</td>
                        <td class="text-end fw-bold {{ $inventoryCount->variance_value > 0 ? 'value-positive' : 'value-negative' }}">
                            {{ $inventoryCount->variance_value > 0 ? '+' : '' }}{{ number_format($inventoryCount->variance_value, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@else
<div class="alert alert-success text-center mb-4">
    <i class="ti ti-check me-2"></i>
    لا توجد فروقات - جميع الأصناف مطابقة!
</div>
@endif

<div class="approval-card">
    <i class="ti ti-shield-check text-success" style="font-size: 3rem;"></i>
    <h4 class="mt-3 mb-2">اعتماد الجرد</h4>
    <p class="text-muted mb-4">
        عند الاعتماد سيتم تحديث المخزون تلقائياً وتسجيل جميع الفروقات.
        <br>
        <strong class="text-danger">هذا الإجراء لا يمكن التراجع عنه.</strong>
    </p>
    <button class="btn btn-success btn-lg px-5" id="approveBtn">
        <i class="ti ti-check me-2"></i>اعتماد الجرد
    </button>
</div>
@endsection

@push('scripts')
<script>
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

document.getElementById('approveBtn').addEventListener('click', async function() {
    if (!confirm('هل أنت متأكد من اعتماد هذا الجرد؟ سيتم تحديث المخزون تلقائياً.')) return;

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الاعتماد...';

    try {
        const response = await fetch('{{ route("inventory-counts.approve", $inventoryCount) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            showToast(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-2"></i>اعتماد الجرد';
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-2"></i>اعتماد الجرد';
    }
});
</script>
@endpush
