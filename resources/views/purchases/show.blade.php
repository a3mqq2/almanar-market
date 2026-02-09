@extends('layouts.app')

@section('title', 'عرض فاتورة مشتريات')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('purchases.index') }}">المشتريات</a></li>
    <li class="breadcrumb-item active">فاتورة #{{ $purchase->id }}</li>
@endsection

@push('styles')
<style>
    /* Card Styles */
    .purchase-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .purchase-card-header {
        background: var(--bs-light, #f8f9fa);
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-radius: 12px 12px 0 0;
    }
    .purchase-card-header i {
        font-size: 1.25rem;
        color: var(--bs-primary);
    }
    .purchase-card-header h6 {
        margin: 0;
        font-weight: 600;
        font-size: 1rem;
    }
    .purchase-card-body {
        padding: 1.25rem;
        background: var(--bs-card-bg, #fff);
        border-radius: 0 0 12px 12px;
    }

    [data-bs-theme="dark"] .purchase-card {
        background: var(--bs-card-bg, #212529);
    }
    [data-bs-theme="dark"] .purchase-card-header {
        background: var(--bs-tertiary-bg, #2b3035);
    }
    [data-bs-theme="dark"] .purchase-card-body {
        background: var(--bs-card-bg, #212529);
    }

    /* Info Rows */
    .info-row {
        display: flex;
        margin-bottom: 0.75rem;
    }
    .info-row:last-child {
        margin-bottom: 0;
    }
    .info-label {
        width: 130px;
        color: var(--bs-secondary-color);
        font-weight: 500;
        font-size: 0.9rem;
    }
    .info-value {
        flex: 1;
        font-weight: 600;
    }

    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .items-table th {
        background: var(--bs-light, #f8f9fa);
        padding: 0.875rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        border-bottom: 2px solid var(--bs-border-color);
        white-space: nowrap;
    }
    .items-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
    }
    .items-table tbody tr:hover {
        background: var(--bs-light, #f8f9fa);
    }

    [data-bs-theme="dark"] .items-table th {
        background: var(--bs-tertiary-bg, #2b3035);
    }
    [data-bs-theme="dark"] .items-table tbody tr:hover {
        background: var(--bs-tertiary-bg, #2b3035);
    }

    /* Summary */
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .summary-row:last-child {
        border-bottom: none;
    }
    .summary-row.total {
        font-weight: 700;
        font-size: 1.15rem;
        padding-top: 1rem;
        margin-top: 0.5rem;
        border-top: 2px solid var(--bs-border-color);
        color: var(--bs-primary);
    }

    /* Status Badge */
    .status-badge-lg {
        font-size: 0.95rem;
        padding: 0.5rem 1rem;
        border-radius: 6px;
    }

    /* Audit Info */
    .audit-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .audit-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .audit-item:first-child {
        padding-top: 0;
    }
    .audit-label {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .audit-date {
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
    }

    /* Cancel Card */
    .cancel-card {
        background: rgba(220, 53, 69, 0.1) !important;
        border-color: rgba(220, 53, 69, 0.3) !important;
    }
    .cancel-card .purchase-card-header {
        background: rgba(220, 53, 69, 0.15) !important;
    }
    .cancel-card .purchase-card-header h6 {
        color: #dc3545;
    }
    .cancel-card .purchase-card-header i {
        color: #dc3545 !important;
    }

    /* Page Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-header-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .page-header-title h4 {
        margin: 0;
        font-weight: 600;
    }
    .page-header-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-title">
        <h4>فاتورة مشتريات #{{ $purchase->id }}</h4>
        <span class="badge status-badge-lg bg-{{ $purchase->status_color }}">{{ $purchase->status_arabic }}</span>
    </div>
    <div class="page-header-actions">
        @if($purchase->status == 'draft')
            <button type="button" class="btn btn-success btn-sm" id="approveBtn">
                <i class="ti ti-check me-1"></i>اعتماد
            </button>
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-outline-primary btn-sm">
                <i class="ti ti-edit me-1"></i>تعديل
            </a>
        @endif
        @if($purchase->canBeCancelled())
            <button type="button" class="btn btn-outline-danger btn-sm" id="cancelBtn">
                <i class="ti ti-x me-1"></i>إلغاء
            </button>
        @endif
        <a href="{{ route('purchases.print', $purchase) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="ti ti-printer me-1"></i>طباعة
        </a>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary btn-sm">
            <i class="ti ti-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Invoice Info Card -->
        <div class="purchase-card">
            <div class="purchase-card-header">
                <i class="ti ti-file-invoice"></i>
                <h6>بيانات الفاتورة</h6>
            </div>
            <div class="purchase-card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">المورد:</span>
                            <span class="info-value">
                                <a href="{{ route('suppliers.account.show', $purchase->supplier) }}">
                                    {{ $purchase->supplier->name }}
                                </a>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">رقم فاتورة المورد:</span>
                            <span class="info-value">{{ $purchase->invoice_number ?: '-' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">تاريخ الشراء:</span>
                            <span class="info-value">{{ $purchase->purchase_date->format('Y-m-d') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">طريقة الدفع:</span>
                            <span class="info-value">{{ $purchase->payment_type_arabic }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">الحالة:</span>
                            <span class="info-value">
                                <span class="badge bg-{{ $purchase->status_color }}">{{ $purchase->status_arabic }}</span>
                            </span>
                        </div>
                        @if($purchase->notes)
                            <div class="info-row">
                                <span class="info-label">ملاحظات:</span>
                                <span class="info-value">{{ $purchase->notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Card -->
        <div class="purchase-card">
            <div class="purchase-card-header">
                <i class="ti ti-list-details"></i>
                <h6>الأصناف ({{ $purchase->items->count() }})</h6>
            </div>
            <div class="purchase-card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصنف</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                                <th>تاريخ الانتهاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('products.show', $item->product) }}" class="fw-medium">
                                            {{ $item->product->name }}
                                        </a>
                                        @if($item->inventoryBatch)
                                            <br><small class="text-muted">دفعة: {{ $item->inventoryBatch->batch_number }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->productUnit?->unit?->name ?? '-' }}</td>
                                    <td>{{ number_format($item->quantity, 4) }}</td>
                                    <td>{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="fw-bold text-primary">{{ number_format($item->total_price, 2) }}</td>
                                    <td>{{ $item->expiry_date?->format('Y-m-d') ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cancel Info Card -->
        @if($purchase->status == 'cancelled')
            <div class="purchase-card cancel-card">
                <div class="purchase-card-header">
                    <i class="ti ti-alert-circle"></i>
                    <h6>معلومات الإلغاء</h6>
                </div>
                <div class="purchase-card-body">
                    <div class="info-row">
                        <span class="info-label">سبب الإلغاء:</span>
                        <span class="info-value">{{ $purchase->cancel_reason }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ألغيت بواسطة:</span>
                        <span class="info-value">{{ $purchase->canceller?->name ?? '-' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">تاريخ الإلغاء:</span>
                        <span class="info-value">{{ $purchase->cancelled_at?->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Summary Card -->
        <div class="purchase-card">
            <div class="purchase-card-header">
                <i class="ti ti-calculator"></i>
                <h6>ملخص الفاتورة</h6>
            </div>
            <div class="purchase-card-body">
                <div class="summary-row">
                    <span>المجموع الفرعي:</span>
                    <span>{{ number_format($purchase->subtotal, 2) }}</span>
                </div>

                @if($purchase->discount_amount > 0)
                    <div class="summary-row">
                        <span>
                            الخصم
                            @if($purchase->discount_type == 'percentage')
                                ({{ $purchase->discount_value }}%)
                            @endif
                        </span>
                        <span class="text-danger">- {{ number_format($purchase->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if($purchase->tax_amount > 0)
                    <div class="summary-row">
                        <span>الضريبة ({{ $purchase->tax_rate }}%)</span>
                        <span>+ {{ number_format($purchase->tax_amount, 2) }}</span>
                    </div>
                @endif

                <div class="summary-row total">
                    <span>الإجمالي:</span>
                    <span>{{ number_format($purchase->total, 2) }}</span>
                </div>

                @if($purchase->payment_type == 'credit')
                    <div class="summary-row">
                        <span>المدفوع:</span>
                        <span class="text-success">{{ number_format($purchase->paid_amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>المتبقي:</span>
                        <span class="text-danger fw-bold">{{ number_format($purchase->remaining_amount, 2) }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Audit Info Card -->
        <div class="purchase-card">
            <div class="purchase-card-header">
                <i class="ti ti-info-circle"></i>
                <h6>معلومات إضافية</h6>
            </div>
            <div class="purchase-card-body">
                <div class="audit-item">
                    <div class="audit-label">أنشئت بواسطة:</div>
                    <div>{{ $purchase->creator?->name ?? '-' }}</div>
                    <div class="audit-date">{{ $purchase->created_at->format('Y-m-d H:i') }}</div>
                </div>
                @if($purchase->approved_at)
                    <div class="audit-item">
                        <div class="audit-label">اعتمدت بواسطة:</div>
                        <div>{{ $purchase->approver?->name ?? '-' }}</div>
                        <div class="audit-date">{{ $purchase->approved_at->format('Y-m-d H:i') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-alert-circle text-danger me-1"></i>إلغاء الفاتورة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-1"></i>
                    سيتم عكس جميع الحركات المرتبطة بهذه الفاتورة (المخزون وحساب المورد).
                </div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '{{ csrf_token() }}';

    @if($purchase->status == 'draft')
    document.getElementById('approveBtn')?.addEventListener('click', function() {
        Swal.fire({
            title: 'تأكيد الاعتماد',
            text: 'هل تريد اعتماد هذه الفاتورة؟ سيتم إضافة الأصناف للمخزون وتسجيل المبلغ على حساب المورد.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، اعتماد',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#198754'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('{{ route('purchases.approve', $purchase) }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم الاعتماد',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => location.reload());
                    } else {
                        Swal.fire('خطأ', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error');
                }
            }
        });
    });
    @endif

    @if($purchase->canBeCancelled())
    const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));

    document.getElementById('cancelBtn')?.addEventListener('click', function() {
        cancelModal.show();
    });

    document.getElementById('confirmCancelBtn')?.addEventListener('click', async function() {
        const reason = document.getElementById('cancelReason').value.trim();

        if (!reason) {
            document.getElementById('cancelReason').classList.add('is-invalid');
            return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإلغاء...';

        try {
            const response = await fetch('{{ route('purchases.cancel', $purchase) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ cancel_reason: reason })
            });

            const data = await response.json();

            if (data.success) {
                cancelModal.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'تم الإلغاء',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => location.reload());
            } else {
                Swal.fire('خطأ', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error');
        }

        this.disabled = false;
        this.innerHTML = '<i class="ti ti-x me-1"></i>تأكيد الإلغاء';
    });
    @endif
});
</script>
@endpush
