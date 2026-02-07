@extends('layouts.app')

@section('title', 'مصروف جديد')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('expenses.index') }}">المصروفات</a></li>
    <li class="breadcrumb-item active">مصروف جديد</li>
@endsection

@push('styles')
<style>
    .form-card {
        background: var(--bs-card-bg, #fff);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.5rem;
    }
    .info-alert {
        background: rgba(var(--bs-info-rgb), 0.1);
        border: 1px solid rgba(var(--bs-info-rgb), 0.3);
        border-radius: 8px;
        padding: 1rem;
    }

    [data-bs-theme="dark"] .form-card {
        background: #212529;
        border-color: #373b3e;
    }
    [data-bs-theme="dark"] .form-control,
    [data-bs-theme="dark"] .form-select {
        background-color: #2b3035;
        border-color: #373b3e;
        color: #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">مصروف جديد</h4>
    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-right me-1"></i>رجوع
    </a>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="form-card">
            <form id="expenseForm" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">العنوان <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">التصنيف <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">اختر التصنيف</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="amount" name="amount" required min="0.01" step="0.01">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">الخزينة <span class="text-danger">*</span></label>
                        <select class="form-select" id="cashbox_id" name="cashbox_id" required>
                            <option value="">اختر الخزينة</option>
                            @foreach($cashboxes as $cashbox)
                                <option value="{{ $cashbox->id }}" data-balance="{{ $cashbox->current_balance }}">
                                    {{ $cashbox->name }} ({{ number_format($cashbox->current_balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">طريقة الدفع</label>
                        <select class="form-select" id="payment_method_id" name="payment_method_id">
                            <option value="">نقدي</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">تاريخ المصروف <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="expense_date" name="expense_date" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">مرفق (اختياري)</label>
                        <input type="file" class="form-control" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">صورة أو PDF - الحد الأقصى 5MB</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">الوصف (اختياري)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="ti ti-check me-1"></i>تسجيل المصروف
                    </button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-info-circle me-2"></i>ملاحظات</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0 pe-3">
                    <li class="mb-2">سيتم تسجيل المصروف وخصم المبلغ مباشرة من الخزينة المختارة.</li>
                    <li class="mb-2">تأكد من اختيار الخزينة الصحيحة والمبلغ المطلوب.</li>
                    <li class="mb-2">يجب أن يكون رصيد الخزينة كافياً لتغطية المصروف.</li>
                    <li>يمكنك إرفاق صورة الفاتورة أو الإيصال للتوثيق.</li>
                </ul>
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

document.getElementById('cashbox_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const balance = parseFloat(selected.dataset.balance || 0);
    const amountInput = document.getElementById('amount');

    if (amountInput.value && parseFloat(amountInput.value) > balance) {
        showToast(`تنبيه: رصيد الخزينة (${balance.toFixed(2)}) أقل من المبلغ المدخل`, 'warning');
    }
});

document.getElementById('amount').addEventListener('input', function() {
    const cashboxSelect = document.getElementById('cashbox_id');
    const selected = cashboxSelect.options[cashboxSelect.selectedIndex];

    if (selected.value) {
        const balance = parseFloat(selected.dataset.balance || 0);
        if (parseFloat(this.value) > balance) {
            showToast(`تنبيه: رصيد الخزينة (${balance.toFixed(2)}) أقل من المبلغ المدخل`, 'warning');
        }
    }
});

document.getElementById('expenseForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري التسجيل...';

    const formData = new FormData(this);

    try {
        const response = await fetch('{{ route('expenses.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location = '{{ route('expenses.index') }}';
            }, 1000);
        } else {
            showToast(data.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>تسجيل المصروف';
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>تسجيل المصروف';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.location = '{{ route('expenses.index') }}';
    }
});
</script>
@endpush
