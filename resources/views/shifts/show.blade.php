@extends('layouts.app')

@section('title', 'تفاصيل الوردية #' . $shift->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('shift-reports.index') }}">تقارير الورديات</a></li>
    <li class="breadcrumb-item active">وردية #{{ $shift->id }}</li>
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
    .nav-tabs .nav-link {
        border: none;
        color: var(--bs-secondary-color);
        font-weight: 500;
        padding: 0.75rem 1.5rem;
    }
    .nav-tabs .nav-link.active {
        color: var(--bs-primary);
        border-bottom: 2px solid var(--bs-primary);
        background: transparent;
    }
    .cashbox-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 10px;
        padding: 1.25rem;
    }
    .cashbox-card .cashbox-name {
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .cashbox-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px dashed var(--bs-border-color);
    }
    .cashbox-row:last-child {
        border-bottom: none;
    }
    .cashbox-row .label {
        color: var(--bs-secondary-color);
    }
    .cashbox-row .value {
        font-weight: 600;
    }
    .difference-row {
        background: var(--bs-tertiary-bg);
        margin: 0.5rem -1.25rem -1.25rem;
        padding: 0.75rem 1.25rem;
        border-radius: 0 0 10px 10px;
    }

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
    [data-bs-theme="dark"] .cashbox-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .cashbox-card .cashbox-name {
        color: #e9ecef;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .cashbox-row {
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .difference-row {
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
    [data-bs-theme="dark"] .nav-tabs {
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .nav-tabs .nav-link.active {
        border-bottom-color: var(--bs-primary);
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">وردية #{{ $shift->id }}</h4>
        <span class="badge bg-{{ $shift->status_color }} fs-6">{{ $shift->status_arabic }}</span>
        @if($shift->force_closed)
            <span class="badge bg-danger fs-6 ms-1">إغلاق إجباري</span>
        @endif
        @if($shift->approved)
            <span class="badge bg-success fs-6 ms-1">معتمدة</span>
        @endif
    </div>
    <div>
        <a href="{{ route('shift-reports.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#overview">
            <i class="ti ti-info-circle me-1"></i>نظرة عامة
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#cashboxes">
            <i class="ti ti-building-bank me-1"></i>الخزائن
            <span class="badge bg-secondary ms-1">{{ $shift->shiftCashboxes->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#transactions">
            <i class="ti ti-arrows-exchange me-1"></i>العمليات
            <span class="badge bg-secondary ms-1">{{ $shift->cashboxTransactions->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#sales">
            <i class="ti ti-receipt me-1"></i>الفواتير
            <span class="badge bg-secondary ms-1">{{ $shift->sales->count() }}</span>
        </a>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="overview">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="info-card">
                    <div class="info-label">الكاشير</div>
                    <div class="info-value">{{ $shift->user->name ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card">
                    <div class="info-label">الجهاز</div>
                    <div class="info-value">{{ $shift->terminal_id ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card">
                    <div class="info-label">تاريخ الفتح</div>
                    <div class="info-value">{{ $shift->opened_at?->format('Y/m/d H:i') ?? '-' }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card">
                    <div class="info-label">تاريخ الإغلاق</div>
                    <div class="info-value">{{ $shift->closed_at?->format('Y/m/d H:i') ?? '-' }}</div>
                </div>
            </div>
        </div>

        @if($shift->force_closed)
        <div class="alert alert-danger mb-4">
            <i class="ti ti-alert-circle me-2"></i>
            <strong>إغلاق إجباري بواسطة:</strong> {{ $shift->forceClosedBy->name ?? '-' }}
            <br>
            <strong>السبب:</strong> {{ $shift->force_close_reason ?? '-' }}
        </div>
        @endif

        @if($shift->approved && $shift->approvedBy)
        <div class="alert alert-success mb-4">
            <i class="ti ti-check me-2"></i>
            <strong>تم الاعتماد بواسطة:</strong> {{ $shift->approvedBy->name }}
            <br>
            <small>في: {{ $shift->approved_at?->format('Y/m/d H:i') }}</small>
        </div>
        @endif

        @if($shift->notes)
        <div class="alert alert-secondary mb-4">
            <i class="ti ti-note me-2"></i>
            <strong>ملاحظات:</strong> {{ $shift->notes }}
        </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-primary">{{ number_format($paymentSummary['total'], 2) }}</div>
                    <div class="summary-label">إجمالي المبيعات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-success">{{ number_format($paymentSummary['cash'], 2) }}</div>
                    <div class="summary-label">نقدي</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-info">{{ number_format($paymentSummary['card'], 2) }}</div>
                    <div class="summary-label">بطاقة</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value">{{ number_format($paymentSummary['other'], 2) }}</div>
                    <div class="summary-label">أخرى</div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-danger">{{ number_format($paymentSummary['refunds'], 2) }}</div>
                    <div class="summary-label">المرتجعات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value">{{ number_format($paymentSummary['net'], 2) }}</div>
                    <div class="summary-label">صافي المبيعات</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-primary">{{ $shift->sales_count }}</div>
                    <div class="summary-label">عدد الفواتير</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <div class="summary-value text-danger">{{ $shift->refunds_count }}</div>
                    <div class="summary-label">عدد المرتجعات</div>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="cashboxes">
        <div class="row g-3">
            @forelse($shift->shiftCashboxes as $shiftCashbox)
            <div class="col-md-4">
                <div class="cashbox-card">
                    <div class="cashbox-name">
                        <i class="ti ti-building-bank me-2"></i>{{ $shiftCashbox->cashbox->name ?? 'خزينة محذوفة' }}
                        @if($shiftCashbox->cashbox)
                            <span class="badge bg-secondary float-start">{{ $shiftCashbox->cashbox->type }}</span>
                        @endif
                    </div>
                    <div class="cashbox-row">
                        <span class="label">الرصيد الافتتاحي</span>
                        <span class="value">{{ number_format($shiftCashbox->opening_balance, 2) }}</span>
                    </div>
                    <div class="cashbox-row">
                        <span class="label">إجمالي الإيداعات</span>
                        <span class="value text-success">+{{ number_format($shiftCashbox->total_in, 2) }}</span>
                    </div>
                    <div class="cashbox-row">
                        <span class="label">إجمالي السحوبات</span>
                        <span class="value text-danger">-{{ number_format($shiftCashbox->total_out, 2) }}</span>
                    </div>
                    <div class="cashbox-row">
                        <span class="label">الرصيد المتوقع</span>
                        <span class="value fw-bold">{{ number_format($shiftCashbox->expected_balance, 2) }}</span>
                    </div>
                    <div class="cashbox-row">
                        <span class="label">رصيد الإغلاق</span>
                        <span class="value">{{ $shiftCashbox->closing_balance != null ? number_format($shiftCashbox->closing_balance, 2) : '-' }}</span>
                    </div>
                    <div class="difference-row d-flex justify-content-between align-items-center">
                        <span class="fw-medium">الفرق</span>
                        <span class="fw-bold fs-5 {{ $shiftCashbox->difference > 0 ? 'text-success' : ($shiftCashbox->difference < 0 ? 'text-danger' : '') }}">
                            {{ number_format($shiftCashbox->difference, 2) }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-building-bank fs-1 d-block mb-2 opacity-50"></i>
                    لا توجد خزائن مرتبطة بهذه الوردية
                </div>
            </div>
            @endforelse
        </div>
    </div>

    <div class="tab-pane fade" id="transactions">
        @if($shift->cashboxTransactions->count() > 0)
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>الخزينة</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>بواسطة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shift->cashboxTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
                                <td>
                                    @if(in_array($transaction->type, ['in', 'transfer_in']))
                                        <span class="badge bg-success">إيداع</span>
                                    @else
                                        <span class="badge bg-danger">سحب</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->cashbox->name ?? '-' }}</td>
                                <td class="{{ in_array($transaction->type, ['in', 'transfer_in']) ? 'text-success' : 'text-danger' }}">
                                    {{ in_array($transaction->type, ['in', 'transfer_in']) ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}
                                </td>
                                <td class="text-muted">{{ $transaction->description ?? '-' }}</td>
                                <td>{{ $transaction->user->name ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="ti ti-arrows-exchange fs-1 d-block mb-2 opacity-50"></i>
            لا توجد عمليات خزينة في هذه الوردية
        </div>
        @endif
    </div>

    <div class="tab-pane fade" id="sales">
        @if($shift->sales->count() > 0)
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>العميل</th>
                                <th>طريقة الدفع</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shift->sales as $sale)
                            <tr>
                                <td class="fw-medium">{{ $sale->invoice_number }}</td>
                                <td>{{ $sale->customer->name ?? 'عميل نقدي' }}</td>
                                <td>{{ $sale->payments->pluck('paymentMethod.name')->filter()->implode(', ') ?: '-' }}</td>
                                <td class="fw-medium">{{ number_format($sale->total, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'cancelled' ? 'danger' : 'warning') }}">
                                        {{ $sale->status == 'completed' ? 'مكتملة' : ($sale->status == 'cancelled' ? 'ملغاة' : 'معلقة') }}
                                    </span>
                                </td>
                                <td>{{ $sale->created_at->format('Y/m/d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="ti ti-receipt fs-1 d-block mb-2 opacity-50"></i>
            لا توجد فواتير في هذه الوردية
        </div>
        @endif

        @if($shift->returns->count() > 0)
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-receipt-refund text-danger me-2"></i>المرتجعات ({{ $shift->returns->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>رقم المرتجع</th>
                                <th>الفاتورة الأصلية</th>
                                <th>المبلغ</th>
                                <th>طريقة الاسترداد</th>
                                <th>بواسطة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($shift->returns as $return)
                            <tr>
                                <td class="fw-medium">{{ $return->return_number }}</td>
                                <td>{{ $return->sale->invoice_number ?? '-' }}</td>
                                <td class="text-danger">-{{ number_format($return->total_amount, 2) }}</td>
                                <td>{{ $return->refund_method_arabic }}</td>
                                <td>{{ $return->creator->name ?? '-' }}</td>
                                <td>{{ $return->created_at->format('Y/m/d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
