@extends('layouts.app')

@section('title', 'فاتورة ' . $sale->invoice_number)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">المبيعات</a></li>
    <li class="breadcrumb-item active">{{ $sale->invoice_number }}</li>
@endsection

@push('styles')
<style>
    :root {
        --header-border: var(--bs-border-color);
        --label-color: var(--bs-secondary-color);
        --card-border: var(--bs-border-color);
    }
    .sale-header {
        background: #fff;
        border: 1px solid var(--header-border);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    [data-bs-theme="dark"] .sale-header {
        background: var(--bs-tertiary-bg);
    }
    .info-label {
        color: var(--label-color);
        font-size: 0.875rem;
    }
    .table thead {
        background: var(--bs-tertiary-bg);
    }
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .summary-card {
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 1rem;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px dashed var(--card-border);
    }
    .summary-row:last-child {
        border-bottom: none;
    }
    .summary-row.total {
        font-size: 1.25rem;
        font-weight: 700;
        border-top: 2px solid var(--card-border);
        border-bottom: none;
        padding-top: 1rem;
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="sale-header">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h4 class="mb-1">فاتورة رقم: {{ $sale->invoice_number }}</h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-{{ $sale->status_color }} fs-6">{{ $sale->status_arabic }}</span>
                <span class="badge bg-{{ $sale->payment_status == 'paid' ? 'success' : ($sale->payment_status == 'partial' ? 'warning' : 'danger') }} fs-6">{{ $sale->payment_status_arabic }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="btn btn-outline-primary">
                <i class="ti ti-printer me-1"></i>طباعة
            </a>
            @if($sale->status == 'completed')
            <button type="button" class="btn btn-outline-danger" id="cancelSaleBtn">
                <i class="ti ti-x me-1"></i>إلغاء الفاتورة
            </button>
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-list me-1"></i>تفاصيل الفاتورة</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th>الوحدة</th>
                                <th>الكمية</th>
                                <th>سعر الوحدة</th>
                                <th>التكلفة</th>
                                <th>الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $item->product->name }}@if($item->barcode_label) <small class="text-muted">({{ $item->barcode_label }})</small>@endif</td>
                                <td>{{ $item->unitName }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-muted">{{ number_format($item->cost_at_sale ?? 0, 2) }}</td>
                                <td class="fw-bold">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($sale->payments->count() > 0)
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-credit-card me-1"></i>طرق الدفع</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>طريقة الدفع</th>
                                <th>الخزينة</th>
                                <th>المرجع</th>
                                <th>المبلغ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->payments as $payment)
                            <tr>
                                <td>{{ $payment->paymentMethod->name }}</td>
                                <td>{{ $payment->cashbox?->name ?? '-' }}</td>
                                <td>{{ $payment->reference_number ?? '-' }}</td>
                                <td class="fw-bold text-success">{{ number_format($payment->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($sale->status == 'cancelled')
        <div class="card border-danger mb-3">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="ti ti-alert-circle me-1"></i>معلومات الإلغاء</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-label">ألغيت بواسطة</div>
                        <div class="fw-medium">{{ $sale->canceller?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-label">تاريخ الإلغاء</div>
                        <div class="fw-medium">{{ $sale->cancelled_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                    <div class="col-12 mt-3">
                        <div class="info-label">سبب الإلغاء</div>
                        <div class="fw-medium">{{ $sale->cancel_reason ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="ti ti-info-circle me-1"></i>معلومات الفاتورة</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="info-label">التاريخ</td>
                        <td class="fw-medium">{{ $sale->sale_date->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">الوقت</td>
                        <td class="fw-medium">{{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">الكاشير</td>
                        <td class="fw-medium">{{ $sale->cashier?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">الزبون</td>
                        <td class="fw-medium">
                            @if($sale->customer)
                            <a href="{{ route('customers.account.show', $sale->customer) }}">{{ $sale->customer->name }}</a>
                            @else
                            زبون عادي
                            @endif
                        </td>
                    </tr>
                    @if($sale->notes)
                    <tr>
                        <td class="info-label">ملاحظات</td>
                        <td class="fw-medium">{{ $sale->notes }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        <div class="summary-card">
            <div class="summary-row">
                <span class="info-label">المجموع الفرعي</span>
                <span>{{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="summary-row">
                <span class="info-label">
                    الخصم
                    @if($sale->discount_type == 'percentage')
                    ({{ $sale->discount_value }}%)
                    @endif
                </span>
                <span class="text-danger">-{{ number_format($sale->discount_amount, 2) }}</span>
            </div>
            @endif
            @if($sale->tax_amount > 0)
            <div class="summary-row">
                <span class="info-label">الضريبة ({{ $sale->tax_rate }}%)</span>
                <span>{{ number_format($sale->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="summary-row total">
                <span>الإجمالي</span>
                <span class="text-primary">{{ number_format($sale->total, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="info-label">المدفوع</span>
                <span class="text-success fw-bold">{{ number_format($sale->paid_amount, 2) }}</span>
            </div>
            @if($sale->credit_amount > 0)
            <div class="summary-row">
                <span class="info-label">آجل</span>
                <span class="text-danger fw-bold">{{ number_format($sale->credit_amount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('cancelSaleBtn')?.addEventListener('click', function() {
        Swal.fire({
            title: 'إلغاء الفاتورة',
            input: 'textarea',
            inputLabel: 'سبب الإلغاء',
            inputPlaceholder: 'أدخل سبب إلغاء الفاتورة...',
            inputAttributes: {
                'aria-label': 'سبب الإلغاء'
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'إلغاء الفاتورة',
            cancelButtonText: 'تراجع',
            inputValidator: (value) => {
                if (!value) {
                    return 'يجب إدخال سبب الإلغاء';
                }
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch('{{ route("pos.cancel", $sale) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ reason: result.value })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم الإلغاء',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('خطأ', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('خطأ', 'حدث خطأ في إلغاء الفاتورة', 'error');
                }
            }
        });
    });
});
</script>
@endpush
