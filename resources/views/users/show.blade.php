@extends('layouts.app')

@section('title', $user->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">المستخدمين</a></li>
    <li class="breadcrumb-item active">{{ $user->name }}</li>
@endsection

@push('styles')
<style>
    .info-card {
        background: var(--bs-card-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 1.5rem;
    }
    .info-label {
        color: var(--bs-secondary-color);
        font-size: 0.85rem;
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 1rem;
        font-weight: 500;
    }
    .stat-box {
        text-align: center;
        padding: 1rem;
        background: var(--bs-tertiary-bg);
        border-radius: 8px;
    }
    .stat-box .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
    }
    .stat-box .stat-label {
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
    }
    .activity-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    .activity-item:last-child {
        border-bottom: none;
    }
    .cashbox-chip {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        background: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 20px;
        margin: 0.25rem;
        font-size: 0.85rem;
    }
    .cashbox-chip.assigned {
        background: var(--bs-success-bg-subtle);
        border-color: var(--bs-success);
        color: var(--bs-success);
    }
</style>
@endpush

@section('content')
<div class="toast-container position-fixed top-0 start-0 p-3" style="z-index: 9999;" id="toastContainer"></div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="info-card mb-4">
            <div class="text-center mb-4">
                <div class="avatar avatar-xl bg-{{ $user->role == 'manager' ? 'info' : 'warning' }}-subtle text-{{ $user->role == 'manager' ? 'info' : 'warning' }} rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                    <i class="ti ti-user"></i>
                </div>
                <h4 class="mt-3 mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-2"><code>{{ $user->username }}</code></p>
                <span class="badge bg-{{ $user->role == 'manager' ? 'info' : 'warning' }}">{{ $user->role_arabic }}</span>
                <span class="badge bg-{{ $user->status ? 'success' : 'secondary' }}">{{ $user->status_arabic }}</span>
            </div>

            <div class="mb-3">
                <div class="info-label">البريد الإلكتروني</div>
                <div class="info-value">{{ $user->email ?? '-' }}</div>
            </div>
            <div class="mb-3">
                <div class="info-label">تاريخ الإنشاء</div>
                <div class="info-value">{{ $user->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <div class="mb-3">
                <div class="info-label">آخر تسجيل دخول</div>
                <div class="info-value">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'لم يسجل الدخول بعد' }}</div>
            </div>
            @if($user->last_login_ip)
            <div class="mb-3">
                <div class="info-label">آخر IP</div>
                <div class="info-value"><code>{{ $user->last_login_ip }}</code></div>
            </div>
            @endif
        </div>

        <div class="row g-2 mb-4">
            <div class="col-4">
                <div class="stat-box">
                    <div class="stat-value text-primary">{{ $stats['total_shifts'] }}</div>
                    <div class="stat-label">وردية</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-box">
                    <div class="stat-value text-success">{{ number_format($stats['total_sales'], 0) }}</div>
                    <div class="stat-label">المبيعات</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-box">
                    <div class="stat-value text-info">{{ $stats['login_count'] }}</div>
                    <div class="stat-label">تسجيل دخول</div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            @if($user->id != auth()->id())
                <button type="button" class="btn btn-{{ $user->status ? 'warning' : 'success' }}" onclick="toggleStatus()">
                    <i class="ti ti-{{ $user->status ? 'lock' : 'lock-open' }} me-1"></i>
                    {{ $user->status ? 'إيقاف الحساب' : 'تفعيل الحساب' }}
                </button>
            @endif
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#passwordModal">
                <i class="ti ti-key me-1"></i>تغيير كلمة المرور
            </button>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-edit me-1"></i>تعديل البيانات</h5>
            </div>
            <div class="card-body">
                <form id="editUserForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">الاسم <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editName" value="{{ $user->name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editUsername" value="{{ $user->username }}" required>
                            <div class="invalid-feedback" id="editUsernameFeedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="editEmail" value="{{ $user->email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الدور</label>
                            <select class="form-select" id="editRole">
                                <option value="cashier" {{ $user->role == 'cashier' ? 'selected' : '' }}>كاشير</option>
                                <option value="manager" {{ $user->role == 'manager' ? 'selected' : '' }}>مدير</option>
                                <option value="price_checker" {{ $user->role == 'price_checker' ? 'selected' : '' }}>جهاز الأسعار</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">
                            <i class="ti ti-check me-1"></i>حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4" id="cashboxesCard" style="{{ in_array($user->role, ['manager', 'price_checker']) ? 'display:none;' : '' }}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-building-bank me-1"></i>الخزائن المسموحة</h5>
                <button type="button" class="btn btn-primary btn-sm" id="saveCashboxesBtn">
                    <i class="ti ti-check me-1"></i>حفظ
                </button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">حدد الخزائن التي يمكن للمستخدم الوصول إليها</p>
                <div class="d-flex flex-wrap">
                    @php
                        $userCashboxIds = $user->cashboxes->pluck('id')->toArray();
                    @endphp
                    @foreach($allCashboxes as $cashbox)
                        <label class="cashbox-chip {{ in_array($cashbox->id, $userCashboxIds) ? 'assigned' : '' }}" style="cursor: pointer;">
                            <input type="checkbox" class="d-none cashbox-assign-checkbox" value="{{ $cashbox->id }}" {{ in_array($cashbox->id, $userCashboxIds) ? 'checked' : '' }}>
                            <i class="ti ti-{{ in_array($cashbox->id, $userCashboxIds) ? 'check' : 'plus' }} me-1"></i>
                            {{ $cashbox->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>سجل النشاط</h5>
            </div>
            <div class="card-body">
                @if($recentActivity->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="ti ti-mood-empty d-block mb-2" style="font-size: 2rem;"></i>
                        لا يوجد نشاط مسجل
                    </div>
                @else
                    @foreach($recentActivity as $activity)
                        <div class="activity-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-secondary me-1">{{ $activity->action_arabic }}</span>
                                    <span class="text-muted small">{{ $activity->description }}</span>
                                </div>
                                <small class="text-muted">{{ $activity->created_at->format('Y-m-d H:i') }}</small>
                            </div>
                            @if($activity->ip_address)
                                <small class="text-muted"><code>{{ $activity->ip_address }}</code></small>
                            @endif
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-key me-1"></i>تغيير كلمة المرور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="passwordForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="newPassword" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="savePasswordBtn">
                        <i class="ti ti-check me-1"></i>حفظ
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const userId = {{ $user->id }};
    let usernameCheckTimeout;
    let isUsernameValid = true;

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

    document.querySelectorAll('.cashbox-assign-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const label = this.closest('.cashbox-chip');
            const icon = label.querySelector('i');
            if (this.checked) {
                label.classList.add('assigned');
                icon.className = 'ti ti-check me-1';
            } else {
                label.classList.remove('assigned');
                icon.className = 'ti ti-plus me-1';
            }
        });
    });

    document.getElementById('editRole').addEventListener('change', function() {
        document.getElementById('cashboxesCard').style.display = (this.value == 'manager' || this.value == 'price_checker') ? 'none' : 'block';
    });

    document.getElementById('editUsername').addEventListener('input', function() {
        clearTimeout(usernameCheckTimeout);
        const username = this.value.trim();

        if (!username) {
            this.classList.remove('is-valid', 'is-invalid');
            isUsernameValid = false;
            return;
        }

        usernameCheckTimeout = setTimeout(async () => {
            try {
                const params = new URLSearchParams({ username, exclude_id: userId });
                const response = await fetch(`{{ route('users.check-username') }}?${params}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.add('is-invalid');
                    this.classList.remove('is-valid');
                    document.getElementById('editUsernameFeedback').textContent = 'اسم المستخدم مستخدم بالفعل';
                    isUsernameValid = false;
                } else {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                    isUsernameValid = true;
                }
            } catch (e) {
                console.error(e);
            }
        }, 300);
    });

    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!isUsernameValid) {
            showToast('اسم المستخدم غير صالح', 'warning');
            return;
        }

        const btn = document.getElementById('saveUserBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/users/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: document.getElementById('editName').value,
                    username: document.getElementById('editUsername').value,
                    email: document.getElementById('editEmail').value || null,
                    role: document.getElementById('editRole').value,
                    status: {{ $user->status ? 'true' : 'false' }}
                })
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ التغييرات';
    });

    document.getElementById('saveCashboxesBtn').addEventListener('click', async function() {
        const cashboxIds = [];
        document.querySelectorAll('.cashbox-assign-checkbox:checked').forEach(cb => {
            cashboxIds.push(parseInt(cb.value));
        });

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/users/${userId}/cashboxes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ cashbox_ids: cashboxIds })
            });

            const result = await response.json();

            if (result.success) {
                showToast(result.message, 'success');
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }

        this.disabled = false;
        this.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });

    window.toggleStatus = async function() {
        const newStatus = !{{ $user->status ? 'true' : 'false' }};
        const action = newStatus ? 'تفعيل' : 'إيقاف';

        const result = await Swal.fire({
            title: `${action} الحساب؟`,
            text: `هل أنت متأكد من ${action} حساب هذا المستخدم؟`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم',
            cancelButtonText: 'إلغاء'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/users/${userId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }
    };

    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password != confirmPassword) {
            showToast('كلمات المرور غير متطابقة', 'warning');
            return;
        }

        const btn = document.getElementById('savePasswordBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/users/${userId}/reset-password`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    password: password,
                    password_confirmation: confirmPassword
                })
            });

            const result = await response.json();

            if (result.success) {
                bootstrap.Modal.getInstance(document.getElementById('passwordModal')).hide();
                showToast(result.message, 'success');
                document.getElementById('passwordForm').reset();
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ';
    });
});
</script>
@endpush
