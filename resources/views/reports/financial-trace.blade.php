@extends('layouts.app')

@section('title', 'تتبع العمليات المالية')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item active">تتبع العمليات المالية</li>
@endsection

@push('styles')
<style>
    .reference-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    [data-bs-theme="dark"] .reference-card {
        background: var(--bs-card-bg);
    }
    .ledger-card {
        background: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 8px;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    [data-bs-theme="dark"] .ledger-card {
        background: var(--bs-card-bg);
    }
    .ledger-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .ledger-card-header.supplier {
        background: rgba(var(--bs-warning-rgb), 0.1);
    }
    .ledger-card-header.supplier i {
        color: var(--bs-warning);
    }
    .ledger-card-header.cashbox {
        background: rgba(var(--bs-info-rgb), 0.1);
    }
    .ledger-card-header.cashbox i {
        color: var(--bs-info);
    }
    .ledger-card-header h6 {
        margin: 0;
        font-weight: 600;
    }
    .ledger-card-body {
        padding: 0;
    }
    .trace-table {
        width: 100%;
        margin: 0;
    }
    .trace-table thead {
        background: var(--bs-tertiary-bg);
    }
    .trace-table th {
        font-weight: 600;
        font-size: 0.85rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        white-space: nowrap;
    }
    .trace-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--bs-border-color);
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .trace-table tbody tr:last-child td {
        border-bottom: none;
    }
    .trace-table tbody tr:hover {
        background: var(--bs-tertiary-bg);
    }
    .amount-in { color: var(--bs-success); font-weight: 600; }
    .amount-out { color: var(--bs-danger); font-weight: 600; }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--bs-secondary-color);
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    .info-label {
        color: var(--bs-secondary-color);
        font-size: 0.8rem;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-search me-2"></i>البحث عن عملية</h6>
            </div>
            <div class="card-body">
                <form id="traceForm">
                    <div class="mb-3">
                        <label class="form-label">نوع العملية</label>
                        <select class="form-select" id="referenceType" name="reference_type">
                            <option value="">اختر النوع...</option>
                            <option value="App\Models\Purchase">فاتورة مشتريات</option>
                            <option value="supplier_payment">سداد مورد</option>
                        </select>
                    </div>
                    <div class="mb-3" id="purchaseSelectGroup" style="display: none;">
                        <label class="form-label">الفاتورة</label>
                        <select class="form-select" id="purchaseSelect" name="reference_id">
                            <option value="">اختر الفاتورة...</option>
                            @foreach($purchases as $purchase)
                                <option value="{{ $purchase->id }}">
                                    #{{ $purchase->id }} - {{ $purchase->supplier->name }} ({{ number_format($purchase->total, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="referenceIdGroup" style="display: none;">
                        <label class="form-label">رقم العملية</label>
                        <input type="number" class="form-control" id="referenceId" name="reference_id_manual" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="searchBtn">
                        <i class="ti ti-search me-2"></i>بحث
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="ti ti-route me-2"></i>مسار العملية المالية</h6>
            </div>
            <div class="card-body">
                <div id="traceResults">
                    <div class="empty-state">
                        <i class="ti ti-route-off d-block"></i>
                        <h6>اختر عملية لتتبعها</h6>
                        <p class="small mb-0">قم باختيار نوع العملية ورقمها من القائمة لعرض المسار المالي الكامل</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const referenceTypeSelect = document.getElementById('referenceType');
    const purchaseSelectGroup = document.getElementById('purchaseSelectGroup');
    const referenceIdGroup = document.getElementById('referenceIdGroup');
    const purchaseSelect = document.getElementById('purchaseSelect');
    const referenceIdInput = document.getElementById('referenceId');

    referenceTypeSelect.addEventListener('change', function() {
        if (this.value == 'App\\Models\\Purchase') {
            purchaseSelectGroup.style.display = 'block';
            referenceIdGroup.style.display = 'none';
            purchaseSelect.required = true;
            referenceIdInput.required = false;
        } else if (this.value == 'supplier_payment') {
            purchaseSelectGroup.style.display = 'none';
            referenceIdGroup.style.display = 'block';
            purchaseSelect.required = false;
            referenceIdInput.required = true;
        } else {
            purchaseSelectGroup.style.display = 'none';
            referenceIdGroup.style.display = 'none';
            purchaseSelect.required = false;
            referenceIdInput.required = false;
        }
    });

    document.getElementById('traceForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const referenceType = referenceTypeSelect.value;
        let referenceId = null;

        if (referenceType == 'App\\Models\\Purchase') {
            referenceId = purchaseSelect.value;
        } else {
            referenceId = referenceIdInput.value;
        }

        if (!referenceType || !referenceId) {
            return;
        }

        const btn = document.getElementById('searchBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري البحث...';

        try {
            const response = await fetch(`{{ route('reports.financial-trace.data') }}?reference_type=${encodeURIComponent(referenceType)}&reference_id=${referenceId}`);
            const result = await response.json();

            if (result.success) {
                renderTrace(result.data);
            } else {
                document.getElementById('traceResults').innerHTML = `
                    <div class="alert alert-warning text-center mb-0">
                        <i class="ti ti-alert-circle me-2"></i>${result.message || 'لم يتم العثور على بيانات'}
                    </div>
                `;
            }
        } catch (error) {
            document.getElementById('traceResults').innerHTML = `
                <div class="alert alert-danger text-center mb-0">
                    <i class="ti ti-alert-circle me-2"></i>حدث خطأ في الاتصال
                </div>
            `;
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-search me-2"></i>بحث';
    });

    function renderTrace(data) {
        const container = document.getElementById('traceResults');
        let html = '';

        if (data.reference) {
            html += `
                <div class="reference-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">${data.reference.type_arabic} #${data.reference.id}</h5>
                            <div class="d-flex flex-wrap gap-3 text-muted small">
                                <span><i class="ti ti-user me-1"></i>${data.reference.supplier_name}</span>
                                <span><i class="ti ti-calendar me-1"></i>${data.reference.date}</span>
                                <span><i class="ti ti-credit-card me-1"></i>${data.reference.payment_type_arabic}</span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fs-4 fw-bold text-primary">${parseFloat(data.reference.total).toFixed(2)}</div>
                            <span class="badge bg-${data.reference.status == 'approved' ? 'success' : (data.reference.status == 'cancelled' ? 'danger' : 'warning')}">${data.reference.status_arabic}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top">
                        <a href="${data.reference.url}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-external-link me-1"></i>عرض تفاصيل الفاتورة
                        </a>
                    </div>
                </div>
            `;
        }

        if (data.supplier_transactions && data.supplier_transactions.length > 0) {
            html += `
                <div class="ledger-card">
                    <div class="ledger-card-header supplier">
                        <i class="ti ti-truck fs-5"></i>
                        <h6>حركات حساب المورد</h6>
                    </div>
                    <div class="ledger-card-body">
                        <div class="table-responsive">
                            <table class="trace-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>الحساب</th>
                                        <th>النوع</th>
                                        <th>المبلغ</th>
                                        <th>الرصيد بعد</th>
                                        <th>الخزينة</th>
                                        <th>البيان</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            data.supplier_transactions.forEach(t => {
                const amountClass = t.type == 'debit' ? 'amount-out' : 'amount-in';
                html += `
                    <tr>
                        <td class="fw-medium">${t.id}</td>
                        <td>${t.transaction_date}</td>
                        <td>${t.account_name}</td>
                        <td><span class="badge bg-${t.type == 'debit' ? 'danger' : 'success'}">${t.type_arabic}</span></td>
                        <td class="${amountClass}">${parseFloat(t.amount).toFixed(2)}</td>
                        <td class="fw-medium">${parseFloat(t.balance_after).toFixed(2)}</td>
                        <td>${t.cashbox_name || '<span class="text-muted">-</span>'}</td>
                        <td>${t.description}</td>
                    </tr>
                `;
            });
            html += `</tbody></table></div></div></div>`;
        }

        if (data.cashbox_transactions && data.cashbox_transactions.length > 0) {
            html += `
                <div class="ledger-card">
                    <div class="ledger-card-header cashbox">
                        <i class="ti ti-building-bank fs-5"></i>
                        <h6>حركات الخزينة</h6>
                    </div>
                    <div class="ledger-card-body">
                        <div class="table-responsive">
                            <table class="trace-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>الخزينة</th>
                                        <th>النوع</th>
                                        <th>المبلغ</th>
                                        <th>الرصيد بعد</th>
                                        <th>البيان</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            data.cashbox_transactions.forEach(t => {
                const amountClass = (t.type == 'in' || t.type == 'transfer_in') ? 'amount-in' : 'amount-out';
                html += `
                    <tr>
                        <td class="fw-medium">${t.id}</td>
                        <td>${t.transaction_date}</td>
                        <td>${t.account_name}</td>
                        <td><span class="badge bg-${(t.type == 'in' || t.type == 'transfer_in') ? 'success' : 'danger'}">${t.type_arabic}</span></td>
                        <td class="${amountClass}">${parseFloat(t.amount).toFixed(2)}</td>
                        <td class="fw-medium">${parseFloat(t.balance_after).toFixed(2)}</td>
                        <td>${t.description}</td>
                    </tr>
                `;
            });
            html += `</tbody></table></div></div></div>`;
        }

        if ((!data.supplier_transactions || data.supplier_transactions.length == 0) &&
            (!data.cashbox_transactions || data.cashbox_transactions.length == 0)) {
            html += `
                <div class="empty-state">
                    <i class="ti ti-file-off d-block"></i>
                    <h6>لا توجد حركات مالية</h6>
                    <p class="small mb-0">لم يتم العثور على أي حركات مالية مرتبطة بهذه العملية</p>
                </div>
            `;
        }

        container.innerHTML = html;
    }
});
</script>
@endpush
