@extends('layouts.app')

@section('title', 'تفاصيل الجرد')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory-counts.index') }}">جرد المخزون</a></li>
    <li class="breadcrumb-item active">{{ $inventoryCount->reference_number }}</li>
@endsection

@push('styles')
<style>
    .info-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1rem;
    }
    .info-card .info-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    .info-card .info-value {
        font-weight: 600;
    }
    .summary-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
        text-align: center;
    }
    .summary-card .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .summary-card .summary-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
    }
    .progress-lg {
        height: 24px;
        border-radius: 12px;
    }
    .progress-lg .progress-bar {
        font-size: 0.85rem;
        font-weight: 600;
    }

    /* Dark mode styles */
    [data-bs-theme="dark"] .info-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .info-card .info-value {
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .summary-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .summary-card .summary-value {
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .progress-lg {
        background: #2b3035;
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
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ $inventoryCount->reference_number }}</h4>
        <span class="badge bg-{{ $inventoryCount->status_color }} fs-6">{{ $inventoryCount->status_arabic }}</span>
    </div>
    <div class="d-flex gap-2">
        @if($inventoryCount->status == 'draft')
        <button class="btn btn-success" id="startBtn">
            <i class="ti ti-player-play me-1"></i>بدء الجرد
        </button>
        @endif
        @if($inventoryCount->status == 'completed')
        <a href="{{ route('inventory-counts.review', $inventoryCount) }}" class="btn btn-primary">
            <i class="ti ti-file-check me-1"></i>مراجعة واعتماد
        </a>
        @endif
        @if($inventoryCount->canCancel())
        <button class="btn btn-outline-danger" onclick="cancelCount()">
            <i class="ti ti-x me-1"></i>إلغاء
        </button>
        @endif
        <a href="{{ route('inventory-counts.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="info-card">
            <div class="info-label">نوع الجرد</div>
            <div class="info-value">{{ $inventoryCount->count_type_arabic }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-card">
            <div class="info-label">بواسطة</div>
            <div class="info-value">{{ $inventoryCount->countedByUser->name ?? '-' }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-card">
            <div class="info-label">تاريخ الإنشاء</div>
            <div class="info-value">{{ $inventoryCount->created_at->format('Y/m/d H:i') }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-card">
            <div class="info-label">
                @if($inventoryCount->status == 'approved')
                    تاريخ الاعتماد
                @elseif($inventoryCount->status == 'completed')
                    تاريخ الإكمال
                @elseif($inventoryCount->started_at)
                    تاريخ البدء
                @else
                    الحالة
                @endif
            </div>
            <div class="info-value">
                @if($inventoryCount->status == 'approved')
                    {{ $inventoryCount->approved_at?->format('Y/m/d H:i') ?? '-' }}
                @elseif($inventoryCount->status == 'completed')
                    {{ $inventoryCount->completed_at?->format('Y/m/d H:i') ?? '-' }}
                @elseif($inventoryCount->started_at)
                    {{ $inventoryCount->started_at->format('Y/m/d H:i') }}
                @else
                    مسودة
                @endif
            </div>
        </div>
    </div>
</div>

@if($inventoryCount->status != 'draft')
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="info-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="info-label mb-0">التقدم</span>
                <span class="fw-medium">{{ $inventoryCount->counted_items }} / {{ $inventoryCount->total_items }} ({{ $inventoryCount->progress_percentage }}%)</span>
            </div>
            <div class="progress progress-lg">
                <div class="progress-bar bg-success" style="width: {{ $inventoryCount->progress_percentage }}%">
                    {{ $inventoryCount->progress_percentage }}%
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-value text-primary">{{ $inventoryCount->total_items }}</div>
            <div class="summary-label">إجمالي الأصناف</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-value">{{ number_format($inventoryCount->total_system_value, 2) }}</div>
            <div class="summary-label">قيمة النظام</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-value">{{ number_format($inventoryCount->total_counted_value, 2) }}</div>
            <div class="summary-label">القيمة المجرودة</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-value {{ $inventoryCount->variance_value > 0 ? 'text-success' : ($inventoryCount->variance_value < 0 ? 'text-danger' : '') }}">
                {{ number_format($inventoryCount->variance_value, 2) }}
            </div>
            <div class="summary-label">الفرق</div>
        </div>
    </div>
</div>
@endif

@if($inventoryCount->notes)
<div class="alert alert-secondary mb-4">
    <i class="ti ti-note me-2"></i>
    <strong>ملاحظات:</strong> {{ $inventoryCount->notes }}
</div>
@endif

@if($inventoryCount->status == 'cancelled')
<div class="alert alert-danger mb-4">
    <i class="ti ti-x me-2"></i>
    <strong>سبب الإلغاء:</strong> {{ $inventoryCount->cancel_reason }}
    <br>
    <small class="text-muted">تم الإلغاء في: {{ $inventoryCount->cancelled_at?->format('Y/m/d H:i') }}</small>
</div>
@endif

@if($inventoryCount->status == 'approved' && $inventoryCount->approvedByUser)
<div class="alert alert-success mb-4">
    <i class="ti ti-check me-2"></i>
    <strong>تم الاعتماد بواسطة:</strong> {{ $inventoryCount->approvedByUser->name }}
    <br>
    <small>في: {{ $inventoryCount->approved_at?->format('Y/m/d H:i') }}</small>
</div>
@endif

@if($inventoryCount->items->count() > 0 && in_array($inventoryCount->status, ['completed', 'approved']))
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="ti ti-list me-2"></i>تفاصيل الأصناف</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>الصنف</th>
                        <th>الباركود</th>
                        <th>كمية النظام</th>
                        <th>الكمية المجرودة</th>
                        <th>الفرق</th>
                        <th>قيمة الفرق</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventoryCount->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->product->name }}</td>
                        <td class="text-muted">{{ $item->product->barcode ?? '-' }}</td>
                        <td>{{ number_format($item->system_qty, 2) }}</td>
                        <td>{{ $item->counted_qty != null ? number_format($item->counted_qty, 2) : '-' }}</td>
                        <td class="{{ $item->difference > 0 ? 'text-success' : ($item->difference < 0 ? 'text-danger' : '') }}">
                            {{ $item->difference != 0 ? number_format($item->difference, 2) : '-' }}
                        </td>
                        <td class="{{ $item->variance_value > 0 ? 'text-success' : ($item->variance_value < 0 ? 'text-danger' : '') }}">
                            {{ $item->variance_value != 0 ? number_format($item->variance_value, 2) : '-' }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $item->variance_status_color }}">{{ $item->variance_status_arabic }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-alert-circle text-danger me-2"></i>إلغاء الجرد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">سبب الإلغاء <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancelReason" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmCancelBtn">
                    <i class="ti ti-x me-1"></i>تأكيد الإلغاء
                </button>
            </div>
        </div>
    </div>
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

@if($inventoryCount->status == 'draft')
document.getElementById('startBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري البدء...';

    try {
        const response = await fetch('{{ route("inventory-counts.start", $inventoryCount) }}', {
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
            }, 500);
        } else {
            showToast(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-player-play me-1"></i>بدء الجرد';
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-player-play me-1"></i>بدء الجرد';
    }
});
@endif

const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));

function cancelCount() {
    document.getElementById('cancelReason').value = '';
    cancelModal.show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', async function() {
    const reason = document.getElementById('cancelReason').value.trim();

    if (!reason) {
        showToast('يرجى إدخال سبب الإلغاء', 'warning');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإلغاء...';

    try {
        const response = await fetch('{{ route("inventory-counts.cancel", $inventoryCount) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ cancel_reason: reason }),
        });

        const data = await response.json();

        if (data.success) {
            cancelModal.hide();
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            showToast(data.message, 'danger');
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'danger');
    }

    this.disabled = false;
    this.innerHTML = '<i class="ti ti-x me-1"></i>تأكيد الإلغاء';
});
</script>
@endpush
