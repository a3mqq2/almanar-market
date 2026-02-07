@extends('layouts.app')

@section('title', 'تقرير مطابقة طرق الدفع')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">مطابقة طرق الدفع</li>
@endsection

@push('styles')
<style>
    .stat-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.25rem;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    [data-bs-theme="dark"] .stat-card {
        background: var(--bs-card-bg);
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
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .stat-card .stat-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    .stat-card.success .stat-icon { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success); }
    .stat-card.primary .stat-icon { background: rgba(var(--bs-primary-rgb), 0.15); color: var(--bs-primary); }
    .stat-card.info .stat-icon { background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info); }
    .stat-card.warning .stat-icon { background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning); }
    .stat-card.danger .stat-icon { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger); }

    .section-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    [data-bs-theme="dark"] .section-card {
        background: var(--bs-card-bg);
    }
    .section-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--bs-tertiary-bg);
    }
    .section-header h6 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-body {
        padding: 0;
    }

    .report-table {
        width: 100%;
        margin: 0;
    }
    .report-table thead {
        background: var(--bs-tertiary-bg);
    }
    .report-table th {
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        white-space: nowrap;
    }
    .report-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .report-table tbody tr:last-child td {
        border-bottom: none;
    }
    .report-table tbody tr:hover {
        background: var(--bs-tertiary-bg);
    }
    .report-table tfoot {
        background: var(--bs-tertiary-bg);
        font-weight: 600;
    }
    .report-table tfoot td {
        border-top: 2px solid var(--bs-border-color);
        border-bottom: none;
    }

    .amount-positive { color: var(--bs-success); font-weight: 600; }
    .amount-negative { color: var(--bs-danger); font-weight: 600; }
    .amount-neutral { color: var(--bs-primary); font-weight: 600; }

    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-balanced { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success); }
    .status-surplus { background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info); }
    .status-shortage { background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger); }

    .cashbox-type-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .type-cash { background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success); }
    .type-card { background: rgba(var(--bs-primary-rgb), 0.15); color: var(--bs-primary); }
    .type-wallet { background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info); }
    .type-bank { background: rgba(var(--bs-secondary-rgb), 0.15); color: var(--bs-secondary); }

    .linked-badge {
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        font-size: 0.65rem;
        background: rgba(var(--bs-primary-rgb), 0.15);
        color: var(--bs-primary);
    }

    .payment-method-card {
        background: var(--bs-tertiary-bg);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        border: 1px solid var(--bs-border-color);
    }
    .payment-method-card .method-name {
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
        margin-bottom: 0.5rem;
    }
    .payment-method-card .method-amount {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--bs-body-color);
    }
    .payment-method-card .method-count {
        font-size: 0.75rem;
        color: var(--bs-secondary-color);
    }
    .payment-method-card .linked-cashbox {
        font-size: 0.75rem;
        color: var(--bs-primary);
        margin-top: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-card">
                <div class="section-header">
                    <h6><i class="ti ti-filter"></i> خيارات التقرير</h6>
                </div>
                <div class="section-body p-3">
                    <form method="GET" action="{{ route('reports.payment-reconciliation') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">التاريخ</label>
                                <input type="date" class="form-control" name="date" value="{{ $date }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الوردية</label>
                                <select class="form-select" name="shift_id">
                                    <option value="">جميع الورديات</option>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}" {{ $shiftId == $shift->id ? 'selected' : '' }}>
                                            {{ $shift->user->name ?? 'غير معروف' }} - {{ $shift->opened_at?->format('H:i') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">الخزينة</label>
                                <select class="form-select" name="cashbox_id">
                                    <option value="">جميع الخزائن</option>
                                    @foreach($cashboxes as $cashbox)
                                        <option value="{{ $cashbox->id }}" {{ $cashboxId == $cashbox->id ? 'selected' : '' }}>
                                            {{ $cashbox->name }}
                                            @if($cashbox->paymentMethod)
                                                ({{ $cashbox->paymentMethod->name }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="generate" value="1" class="btn btn-primary w-100">
                                    <i class="ti ti-chart-bar me-1"></i> عرض التقرير
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($reportData)
        {{-- Payment Methods Linkage Overview --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="section-card">
                    <div class="section-header">
                        <h6><i class="ti ti-link"></i> ربط طرق الدفع بالخزائن</h6>
                    </div>
                    <div class="section-body p-3">
                        <div class="row g-3">
                            @foreach($paymentMethods as $method)
                                <div class="col-md-3 col-sm-6">
                                    <div class="payment-method-card">
                                        <div class="method-name">{{ $method->name }}</div>
                                        <div class="method-amount">
                                            @php
                                                $methodData = collect($reportData['by_payment_method'])->firstWhere('id', $method->id);
                                            @endphp
                                            {{ number_format($methodData['total_amount'] ?? 0, 2) }}
                                        </div>
                                        <div class="method-count">{{ $methodData['transaction_count'] ?? 0 }} عملية</div>
                                        @if($method->cashbox)
                                            <div class="linked-cashbox">
                                                <i class="ti ti-link"></i>
                                                {{ $method->cashbox->name }}
                                                <span class="cashbox-type-badge type-{{ $method->cashbox->type }}">
                                                    {{ $method->cashbox->typeArabic }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="linked-cashbox text-warning">
                                                <i class="ti ti-unlink"></i> غير مرتبط
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Shift Summary (if specific shift selected) --}}
        @if($reportData['shift_summary'])
            <div class="row mb-4">
                <div class="col-12">
                    <div class="section-card">
                        <div class="section-header">
                            <h6><i class="ti ti-clock"></i> ملخص الوردية</h6>
                        </div>
                        <div class="section-body p-3">
                            <div class="row g-3">
                                <div class="col-md-2 col-sm-4">
                                    <div class="stat-card primary">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon"><i class="ti ti-user"></i></div>
                                            <div>
                                                <div class="stat-label">الكاشير</div>
                                                <div class="stat-value fs-5">{{ $reportData['shift_summary']['cashier'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <div class="stat-card info">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon"><i class="ti ti-cash"></i></div>
                                            <div>
                                                <div class="stat-label">الرصيد الافتتاحي</div>
                                                <div class="stat-value fs-5">{{ number_format($reportData['shift_summary']['opening_cash'], 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <div class="stat-card success">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon"><i class="ti ti-receipt-2"></i></div>
                                            <div>
                                                <div class="stat-label">إجمالي المبيعات</div>
                                                <div class="stat-value fs-5">{{ number_format($reportData['shift_summary']['total_sales'], 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <div class="stat-card warning">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon"><i class="ti ti-target"></i></div>
                                            <div>
                                                <div class="stat-label">المتوقع</div>
                                                <div class="stat-value fs-5">{{ number_format($reportData['shift_summary']['expected_cash'], 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4">
                                    <div class="stat-card {{ $reportData['shift_summary']['difference'] >= 0 ? 'success' : 'danger' }}">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="stat-icon"><i class="ti ti-plus-minus"></i></div>
                                            <div>
                                                <div class="stat-label">الفرق</div>
                                                <div class="stat-value fs-5 {{ $reportData['shift_summary']['difference'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                                    {{ number_format($reportData['shift_summary']['difference'], 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Cashboxes breakdown --}}
                            @if(isset($reportData['shift_summary']['cashboxes']) && count($reportData['shift_summary']['cashboxes']) > 0)
                                <hr class="my-3">
                                <h6 class="mb-3"><i class="ti ti-cash me-1"></i> تفاصيل الصناديق</h6>
                                <div class="table-responsive">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>الصندوق</th>
                                                <th>النوع</th>
                                                <th>الافتتاحي</th>
                                                <th>المتوقع</th>
                                                <th>الإغلاق</th>
                                                <th>الفرق</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($reportData['shift_summary']['cashboxes'] as $cb)
                                                <tr>
                                                    <td><strong>{{ $cb['name'] }}</strong></td>
                                                    <td>
                                                        <span class="cashbox-type-badge type-{{ $cb['type'] }}">
                                                            {{ match($cb['type']) { 'cash' => 'نقدي', 'card' => 'بطاقة', 'wallet' => 'محفظة', 'bank' => 'مصرفي', default => 'نقدي' } }}
                                                        </span>
                                                    </td>
                                                    <td>{{ number_format($cb['opening_balance'], 2) }}</td>
                                                    <td>{{ number_format($cb['expected_balance'], 2) }}</td>
                                                    <td>{{ number_format($cb['closing_balance'], 2) }}</td>
                                                    <td class="{{ $cb['difference'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                                        {{ $cb['difference'] >= 0 ? '+' : '' }}{{ number_format($cb['difference'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            {{-- Payment breakdown in shift --}}
                            @if(count($reportData['shift_summary']['payment_totals']) > 0)
                                <hr class="my-3">
                                <h6 class="mb-3"><i class="ti ti-credit-card me-1"></i> توزيع المدفوعات</h6>
                                <div class="row g-2">
                                    @foreach($reportData['shift_summary']['payment_totals'] as $payment)
                                        <div class="col-auto">
                                            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                                {{ $payment['name'] }}: {{ number_format($payment['total'], 2) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Reconciliation Table --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="section-card">
                    <div class="section-header">
                        <h6><i class="ti ti-adjustments-check"></i> جدول المطابقة</h6>
                    </div>
                    <div class="section-body">
                        <div class="table-responsive">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>طريقة الدفع</th>
                                        <th>الخزينة</th>
                                        <th>النوع</th>
                                        <th>المتوقع</th>
                                        <th>الفعلي</th>
                                        <th>الفرق</th>
                                        <th>الحالة</th>
                                        <th>الربط</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['reconciliation'] as $row)
                                        <tr>
                                            <td>{{ $row['payment_method_name'] }}</td>
                                            <td>{{ $row['cashbox_name'] }}</td>
                                            <td>
                                                <span class="cashbox-type-badge type-{{ $row['cashbox_type'] }}">
                                                    {{ match($row['cashbox_type']) {
                                                        'cash' => 'نقدي',
                                                        'card' => 'بطاقة',
                                                        'wallet' => 'محفظة',
                                                        'bank' => 'مصرفي',
                                                        default => 'نقدي'
                                                    } }}
                                                </span>
                                            </td>
                                            <td class="amount-neutral">{{ number_format($row['expected'], 2) }}</td>
                                            <td class="amount-neutral">{{ number_format($row['actual'], 2) }}</td>
                                            <td class="{{ $row['difference'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                                {{ $row['difference'] >= 0 ? '+' : '' }}{{ number_format($row['difference'], 2) }}
                                            </td>
                                            <td>
                                                <span class="status-badge status-{{ $row['status'] }}">
                                                    {{ match($row['status']) {
                                                        'balanced' => 'متطابق',
                                                        'surplus' => 'زيادة',
                                                        'shortage' => 'نقص',
                                                        default => '-'
                                                    } }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($row['is_linked'])
                                                    <span class="linked-badge"><i class="ti ti-link"></i> مرتبط</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="ti ti-mood-empty fs-1 d-block mb-2"></i>
                                                لا توجد بيانات للمطابقة
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cashbox Details --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="section-card">
                    <div class="section-header">
                        <h6><i class="ti ti-building-bank"></i> تفاصيل الخزائن</h6>
                    </div>
                    <div class="section-body">
                        <div class="table-responsive">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>الخزينة</th>
                                        <th>النوع</th>
                                        <th>طريقة الدفع المرتبطة</th>
                                        <th>الوارد</th>
                                        <th>الصادر</th>
                                        <th>صافي الحركة</th>
                                        <th>الرصيد الحالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reportData['by_cashbox'] as $cashbox)
                                        <tr>
                                            <td><strong>{{ $cashbox['name'] }}</strong></td>
                                            <td>
                                                <span class="cashbox-type-badge type-{{ $cashbox['type'] }}">
                                                    {{ $cashbox['type_arabic'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($cashbox['linked_payment_method'])
                                                    <span class="badge bg-primary-subtle text-primary">
                                                        {{ $cashbox['linked_payment_method']['name'] }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="amount-positive">+{{ number_format($cashbox['total_in'], 2) }}</td>
                                            <td class="amount-negative">-{{ number_format($cashbox['total_out'], 2) }}</td>
                                            <td class="{{ $cashbox['net_change'] >= 0 ? 'amount-positive' : 'amount-negative' }}">
                                                {{ $cashbox['net_change'] >= 0 ? '+' : '' }}{{ number_format($cashbox['net_change'], 2) }}
                                            </td>
                                            <td class="amount-neutral">{{ number_format($cashbox['current_balance'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                لا توجد حركات على الخزائن
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Generated At --}}
        <div class="text-center text-muted small mb-4">
            <i class="ti ti-clock me-1"></i>
            تم إنشاء التقرير: {{ $reportData['generated_at'] }}
        </div>
    @else
        <div class="text-center py-5">
            <i class="ti ti-chart-bar text-muted" style="font-size: 4rem;"></i>
            <h5 class="mt-3 text-muted">اختر التاريخ واضغط "عرض التقرير"</h5>
        </div>
    @endif
</div>
@endsection
