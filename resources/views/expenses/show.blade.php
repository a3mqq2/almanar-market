@extends('layouts.app')

@section('title', 'تفاصيل المصروف')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">المصروفات</a></li>
    <li class="breadcrumb-item active">{{ $expense->reference_number }}</li>
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
    .amount-card {
        background: linear-gradient(135deg, rgba(var(--bs-danger-rgb), 0.1), rgba(var(--bs-danger-rgb), 0.05));
        border: 2px solid var(--bs-danger);
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
    }
    .amount-card .amount-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--bs-danger);
    }
    .amount-card .amount-label {
        color: var(--bs-secondary-color);
        font-size: 0.9rem;
    }

    [data-bs-theme="dark"] .info-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .info-card .info-value {
        color: #e9ecef;
    }
    [data-bs-theme="dark"] .amount-card {
        background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.1));
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">{{ $expense->reference_number }}</h4>
        <span class="badge bg-secondary fs-6">{{ $expense->category->name ?? '-' }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-right me-1"></i>رجوع
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>معلومات المصروف</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">العنوان</div>
                            <div class="info-value">{{ $expense->title }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">التصنيف</div>
                            <div class="info-value">{{ $expense->category->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">الخزينة</div>
                            <div class="info-value">{{ $expense->cashbox->name ?? '-' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">طريقة الدفع</div>
                            <div class="info-value">{{ $expense->paymentMethod->name ?? 'نقدي' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">تاريخ المصروف</div>
                            <div class="info-value">{{ $expense->expense_date->format('Y/m/d') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">تاريخ التسجيل</div>
                            <div class="info-value">{{ $expense->created_at->format('Y/m/d H:i') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">تم التسجيل بواسطة</div>
                            <div class="info-value">{{ $expense->creator->name ?? '-' }}</div>
                        </div>
                    </div>
                    @if($expense->shift_id)
                    <div class="col-md-6">
                        <div class="info-card">
                            <div class="info-label">الوردية</div>
                            <div class="info-value">
                                <a href="{{ route('shift-reports.show', $expense->shift_id) }}">#{{ $expense->shift_id }}</a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($expense->description)
                <div class="mt-3">
                    <div class="info-card">
                        <div class="info-label">الوصف</div>
                        <div class="info-value">{{ $expense->description }}</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($expense->attachment)
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-paperclip me-2"></i>المرفق</h6>
            </div>
            <div class="card-body">
                @php
                    $extension = pathinfo($expense->attachment, PATHINFO_EXTENSION);
                    $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                @endphp

                @if($isImage)
                <div class="mb-3">
                    <img src="{{ Storage::url($expense->attachment) }}" alt="المرفق" class="img-fluid rounded" style="max-height: 300px;">
                </div>
                @endif

                <a href="{{ Storage::url($expense->attachment) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="ti ti-download me-1"></i>عرض/تحميل المرفق
                </a>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="amount-card mb-4">
            <div class="amount-value">{{ number_format($expense->amount, 2) }}</div>
            <div class="amount-label">المبلغ</div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-receipt-2 me-2"></i>ملخص</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">الرقم المرجعي</span>
                    <span class="fw-medium">{{ $expense->reference_number }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">التصنيف</span>
                    <span class="fw-medium">{{ $expense->category->name ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">الخزينة</span>
                    <span class="fw-medium">{{ $expense->cashbox->name ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">التاريخ</span>
                    <span class="fw-medium">{{ $expense->expense_date->format('Y/m/d') }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">المبلغ</span>
                    <span class="fw-bold text-danger fs-5">{{ number_format($expense->amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
