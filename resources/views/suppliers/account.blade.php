@extends('layouts.app')

@section('title', 'حساب المورد - ' . $supplier->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">الموردين</a></li>
    <li class="breadcrumb-item active">{{ $supplier->name }}</li>
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

    .supplier-header {
        background: #fff;
        border: 1px solid var(--header-border);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    [data-bs-theme="dark"] .supplier-header {
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
    .transaction-debit { color: var(--bs-danger); }
    .transaction-credit { color: var(--bs-success); }
    .quick-action-btn {
        padding: 1rem;
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
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<div class="supplier-header">
    <div class="d-flex align-items-center gap-3">
        <div class="flex-grow-1">
            <h5 class="mb-1">{{ $supplier->name }}</h5>
            <div class="d-flex gap-2 align-items-center text-muted small">
                <span dir="ltr"><i class="ti ti-phone me-1"></i>{{ $supplier->phone }}</span>
                <span class="text-muted">|</span>
                <span class="badge {{ $supplier->status ? 'bg-success' : 'bg-secondary' }}">
                    {{ $supplier->status ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
        <div class="text-end">
            <div class="info-label">الرصيد الحالي</div>
            <div class="fs-4 fw-bold {{ $supplier->current_balance > 0 ? 'text-danger' : ($supplier->current_balance < 0 ? 'text-success' : '') }}" id="currentBalance">{{ number_format($supplier->current_balance, 2) }}</div>
        </div>
        <div class="border-start ps-3 text-end">
            <div class="info-label">إجمالي المدين</div>
            <div class="fs-5 text-danger" id="totalDebit">{{ number_format($stats['total_debit'], 2) }}</div>
        </div>
        <div class="border-start ps-3 text-end">
            <div class="info-label">إجمالي السداد</div>
            <div class="fs-5 text-success" id="totalCredit">{{ number_format($stats['total_credit'], 2) }}</div>
        </div>
        <div class="border-start ps-3 d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-outline-warning btn-sm" id="editSupplierBtn" title="تعديل">
                <i class="ti ti-edit me-1"></i>تعديل
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="deleteSupplierBtn" title="حذف">
                <i class="ti ti-trash me-1"></i>حذف
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
                    <i class="ti ti-plus me-1"></i>إضافة حركة
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="ledger-tab" data-bs-toggle="tab" data-bs-target="#ledger">
                    <i class="ti ti-list me-1"></i>كشف الحساب
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
                                <td class="info-label" width="150">اسم المورد</td>
                                <td class="fw-medium">{{ $supplier->name }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">رقم الهاتف</td>
                                <td dir="ltr" class="text-end">{{ $supplier->phone }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">الحالة</td>
                                <td>
                                    <span class="badge {{ $supplier->status ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $supplier->status ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="info-label">تاريخ الإضافة</td>
                                <td>{{ $supplier->created_at->format('Y-m-d') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="info-label" width="150">الرصيد الافتتاحي</td>
                                <td class="fw-medium" id="openingBalance">{{ number_format($supplier->opening_balance, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">إجمالي الديون</td>
                                <td class="text-danger fw-medium">{{ number_format($stats['total_debit'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">إجمالي السداد</td>
                                <td class="text-success fw-medium">{{ number_format($stats['total_credit'], 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">عدد الحركات</td>
                                <td>{{ $stats['transactions_count'] }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if(!$supplier->transactions()->exists())
                <div class="mt-4 p-3 border rounded">
                    <h6 class="mb-3">تعيين الرصيد الافتتاحي</h6>
                    <form id="openingBalanceForm" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">الرصيد الافتتاحي</label>
                            <input type="number" class="form-control" id="newOpeningBalance" value="{{ $supplier->opening_balance }}" min="0" step="0.01">
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
                                @forelse($supplier->transactions->take(5) as $t)
                                    <tr>
                                        <td>{{ $t->transaction_date->format('Y-m-d') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $t->type === 'debit' ? 'danger' : 'success' }}">
                                                {{ $t->type_arabic }}
                                            </span>
                                        </td>
                                        <td class="{{ $t->type === 'debit' ? 'transaction-debit' : 'transaction-credit' }} fw-bold">
                                            {{ $t->type === 'debit' ? '+' : '-' }}{{ number_format($t->amount, 2) }}
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
                    <div class="col-md-6">
                        <div class="quick-action-btn text-danger" data-bs-toggle="modal" data-bs-target="#debitModal">
                            <i class="ti ti-plus d-block"></i>
                            <div class="fw-bold">إضافة دين</div>
                            <small class="text-muted">زيادة المبلغ المستحق للمورد</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="quick-action-btn text-success" data-bs-toggle="modal" data-bs-target="#creditModal">
                            <i class="ti ti-minus d-block"></i>
                            <div class="fw-bold">سداد دين</div>
                            <small class="text-muted">تسديد مبلغ للمورد</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 border rounded">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="info-label">الرصيد الحالي</div>
                            <div class="fs-4 fw-bold" id="txCurrentBalance">{{ number_format($supplier->current_balance, 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">إجمالي المدين</div>
                            <div class="fs-5 text-danger" id="txTotalDebit">{{ number_format($stats['total_debit'], 2) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">إجمالي المسدد</div>
                            <div class="fs-5 text-success" id="txTotalCredit">{{ number_format($stats['total_credit'], 2) }}</div>
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
                            <option value="debit">مدين</option>
                            <option value="credit">دائن</option>
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
                        <a href="{{ route('suppliers.account.print', $supplier) }}" target="_blank" class="btn btn-outline-dark btn-sm" id="printStatementBtn" title="طباعة كشف الحساب">
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

<div class="modal fade" id="debitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-danger"><i class="ti ti-plus me-1"></i>إضافة دين</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="debitForm">
                <div class="modal-body">
                    <div class="alert alert-danger small">
                        <i class="ti ti-alert-circle me-1"></i>
                        سيتم إضافة هذا المبلغ كدين على المورد (زيادة المستحق)
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="debitAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ العملية</label>
                        <input type="date" class="form-control" id="debitDate" name="transaction_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البيان</label>
                        <input type="text" class="form-control" id="debitDescription" name="description" placeholder="مثال: فاتورة مشتريات">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm" id="debitSubmitBtn">
                        <i class="ti ti-plus me-1"></i>إضافة دين
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="creditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-success"><i class="ti ti-minus me-1"></i>سداد دين</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="creditForm">
                <div class="modal-body">
                    <div class="alert alert-success small">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم خصم هذا المبلغ من رصيد المورد وصرفه من الخزينة المحددة
                    </div>
                    <div class="mb-3">
                        <label class="form-label">المبلغ <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="creditAmount" name="amount" min="0.01" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الخزينة <span class="text-danger">*</span></label>
                        <select class="form-select" id="creditCashbox" name="cashbox_id" required>
                            <option value="">اختر الخزينة...</option>
                            @foreach($cashboxes as $cashbox)
                                <option value="{{ $cashbox->id }}" data-balance="{{ $cashbox->current_balance }}">
                                    {{ $cashbox->name }} ({{ number_format($cashbox->current_balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">يرجى اختيار الخزينة</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ العملية</label>
                        <input type="date" class="form-control" id="creditDate" name="transaction_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البيان</label>
                        <input type="text" class="form-control" id="creditDescription" name="description" placeholder="مثال: سداد نقدي">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm" id="creditSubmitBtn">
                        <i class="ti ti-check me-1"></i>سداد
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-edit me-1"></i>تعديل المورد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSupplierForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المورد <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editSupplierName" name="name" value="{{ $supplier->name }}" required>
                        <div class="invalid-feedback">اسم المورد مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editSupplierPhone" name="phone" value="{{ $supplier->phone }}" required>
                        <div class="invalid-feedback" id="editPhoneFeedback">رقم الهاتف مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="editSupplierStatus" name="status" {{ $supplier->status ? 'checked' : '' }}>
                            <label class="form-check-label" for="editSupplierStatus">نشط</label>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const supplierId = {{ $supplier->id }};
    const csrfToken = '{{ csrf_token() }}';
    let ledgerPage = 1;
    let isPhoneValid = true;
    let phoneCheckTimeout = null;

    const tabStorageKey = `supplier_${supplierId}_active_tab`;
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

    document.getElementById('editSupplierBtn').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('editSupplierModal')).show();
    });

    document.getElementById('editSupplierPhone').addEventListener('input', function() {
        clearTimeout(phoneCheckTimeout);
        const phone = this.value.trim();

        if (!phone) {
            this.classList.remove('is-invalid', 'is-valid');
            isPhoneValid = true;
            return;
        }

        phoneCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("suppliers.check-phone") }}?phone=${encodeURIComponent(phone)}&exclude_id=${supplierId}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('editPhoneFeedback').textContent = 'رقم الهاتف مستخدم بالفعل';
                    isPhoneValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    isPhoneValid = true;
                }
            } catch (error) {
                console.error('Error checking phone:', error);
            }
        }, 300);
    });

    document.getElementById('editSupplierForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('editSupplierName').value.trim();
        const phone = document.getElementById('editSupplierPhone').value.trim();
        const status = document.getElementById('editSupplierStatus').checked;

        if (!name) {
            document.getElementById('editSupplierName').classList.add('is-invalid');
            return;
        }

        if (!phone) {
            document.getElementById('editSupplierPhone').classList.add('is-invalid');
            document.getElementById('editPhoneFeedback').textContent = 'رقم الهاتف مطلوب';
            return;
        }

        if (!isPhoneValid) {
            document.getElementById('editSupplierPhone').focus();
            return;
        }

        const btn = document.getElementById('editSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/suppliers/${supplierId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, phone, status })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editSupplierModal')).hide();
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

    document.getElementById('deleteSupplierBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'تأكيد الحذف',
            html: `هل أنت متأكد من حذف المورد:<br><strong>{{ $supplier->name }}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('_method', 'DELETE');
                    formData.append('_token', csrfToken);

                    const response = await fetch(`/suppliers/${supplierId}`, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: formData
                    });

                    const data = await response.json();
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'تم الحذف',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '{{ route("suppliers.index") }}';
                        });
                    } else {
                        Swal.fire('خطأ', data.message || 'حدث خطأ', 'error');
                    }
                } catch (error) {
                    Swal.fire('خطأ', 'حدث خطأ في حذف المورد', 'error');
                }
            }
        });
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

    function updateBalanceDisplays(newBalance, totalDebit = null, totalCredit = null) {
        const formatted = parseFloat(newBalance).toFixed(2);
        document.getElementById('currentBalance').textContent = formatted;
        document.getElementById('txCurrentBalance').textContent = formatted;

        if (totalDebit !== null) {
            document.getElementById('totalDebit').textContent = parseFloat(totalDebit).toFixed(2);
            document.getElementById('txTotalDebit').textContent = parseFloat(totalDebit).toFixed(2);
        }
        if (totalCredit !== null) {
            document.getElementById('totalCredit').textContent = parseFloat(totalCredit).toFixed(2);
            document.getElementById('txTotalCredit').textContent = parseFloat(totalCredit).toFixed(2);
        }
    }

    document.getElementById('openingBalanceForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const amount = document.getElementById('newOpeningBalance').value;

        try {
            const response = await fetch(`/suppliers/${supplierId}/account/opening-balance`, {
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

    document.getElementById('debitForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('debitSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإضافة...';

        const data = {
            amount: document.getElementById('debitAmount').value,
            transaction_date: document.getElementById('debitDate').value,
            description: document.getElementById('debitDescription').value
        };

        try {
            const response = await fetch(`/suppliers/${supplierId}/account/debit`, {
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
                bootstrap.Modal.getInstance(document.getElementById('debitModal')).hide();
                this.reset();
                document.getElementById('debitDate').value = '{{ date("Y-m-d") }}';
                refreshAccountData();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-plus me-1"></i>إضافة دين';
    });

    document.getElementById('creditForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('creditSubmitBtn');
        const cashboxSelect = document.getElementById('creditCashbox');

        if (!cashboxSelect.value) {
            cashboxSelect.classList.add('is-invalid');
            showToast('يرجى اختيار الخزينة', 'warning');
            return;
        }
        cashboxSelect.classList.remove('is-invalid');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري السداد...';

        const data = {
            amount: document.getElementById('creditAmount').value,
            cashbox_id: cashboxSelect.value,
            transaction_date: document.getElementById('creditDate').value,
            description: document.getElementById('creditDescription').value
        };

        try {
            const response = await fetch(`/suppliers/${supplierId}/account/credit`, {
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
                bootstrap.Modal.getInstance(document.getElementById('creditModal')).hide();
                this.reset();
                document.getElementById('creditDate').value = '{{ date("Y-m-d") }}';
                refreshAccountData();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>سداد';
    });

    async function refreshAccountData() {
        try {
            const response = await fetch(`/suppliers/${supplierId}/account/summary`);
            const result = await response.json();
            if (result.success) {
                updateBalanceDisplays(
                    result.supplier.current_balance,
                    result.stats.total_debit,
                    result.stats.total_credit
                );
                document.getElementById('openingBalance').textContent = parseFloat(result.supplier.opening_balance).toFixed(2);
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
            const response = await fetch(`/suppliers/${supplierId}/account/ledger?${params}`);
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

        if (data.length === 0) {
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
                        <span class="badge bg-${t.type === 'debit' ? 'danger' : 'success'}">
                            ${t.type_arabic}
                        </span>
                    </td>
                    <td class="${t.type === 'debit' ? 'transaction-debit' : 'transaction-credit'} fw-bold">
                        ${t.type === 'debit' ? '+' : '-'}${parseFloat(t.amount).toFixed(2)}
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
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="loadLedgerPage(${meta.current_page - 1}); return false;">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            `;

            for (let i = 1; i <= meta.last_page; i++) {
                if (i === 1 || i === meta.last_page || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
                    html += `
                        <li class="page-item ${i === meta.current_page ? 'active' : ''}">
                            <a class="page-link" href="#" onclick="loadLedgerPage(${i}); return false;">${i}</a>
                        </li>
                    `;
                } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
                    html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                }
            }

            html += `
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
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
        const baseUrl = '{{ route("suppliers.account.print", $supplier) }}';
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
