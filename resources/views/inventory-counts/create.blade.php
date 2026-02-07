@extends('layouts.app')

@section('title', 'جرد جديد')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('inventory-counts.index') }}">جرد المخزون</a></li>
    <li class="breadcrumb-item active">جرد جديد</li>
@endsection

@push('styles')
<style>
    .type-card {
        background: var(--bs-card-bg, #fff);
        border: 2px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .type-card:hover {
        border-color: var(--bs-primary);
        transform: translateY(-2px);
    }
    .type-card.selected {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.1);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.15);
        position: relative;
    }
    .check-badge {
        display: none;
        position: absolute;
        top: 10px;
        left: 10px;
        width: 28px;
        height: 28px;
        background: #0d6efd;
        color: #fff;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    .type-card.selected .check-badge {
        display: flex !important;
    }
    .type-card .type-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1rem;
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
    }
    .type-card .type-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .type-card .type-desc {
        color: var(--bs-secondary-color);
        font-size: 0.9rem;
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

<div class="row justify-content-center">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard-plus me-2"></i>إنشاء جرد جديد</h5>
            </div>
            <div class="card-body">
                <form id="createForm">
                    <div class="mb-4">
                        <label class="form-label fw-medium">نوع الجرد <span class="text-danger">*</span></label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="type-card selected" data-type="full" onclick="selectType('full')">
                                    <span class="check-badge"><i class="ti ti-check"></i></span>
                                    <div class="type-icon">
                                        <i class="ti ti-box"></i>
                                    </div>
                                    <div class="type-title">جرد شامل</div>
                                    <div class="type-desc">جرد جميع الأصناف في المخزون</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="type-card" data-type="partial" onclick="selectType('partial')">
                                    <span class="check-badge"><i class="ti ti-check"></i></span>
                                    <div class="type-icon">
                                        <i class="ti ti-filter"></i>
                                    </div>
                                    <div class="type-title">جرد جزئي</div>
                                    <div class="type-desc">جرد أصناف محددة فقط</div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="count_type" id="countType" value="full">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">ملاحظات</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="ملاحظات إضافية (اختياري)..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>ملاحظة:</strong> سيتم إنشاء الجرد كمسودة أولاً، ثم يمكنك بدء الجرد الفعلي.
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="{{ route('inventory-counts.index') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-right me-1"></i>رجوع
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-plus me-1"></i>إنشاء الجرد
                        </button>
                    </div>
                </form>
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

function selectType(type) {
    document.querySelectorAll('.type-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`[data-type="${type}"]`).classList.add('selected');
    document.getElementById('countType').value = type;
}

document.getElementById('createForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإنشاء...';

    try {
        const response = await fetch('{{ route("inventory-counts.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                count_type: document.getElementById('countType').value,
                notes: document.getElementById('notes').value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 500);
        } else {
            showToast(data.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ti ti-plus me-1"></i>إنشاء الجرد';
        }
    } catch (error) {
        showToast('حدث خطأ في الاتصال', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-plus me-1"></i>إنشاء الجرد';
    }
});
</script>
@endpush
