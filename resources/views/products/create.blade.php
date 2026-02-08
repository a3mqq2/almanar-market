@extends('layouts.app')

@section('title', 'إضافة صنف جديد')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">الأصناف</a></li>
    <li class="breadcrumb-item active">إضافة صنف</li>
@endsection

@push('styles')
<style>
    .wizard-steps {
        display: flex;
        justify-content: center;
        margin-bottom: 2rem;
        position: relative;
    }
    .wizard-steps::before {
        content: '';
        position: absolute;
        top: 24px;
        right: 15%;
        left: 15%;
        height: 2px;
        background: #e9ecef;
        z-index: 0;
    }
    .wizard-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
        max-width: 200px;
        cursor: pointer;
    }
    .wizard-step-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #6c757d;
        transition: all 0.3s ease;
        border: 3px solid #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .wizard-step.active .wizard-step-icon {
        background: var(--bs-primary);
        color: #fff;
    }
    .wizard-step.completed .wizard-step-icon {
        background: #198754;
        color: #fff;
    }
    .wizard-step-label {
        margin-top: 0.5rem;
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
    .wizard-step.active .wizard-step-label {
        color: var(--bs-primary);
        font-weight: 600;
    }
    .wizard-step.completed .wizard-step-label {
        color: #198754;
    }
    .wizard-content {
        display: none;
    }
    .wizard-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .image-upload-zone {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
        position: relative;
        overflow: hidden;
    }
    .image-upload-zone:hover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.05);
    }
    .image-upload-zone.dragover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.1);
        transform: scale(1.02);
    }
    .image-upload-zone.has-image {
        padding: 1rem;
    }
    .image-upload-icon {
        font-size: 3rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }
    .image-upload-zone:hover .image-upload-icon {
        color: var(--bs-primary);
    }
    .image-preview-container {
        position: relative;
        display: inline-block;
    }
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .image-remove-btn {
        position: absolute;
        top: -8px;
        left: -8px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #dc3545;
        color: #fff;
        border: 2px solid #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        transition: transform 0.2s;
    }
    .image-remove-btn:hover {
        transform: scale(1.1);
    }
    .wizard-nav {
        display: flex;
        justify-content: space-between;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">إضافة صنف جديد</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="wizard-step-icon">
                            <i class="ti ti-info-circle"></i>
                        </div>
                        <span class="wizard-step-label">البيانات الأساسية</span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="wizard-step-icon">
                            <i class="ti ti-ruler"></i>
                        </div>
                        <span class="wizard-step-label">الوحدات والأسعار</span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="wizard-step-icon">
                            <i class="ti ti-package"></i>
                        </div>
                        <span class="wizard-step-label">المخزون المبدئي</span>
                    </div>
                </div>

                <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" id="productForm">
                    @csrf

                    <div class="wizard-content active" data-step="1" style="display: block;">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="name" class="form-label">اسم الصنف <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="أدخل اسم الصنف" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-8 mb-3">
                                        <label for="barcode" class="form-label">الباركود</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode') }}" placeholder="أدخل الباركود أو اضغط توليد">
                                            <button type="button" class="btn btn-outline-primary" id="generateBarcode">
                                                <i class="ti ti-refresh me-1"></i>
                                                توليد تلقائي
                                            </button>
                                        </div>
                                        @error('barcode')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="status" class="form-label">حالة الصنف <span class="text-danger">*</span></label>
                                        <select class="form-select @error('status') is-invalid @enderror" id="" name="status" required>
                                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>نشط</option>
                                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">صورة الصنف</label>
                                <div class="image-upload-zone" id="imageUploadZone">
                                    <input type="file" class="d-none" id="image" name="image" accept="image/*">
                                    <div id="uploadPlaceholder">
                                        <i class="ti ti-photo-plus image-upload-icon"></i>
                                        <p class="mb-1 text-muted">اسحب الصورة هنا</p>
                                        <p class="mb-0 text-muted small">أو اضغط للاختيار</p>
                                    </div>
                                    <div id="imagePreviewContainer" class="d-none">
                                        <div class="image-preview-container">
                                            <img id="imagePreview" class="image-preview" src="" alt="معاينة">
                                            <button type="button" class="image-remove-btn" id="removeImage">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @error('image')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="wizard-content" data-step="2" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="unitsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 180px;">الوحدة <span class="text-danger">*</span></th>
                                        <th style="width: 100px;">المعامل <span class="text-danger">*</span></th>
                                        <th style="width: 120px;">سعر التكلفة</th>
                                        <th style="width: 100px;">هامش الربح %</th>
                                        <th style="width: 120px;">سعر البيع</th>
                                        <th style="width: 80px;">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody id="unitsTableBody">
                                    <tr class="unit-row" data-row="0">
                                        <td>
                                            <div class="input-group">
                                                <select class="form-select unit-select" name="units[0][unit_id]" required>
                                                    @foreach($units as $unit)
                                                        <option value="{{ $unit->id }}" {{ $unit->is_default ? 'selected' : '' }}>{{ $unit->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-outline-primary add-unit-btn" title="إضافة وحدة جديدة">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control multiplier-input" name="units[0][multiplier]" value="1.0000" min="0.0001" step="0.0001" readonly required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control cost-input base-cost" name="units[0][cost_price]" value="0.00" min="0" step="0.01">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control margin-input" value="0.00" min="0" step="0.01">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control sell-price-display" name="units[0][sell_price]" value="0.00" min="0" step="0.01" readonly>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">أساسية</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-success mt-2" id="addUnitRow">
                            <i class="ti ti-plus me-1"></i>
                            إضافة وحدة أخرى
                        </button>
                    </div>

                    <div class="wizard-content" data-step="3" style="display: none;">
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="ti ti-package me-1"></i>
                                            بيانات المخزون الافتتاحي
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="opening_quantity" class="form-label">الكمية الافتتاحية</label>
                                                <input type="number" class="form-control @error('opening_quantity') is-invalid @enderror" id="opening_quantity" name="opening_quantity" value="{{ old('opening_quantity', 0) }}" min="0" step="0.0001">
                                                @error('opening_quantity')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="expiry_date" class="form-label">تاريخ الصلاحية</label>
                                                <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}">
                                                @error('expiry_date')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="inventory_notes" class="form-label">ملاحظات</label>
                                                <textarea class="form-control @error('inventory_notes') is-invalid @enderror" id="inventory_notes" name="inventory_notes" rows="1">{{ old('inventory_notes') }}</textarea>
                                                @error('inventory_notes')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <div>
                            <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                                <i class="ti ti-arrow-right me-1"></i>
                                السابق
                            </button>
                        </div>
                        <div>
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="ti ti-x me-1"></i>
                                إلغاء
                            </a>
                            <button type="button" class="btn btn-primary" id="nextBtn">
                                التالي
                                <i class="ti ti-arrow-left ms-1"></i>
                            </button>
                            <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">
                                <i class="ti ti-device-floppy me-1"></i>
                                حفظ الصنف
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة وحدة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="newUnitName" class="form-label">اسم الوحدة <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newUnitName" required>
                </div>
                <div class="mb-3">
                    <label for="newUnitSymbol" class="form-label">الرمز</label>
                    <input type="text" class="form-control" id="newUnitSymbol">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveNewUnit">حفظ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentStep = 1;
    const totalSteps = 3;
    let rowIndex = 1;
    const unitsTableBody = document.getElementById('unitsTableBody');
    const baseCostInput = document.querySelector('.base-cost');

    const steps = document.querySelectorAll('.wizard-step');
    const contents = document.querySelectorAll('.wizard-content');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');

    function updateWizard() {
        steps.forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('active', 'completed');
            if (stepNum === currentStep) {
                step.classList.add('active');
            } else if (stepNum < currentStep) {
                step.classList.add('completed');
            }
        });

        contents.forEach(content => {
            content.classList.remove('active');
            if (parseInt(content.dataset.step) === currentStep) {
                content.classList.add('active');
            }
        });

        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
        submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }

    function validateStep(step) {
        const content = document.querySelector(`.wizard-content[data-step="${step}"]`);
        const requiredInputs = content.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        return isValid;
    }

    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep) && currentStep < totalSteps) {
            currentStep++;
            updateWizard();
        }
    });

    prevBtn.addEventListener('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    steps.forEach(step => {
        step.addEventListener('click', function() {
            const stepNum = parseInt(this.dataset.step);
            if (stepNum < currentStep || (stepNum === currentStep + 1 && validateStep(currentStep))) {
                currentStep = stepNum;
                updateWizard();
            }
        });
    });

    document.getElementById('generateBarcode').addEventListener('click', function() {
        fetch('{{ route("products.generate-barcode") }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('barcode').value = data.barcode;
            });
    });

    const imageUploadZone = document.getElementById('imageUploadZone');
    const imageInput = document.getElementById('image');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const removeImageBtn = document.getElementById('removeImage');

    imageUploadZone.addEventListener('click', function(e) {
        if (e.target.id !== 'removeImage' && !e.target.closest('#removeImage')) {
            imageInput.click();
        }
    });

    imageUploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });

    imageUploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });

    imageUploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            imageInput.files = files;
            showImagePreview(files[0]);
        }
    });

    imageInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            showImagePreview(e.target.files[0]);
        }
    });

    function showImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            uploadPlaceholder.classList.add('d-none');
            imagePreviewContainer.classList.remove('d-none');
            imageUploadZone.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    }

    removeImageBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        imageInput.value = '';
        imagePreview.src = '';
        uploadPlaceholder.classList.remove('d-none');
        imagePreviewContainer.classList.add('d-none');
        imageUploadZone.classList.remove('has-image');
    });

    document.getElementById('addUnitRow').addEventListener('click', addUnitRow);

    function calculateSellPrice(row) {
        const costInput = row.querySelector('.base-cost') || row.querySelector('.calculated-cost');
        const marginInput = row.querySelector('.margin-input');
        const sellPriceInput = row.querySelector('.sell-price-display');

        if (!costInput || !marginInput || !sellPriceInput) return;

        const cost = parseFloat(costInput.value) || 0;
        const margin = parseFloat(marginInput.value) || 0;
        const sellPrice = cost * (1 + margin / 100);
        sellPriceInput.value = sellPrice.toFixed(2);
    }

    function updateCalculatedCostsAndPrices() {
        const baseCost = parseFloat(baseCostInput.value) || 0;

        document.querySelectorAll('.unit-row').forEach((row, index) => {
            if (index === 0) {
                calculateSellPrice(row);
                return;
            }

            const multiplierInput = row.querySelector('.multiplier-input');
            const calculatedCostInput = row.querySelector('.calculated-cost');
            const multiplier = parseFloat(multiplierInput?.value) || 1;

            if (calculatedCostInput) {
                calculatedCostInput.value = (baseCost * multiplier).toFixed(2);
            }

            calculateSellPrice(row);
        });
    }

    function addUnitRow() {
        const baseCost = parseFloat(baseCostInput.value) || 0;
        const unitOptions = Array.from(document.querySelectorAll('.unit-select')[0].options)
            .map(opt => `<option value="${opt.value}">${opt.text}</option>`)
            .join('');

        const row = document.createElement('tr');
        row.className = 'unit-row';
        row.dataset.row = rowIndex;
        row.innerHTML = `
            <td>
                <div class="input-group">
                    <select class="form-select unit-select" name="units[${rowIndex}][unit_id]" required>
                        ${unitOptions}
                    </select>
                    <button type="button" class="btn btn-outline-primary add-unit-btn" title="إضافة وحدة جديدة">
                        <i class="ti ti-plus"></i>
                    </button>
                </div>
            </td>
            <td>
                <input type="number" class="form-control multiplier-input" name="units[${rowIndex}][multiplier]" value="1.0000" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <input type="text" class="form-control calculated-cost" value="${baseCost.toFixed(2)}" readonly disabled>
            </td>
            <td>
                <input type="number" class="form-control margin-input" value="0.00" min="0" step="0.01">
            </td>
            <td>
                <input type="number" class="form-control sell-price-display" name="units[${rowIndex}][sell_price]" value="0.00" min="0" step="0.01" readonly>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-row">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;
        unitsTableBody.appendChild(row);
        rowIndex++;
        updateCalculatedCostsAndPrices();
    }

    baseCostInput.addEventListener('input', updateCalculatedCostsAndPrices);

    unitsTableBody.addEventListener('input', function(e) {
        if (e.target.classList.contains('margin-input')) {
            calculateSellPrice(e.target.closest('.unit-row'));
        } else if (e.target.classList.contains('multiplier-input')) {
            updateCalculatedCostsAndPrices();
        }
    });

    unitsTableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            e.target.closest('.unit-row').remove();
        }
        if (e.target.closest('.add-unit-btn')) {
            const modal = new bootstrap.Modal(document.getElementById('addUnitModal'));
            modal.show();
        }
    });

    document.getElementById('saveNewUnit').addEventListener('click', function() {
        const name = document.getElementById('newUnitName').value.trim();
        const symbol = document.getElementById('newUnitSymbol').value.trim();

        if (!name) {
            alert('يرجى إدخال اسم الوحدة');
            return;
        }

        fetch('{{ route("units.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: name, symbol: symbol })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.unit-select').forEach(select => {
                    const option = document.createElement('option');
                    option.value = data.unit.id;
                    option.text = data.unit.name;
                    select.appendChild(option);
                });

                document.getElementById('newUnitName').value = '';
                document.getElementById('newUnitSymbol').value = '';
                bootstrap.Modal.getInstance(document.getElementById('addUnitModal')).hide();
            } else {
                alert(data.message || 'حدث خطأ');
            }
        })
        .catch(error => {
            alert('حدث خطأ في الاتصال');
        });
    });
});
</script>
@endpush
