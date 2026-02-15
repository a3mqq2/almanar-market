@extends('layouts.app')

@section('title', 'الخزينة - ' . $cashbox->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cashboxes.index') }}">الخزائن</a></li>
    <li class="breadcrumb-item active">{{ $cashbox->name }}</li>
@endsection

@push('styles')
<style>
    :root {
        --header-bg: var(--bs-tertiary-bg);
        --header-border: var(--bs-border-color);
        --label-color: var(--bs-secondary-color);
        --tab-color: var(--bs-secondary-color);
        --tab-active: var(--bs-primary);
        --card-border: var(--bs-border-color);
    }

    .cashbox-header {
        background: #fff;
        border: 1px solid var(--header-border);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    [data-bs-theme="dark"] .cashbox-header {
        background: var(--bs-tertiary-bg);
    }
    .info-label {
        color: var(--label-color);
        font-size: 0.875rem;
    }
    .balance-card {
        border: 1px solid var(--card-border);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
        background: var(--bs-body-bg);
    }
    .balance-card .balance-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .balance-card .balance-label {
        font-size: 0.8rem;
        color: var(--label-color);
    }
    .nav-tabs .nav-link {
        color: var(--tab-color);
        border: none;
        padding: 0.75rem 1.25rem;
    }
    .nav-tabs .nav-link.active {
        color: var(--tab-active);
        border-bottom: 2px solid var(--tab-active);
        background: transparent;
    }
    .table thead {
        background: var(--bs-tertiary-bg);
    }
    .table th {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    .table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .toast-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    .empty-state i {
        font-size: 4rem;
        color: var(--bs-secondary-color);
    }
    .transaction-in { color: var(--bs-success); }
    .transaction-out { color: var(--bs-danger); }
    .quick-action-btn {
        padding: 1.5rem;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid var(--card-border);
        background: var(--bs-body-bg);
    }
    .quick-action-btn:hover {
        background: var(--bs-tertiary-bg);
    }
    .quick-action-btn i {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="cashbox-header">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="flex-grow-1">
            <h5 class="mb-1">{{ $cashbox->name }}</h5>
            <div class="d-flex gap-2 align-items-center text-muted small">
                <span class="badge {{ $cashbox->status ? 'bg-success' : 'bg-secondary' }}">
                    {{ $cashbox->status_arabic }}
                </span>
            </div>
        </div>
        <div class="text-end">
            <div class="info-label">الرصيد الحالي</div>
            <div class="fs-3 fw-bold text-primary" id="currentBalance">{{ number_format($cashbox->current_balance, 2) }}</div>
        </div>
        <div class="border-start ps-3 text-end">
            <div class="info-label">إجمالي الوارد</div>
            <div class="fs-5 text-success" id="totalIn">{{ number_format($stats['total_in'], 2) }}</div>
        </div>
        <div class="border-start ps-3 text-end">
            <div class="info-label">إجمالي الصادر</div>
            <div class="fs-5 text-danger" id="totalOut">{{ number_format($stats['total_out'], 2) }}</div>
        </div>
        <div class="border-start ps-3 d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-outline-warning btn-sm" id="editCashboxBtn" title="تعديل">
                <i class="ti ti-edit me-1"></i>تعديل
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-bottom-0 pb-0">
        <ul class="nav nav-tabs" id="accountTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview">
                    <i class="ti ti-info-circle me-1"></i>نظرة عامة
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="transaction-tab" data-bs-toggle="tab" data-bs-target="#transaction">
                    <i class="ti ti-plus me-1"></i>حركة جديدة
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger">
                    <i class="ti ti-list me-1"></i>سجل الحركات
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="overview">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="info-label" width="150">اسم الخزينة</td>
                                <td class="fw-medium">{{ $cashbox->name }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">الحالة</td>
                                <td>
                                    <span class="badge {{ $cashbox->status ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $cashbox->status_arabic }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="info-label">تاريخ الإنشاء</td>
                                <td>{{ $cashbox->created_at->format('Y-m-d') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="info-label" width="150">الرصيد الافتتاحي</td>
                                <td class="fw-medium" id="openingBalance">{{ number_format($cashbox->opening_balance, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">إجمالي الوارد</td>
                                <td class="text-success fw-medium">{{ number_format($stats['total_in'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">إجمالي الصادر</td>
                                <td class="text-danger fw-medium">{{ number_format($stats['total_out'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">عدد الحركات</td>
                                <td>{{ $stats['transactions_count'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if(!$cashbox->transactions()->exists())
                <div class="mt-4 p-3 border rounded">
                    <h6 class="mb-3">تعيين الرصيد الافتتاحي</h6>
                    <form id="openingBalanceForm" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">الرصيد الافتتاحي</label>
                            <input type="number" class="form-control" id="newOpeningBalance" value="{{ $cashbox->opening_balance }}" min="0" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-check me-1"></i>حفظ
                            </button>
                        </div>
                    </form>
                </div>
                @endif

                <div class="mt-4">
                    <h6 class="mb-3">آخر الحركات</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                    <th>الرصيد بعدها</th>
                                    <th>البيان</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cashbox->transactions->take(5) as $t)
                                    <tr>
                                        <td>{{ $t->transaction_date->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $t->is_in ? 'success' : 'danger' }}">
                                                {{ $t->type_arabic }}
                                            </span>
                                        </td>
                                        <td class="{{ $t->is_in ? 'transaction-in' : 'transaction-out' }} fw-bold">
                                            {{ $t->is_in ? '+' : '-' }}{{ number_format($t->amount, 2) }}
                                        </td>
                                        <td class="fw-medium">{{ number_format($t->balance_after, 2) }}</td>
                                        <td>{{ $t->description ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">لا توجد حركات</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="transaction">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="quick-action-btn text-success" data-bs-toggle="modal" data-bs-target="#depositModal">
                            <i class="ti ti-arrow-down-left d-block"></i>
                            <div class="fw-bold">إيداع</div>
                            <small class="text-muted">إضافة مبلغ للخزينة</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-action-btn text-danger" data-bs-toggle="modal" data-bs-target="#withdrawModal">
                            <i class="ti ti-arrow-up-right d-block"></i>
                            <div class="fw-bold">سحب</div>
                            <small class="text-muted">سحب مبلغ من الخزينة</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="quick-action-btn text-info" data-bs-toggle="modal" data-bs-target="#transferModal">
                            <i class="ti ti-arrows-exchange d-block"></i>
                            <div class="fw-bold">تحويل</div>
                            <small class="text-muted">تحويل لخزينة أخرى</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 border rounded">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="info-label">الرصيد الحالي</div>
                            <div class="fs-4 fw-bold text-primary" id="txCurrentBalance">{{ number_format($cashbox->current_balance, 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">إجمالي الوارد</div>
                            <div class="fs-5 text-success" id="txTotalIn">{{ number_format($stats['total_in'], 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">إجمالي الصادر</div>
                            <div class="fs-5 text-danger" id="txTotalOut">{{ number_format($stats['total_out'], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="ledger">
                <div class="row mb-3 g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label small mb-1">النوع</label>
                        <select class="form-select form-select-sm" id="filterType" style="min-width: 120px;">
                            <option value="">كل الأنواع</option>
                            <option value="in">إيداع</option>
                            <option value="out">سحب</option>
                            <option value="transfer">تحويلات</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">من تاريخ</label>
                        <input type="date" class="form-control form-control-sm" id="filterDateFrom">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">إلى تاريخ</label>
                        <input type="date" class="form-control form-control-sm" id="filterDateTo">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary btn-sm" id="filterLedgerBtn">
                            <i class="ti ti-filter me-1"></i>تصفية
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearLedgerFilters">
                            <i class="ti ti-x me-1"></i>مسح
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-success btn-sm" id="refreshLedger">
                            <i class="ti ti-refresh me-1"></i>تحديث
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="{{ route('cashboxes.print', $cashbox) }}" target="_blank" class="btn btn-outline-dark btn-sm" id="printStatementBtn" title="طباعة كشف الحساب">
                            <i class="ti ti-printer me-1"></i>طباعة الكشف
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0" id="ledgerTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الرصيد بعدها</th>
                                <th>البيان</th>
                                <th>بواسطة</th>
                                <th>تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerTableBody">
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small" id="ledgerPaginationInfo"></div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="ledgerPaginationLinks"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success"><i class="ti ti-arrow-down-left me-1"></i>إيداع في الخزينة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="depositForm">
                <div class="modal-body">
                    <div class="alert alert-success small">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم إضافة هذا المبلغ إلى رصيد الخزينة
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="depositAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ العملية</label>
                        <input type="date" class="form-control" id="depositDate" name="transaction_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البيان</label>
                        <input type="text" class="form-control" id="depositDescription" name="description" placeholder="مثال: إيداع نقدي">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm" id="depositSubmitBtn">
                        <i class="ti ti-check me-1"></i>إيداع
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="withdrawModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger"><i class="ti ti-arrow-up-right me-1"></i>سحب من الخزينة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="withdrawForm">
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        <i class="ti ti-alert-circle me-1"></i>
                        سيتم خصم هذا المبلغ من رصيد الخزينة
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="withdrawAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ العملية</label>
                        <input type="date" class="form-control" id="withdrawDate" name="transaction_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البيان</label>
                        <input type="text" class="form-control" id="withdrawDescription" name="description" placeholder="مثال: مصروفات">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="withdrawSubmitBtn">
                        <i class="ti ti-check me-1"></i>سحب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-info"><i class="ti ti-arrows-exchange me-1"></i>تحويل لخزينة أخرى</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="transferForm">
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم تحويل المبلغ من هذه الخزينة إلى الخزينة المحددة
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الخزينة المستلمة <span class="text-danger">*</span></label>
                        <select class="form-select" id="toCashboxId" name="to_cashbox_id" required>
                            <option value="">اختر الخزينة...</option>
                            @foreach($otherCashboxes as $cb)
                                <option value="{{ $cb->id }}">{{ $cb->name }} ({{ number_format($cb->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="transferAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ العملية</label>
                        <input type="date" class="form-control" id="transferDate" name="transaction_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البيان</label>
                        <input type="text" class="form-control" id="transferDescription" name="description" placeholder="مثال: تحويل داخلي">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info btn-sm" id="transferSubmitBtn">
                        <i class="ti ti-check me-1"></i>تحويل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCashboxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-edit me-1"></i>تعديل الخزينة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCashboxForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الخزينة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editCashboxName" name="name" value="{{ $cashbox->name }}" required>
                        <div class="invalid-feedback" id="editNameFeedback">اسم الخزينة مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="editCashboxStatus" name="status" {{ $cashbox->status ? 'checked' : '' }}>
                            <label class="form-check-label" for="editCashboxStatus">نشط</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="editSubmitBtn">
                        <i class="ti ti-check me-1"></i>حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cashboxId = {{ $cashbox->id }};
    const csrfToken = '{{ csrf_token() }}';
    let ledgerPage = 1;
    let isNameValid = true;
    let nameCheckTimeout = null;

    const tabStorageKey = `cashbox_${cashboxId}_active_tab`;
    const savedTab = localStorage.getItem(tabStorageKey);
    if (savedTab) {
        const tabEl = document.querySelector(`#accountTabs button[data-bs-target="${savedTab}"]`);
        if (tabEl) new bootstrap.Tab(tabEl).show();
    }
    document.querySelectorAll('#accountTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem(tabStorageKey, e.target.getAttribute('data-bs-target'));
        });
    });

    document.getElementById('editCashboxBtn').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('editCashboxModal')).show();
    });

    document.getElementById('editCashboxName').addEventListener('input', function() {
        clearTimeout(nameCheckTimeout);
        const name = this.value.trim();

        if (!name) {
            this.classList.remove('is-invalid', 'is-valid');
            isNameValid = true;
            return;
        }

        nameCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("cashboxes.check-name") }}?name=${encodeURIComponent(name)}&exclude_id=${cashboxId}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('editNameFeedback').textContent = 'اسم الخزينة مستخدم بالفعل';
                    isNameValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    isNameValid = true;
                }
            } catch (error) {
                console.error('Error checking name:', error);
            }
        }, 300);
    });

    document.getElementById('editCashboxForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('editCashboxName').value.trim();
        const status = document.getElementById('editCashboxStatus').checked;

        if (!name) {
            document.getElementById('editCashboxName').classList.add('is-invalid');
            return;
        }

        if (!isNameValid) {
            document.getElementById('editCashboxName').focus();
            return;
        }

        const btn = document.getElementById('editSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/cashboxes/${cashboxId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, status })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editCashboxModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ التغييرات';
    });

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    function updateBalanceDisplays(newBalance, totalIn = null, totalOut = null) {
        const formatted = parseFloat(newBalance).toFixed(2);
        document.getElementById('currentBalance').textContent = formatted;
        document.getElementById('txCurrentBalance').textContent = formatted;

        if (totalIn != null) {
            document.getElementById('totalIn').textContent = parseFloat(totalIn).toFixed(2);
            document.getElementById('txTotalIn').textContent = parseFloat(totalIn).toFixed(2);
        }
        if (totalOut != null) {
            document.getElementById('totalOut').textContent = parseFloat(totalOut).toFixed(2);
            document.getElementById('txTotalOut').textContent = parseFloat(totalOut).toFixed(2);
        }
    }

    document.getElementById('openingBalanceForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const amount = document.getElementById('newOpeningBalance').value;

        try {
            const response = await fetch(`/cashboxes/${cashboxId}/opening-balance`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ opening_balance: amount })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                document.getElementById('openingBalance').textContent = parseFloat(amount).toFixed(2);
                updateBalanceDisplays(amount);
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    document.getElementById('depositForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('depositSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإيداع...';

        const data = {
            amount: document.getElementById('depositAmount').value,
            transaction_date: document.getElementById('depositDate').value,
            description: document.getElementById('depositDescription').value
        };

        try {
            const response = await fetch(`/cashboxes/${cashboxId}/deposit`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('depositModal')).hide();
                this.reset();
                document.getElementById('depositDate').value = '{{ date("Y-m-d") }}';
                refreshAccountData();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>إيداع';
    });

    document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('withdrawSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري السحب...';

        const data = {
            amount: document.getElementById('withdrawAmount').value,
            transaction_date: document.getElementById('withdrawDate').value,
            description: document.getElementById('withdrawDescription').value
        };

        try {
            const response = await fetch(`/cashboxes/${cashboxId}/withdraw`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('withdrawModal')).hide();
                this.reset();
                document.getElementById('withdrawDate').value = '{{ date("Y-m-d") }}';
                refreshAccountData();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>سحب';
    });

    document.getElementById('transferForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('transferSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري التحويل...';

        const data = {
            to_cashbox_id: document.getElementById('toCashboxId').value,
            amount: document.getElementById('transferAmount').value,
            transaction_date: document.getElementById('transferDate').value,
            description: document.getElementById('transferDescription').value
        };

        try {
            const response = await fetch(`/cashboxes/${cashboxId}/transfer`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
                this.reset();
                document.getElementById('transferDate').value = '{{ date("Y-m-d") }}';
                refreshAccountData();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>تحويل';
    });

    async function refreshAccountData() {
        try {
            const response = await fetch(`/cashboxes/${cashboxId}/summary`);
            const result = await response.json();
            if (result.success) {
                updateBalanceDisplays(
                    result.cashbox.current_balance,
                    result.stats.total_in,
                    result.stats.total_out
                );
                document.getElementById('openingBalance').textContent = parseFloat(result.cashbox.opening_balance).toFixed(2);
            }
        } catch (error) {
            console.error('Error refreshing account data:', error);
        }
    }

    async function loadLedger(page = 1) {
        ledgerPage = page;
        const tbody = document.getElementById('ledgerTableBody');
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3"><span class="spinner-border spinner-border-sm"></span> جاري التحميل...</td></tr>';

        const params = new URLSearchParams();
        params.append('page', page);

        const type = document.getElementById('filterType').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;

        if (type) params.append('type', type);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        try {
            const response = await fetch(`/cashboxes/${cashboxId}/transactions?${params}`);
            const result = await response.json();

            if (result.success) {
                renderLedger(result.data, result.meta);
            }
        } catch (error) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-3">حدث خطأ في تحميل البيانات</td></tr>';
        }
    }

    function renderLedger(data, meta) {
        const tbody = document.getElementById('ledgerTableBody');

        if (data.length == 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="ti ti-receipt-off d-block mb-2"></i>
                            <p class="text-muted mb-0">لا توجد حركات</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('ledgerPaginationInfo').textContent = '';
            document.getElementById('ledgerPaginationLinks').innerHTML = '';
            return;
        }

        let html = '';
        data.forEach((t, index) => {
            const rowNum = meta.from + index;
            html += `
                <tr>
                    <td>${rowNum}</td>
                    <td>${t.transaction_date}</td>
                    <td>
                        <span class="badge bg-${t.is_in ? 'success' : 'danger'}">
                            ${t.type_arabic}
                        </span>
                        ${t.related_cashbox ? `<br><small class="text-muted">${t.related_cashbox}</small>` : ''}
                    </td>
                    <td class="${t.is_in ? 'transaction-in' : 'transaction-out'} fw-bold">
                        ${t.is_in ? '+' : '-'}${parseFloat(t.amount).toFixed(2)}
                    </td>
                    <td class="fw-medium">${parseFloat(t.balance_after).toFixed(2)}</td>
                    <td>${t.description || '-'}</td>
                    <td>${t.created_by}</td>
                    <td>${t.created_at}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        renderLedgerPagination(meta);
    }

    function renderLedgerPagination(meta) {
        document.getElementById('ledgerPaginationInfo').textContent =
            `عرض ${meta.from || 0} إلى ${meta.to || 0} من ${meta.total} حركة`;

        const paginationLinks = document.getElementById('ledgerPaginationLinks');
        let html = '';

        if (meta.last_page > 1) {
            html += `
                <li class="page-item ${meta.current_page == 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadLedgerPage(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            for (let i = 1; i <= meta.last_page; i++) {
                if (i == 1 || i == meta.last_page || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
                    html += `
                        <li class="page-item ${i == meta.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadLedgerPage(${i}); return false;">${i}</a>
                        </li>
                    `;
                } else if (i == meta.current_page - 3 || i == meta.current_page + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += `
                <li class="page-item ${meta.current_page == meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadLedgerPage(${meta.current_page + 1}); return false;">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
            `;
        }

        paginationLinks.innerHTML = html;
    }

    window.loadLedgerPage = function(page) {
        loadLedger(page);
    };

    function updatePrintUrl() {
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const baseUrl = '{{ route("cashboxes.print", $cashbox) }}';
        const params = new URLSearchParams();
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);
        const url = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
        document.getElementById('printStatementBtn').href = url;
    }

    document.getElementById('filterLedgerBtn').addEventListener('click', () => {
        loadLedger(1);
        updatePrintUrl();
    });
    document.getElementById('refreshLedger').addEventListener('click', () => loadLedger(ledgerPage));

    document.getElementById('clearLedgerFilters').addEventListener('click', function() {
        document.getElementById('filterType').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        loadLedger(1);
        updatePrintUrl();
    });

    document.getElementById('ledger-tab').addEventListener('shown.bs.tab', function() {
        loadLedger(1);
        updatePrintUrl();
    });
});
</script>
@endpush
