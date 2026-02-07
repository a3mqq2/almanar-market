@extends('layouts.app')

@section('title', 'جرد المخزون')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">جرد المخزون</li>
@endsection

@push('styles')
<style>
    .stat-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
    }
    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
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
        margin-bottom: 1rem;
    }
    .progress-sm {
        height: 6px;
        border-radius: 3px;
    }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--bs-secondary-color);
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }

    /* Dark mode styles */
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
    [data-bs-theme="dark"] .progress-sm {
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
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background: #1a1d21;
        border-color: #373b3e;
        color: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="card-title mb-0">سجل الجرد</h5>
        <a href="{{ route('inventory-counts.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-plus me-1"></i>جرد جديد
        </a>
    </div>
    <div class="card-body">
        <div class="filter-card">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">الحالة</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">الكل</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>مسودة</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>جاري الجرد</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>معتمد</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">من تاريخ</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">إلى تاريخ</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">بحث</label>
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="رقم الجرد..." value="{{ request('search') }}">
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="ti ti-search me-1"></i>بحث
                    </button>
                    <a href="{{ route('inventory-counts.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-x"></i>
                    </a>
                </div>
            </form>
        </div>

        @if($counts->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم الجرد</th>
                        <th>النوع</th>
                        <th>الحالة</th>
                        <th>التقدم</th>
                        <th>الفروقات</th>
                        <th>بواسطة</th>
                        <th>التاريخ</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($counts as $count)
                    <tr>
                        <td>
                            <a href="{{ route('inventory-counts.show', $count) }}" class="fw-medium text-decoration-none">
                                {{ $count->reference_number }}
                            </a>
                        </td>
                        <td>{{ $count->count_type_arabic }}</td>
                        <td>
                            <span class="badge bg-{{ $count->status_color }}">{{ $count->status_arabic }}</span>
                        </td>
                        <td style="min-width: 150px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress progress-sm flex-grow-1">
                                    <div class="progress-bar bg-success" style="width: {{ $count->progress_percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ $count->counted_items }}/{{ $count->total_items }}</small>
                            </div>
                        </td>
                        <td>
                            @if($count->variance_items > 0)
                                <span class="badge bg-warning text-dark">{{ $count->variance_items }} فروقات</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ $count->countedByUser->name ?? '-' }}</td>
                        <td>
                            <small class="text-muted">{{ $count->created_at->format('Y/m/d') }}</small>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventory-counts.show', $count) }}">
                                            <i class="ti ti-eye me-2"></i>عرض
                                        </a>
                                    </li>
                                    @if($count->status === 'in_progress')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventory-counts.count', $count) }}">
                                            <i class="ti ti-clipboard-check me-2"></i>متابعة الجرد
                                        </a>
                                    </li>
                                    @endif
                                    @if($count->status === 'completed')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('inventory-counts.review', $count) }}">
                                            <i class="ti ti-file-check me-2"></i>مراجعة واعتماد
                                        </a>
                                    </li>
                                    @endif
                                    @if($count->canCancel())
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="cancelCount({{ $count->id }})">
                                            <i class="ti ti-x me-2"></i>إلغاء
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $counts->withQueryString()->links() }}
        </div>
        @else
        <div class="empty-state">
            <i class="ti ti-clipboard-off d-block"></i>
            <h5>لا توجد عمليات جرد</h5>
            <p class="mb-3">ابدأ بإنشاء جرد جديد للمخزون</p>
            <a href="{{ route('inventory-counts.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>جرد جديد
            </a>
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-alert-circle text-danger me-2"></i>إلغاء الجرد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="cancelCountId">
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
const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));

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

function cancelCount(id) {
    document.getElementById('cancelCountId').value = id;
    document.getElementById('cancelReason').value = '';
    cancelModal.show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', async function() {
    const id = document.getElementById('cancelCountId').value;
    const reason = document.getElementById('cancelReason').value.trim();

    if (!reason) {
        showToast('يرجى إدخال سبب الإلغاء', 'warning');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإلغاء...';

    try {
        const response = await fetch(`/inventory-counts/${id}/cancel`, {
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
            setTimeout(() => location.reload(), 1000);
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
