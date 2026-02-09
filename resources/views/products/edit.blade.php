@extends('layouts.app')

@section('title', 'تعديل صنف')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">الأصناف</a></li>
    <li class="breadcrumb-item active">تعديل صنف</li>
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
        right: 20%;
        left: 20%;
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
                <h5 class="card-title mb-0">تعديل صنف: {{ $product->name }}</h5>
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
                </div>

                <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" id="productForm">
                    @csrf
                    @method('PUT')

                    <div class="wizard-content active" data-step="1" style="display: block;">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="name" class="form-label">اسم الصنف <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $product->name) }}" placeholder="أدخل اسم الصنف" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-8 mb-3">
                                        <label for="barcode" class="form-label">الباركود</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control @error('barcode') is-invalid @enderror" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}" placeholder="أدخل الباركود أو اضغط توليد">
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
                                        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>نشط</option>
                                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                                        </select>
                                        @error('status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <label class="form-label">صورة الصنف</label>
                                <div class="image-upload-zone {{ $product->image ? 'has-image' : '' }}" id="imageUploadZone">
                                    <input type="file" class="d-none" id="image" name="image" accept="image/*">
                                    <div id="uploadPlaceholder" class="{{ $product->image ? 'd-none' : '' }}">
                                        <i class="ti ti-photo-plus image-upload-icon"></i>
                                        <p class="mb-1 text-muted">اسحب الصورة هنا</p>
                                        <p class="mb-0 text-muted small">أو اضغط للاختيار</p>
                                    </div>
                                    <div id="imagePreviewContainer" class="{{ $product->image ? '' : 'd-none' }}">
                                        <div class="image-preview-container">
                                            <img id="imagePreview" class="image-preview" src="{{ $product->image ? Storage::url($product->image) : '' }}" alt="معاينة">
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
                                        <th style="width: 200px;">الوحدة <span class="text-danger">*</span></th>
                                        <th style="width: 120px;">المعامل <span class="text-danger">*</span></th>
                                        <th style="width: 150px;">سعر التكلفة</th>
                                        <th style="width: 150px;">سعر البيع <span class="text-danger">*</span></th>
                                        <th style="width: 80px;">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody id="unitsTableBody">
                                    @foreach($product->productUnits->sortBy(fn($pu) => !$pu->is_base_unit) as $index => $productUnit)
                                        <tr class="unit-row" data-row="{{ $index }}">
                                            <td>
                                                <div class="input-group">
                                                    <select class="form-select unit-select" name="units[{{ $index }}][unit_id]" required>
                                                        @foreach($units as $unit)
                                                            <option value="{{ $unit->id }}" {{ $productUnit->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline-primary add-unit-btn" title="إضافة وحدة جديدة">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control multiplier-input" name="units[{{ $index }}][multiplier]" value="{{ $productUnit->multiplier }}" min="0.0001" step="0.0001" {{ $productUnit->is_base_unit ? 'readonly' : '' }} required>
                                            </td>
                                            <td>
                                                @if($productUnit->is_base_unit)
                                                    <input type="number" class="form-control cost-input base-cost" name="units[{{ $index }}][cost_price]" value="{{ $productUnit->cost_price }}" min="0" step="0.01" required>
                                                @else
                                                    <input type="text" class="form-control calculated-cost" value="{{ number_format($productUnit->calculated_cost, 2) }}" readonly disabled>
                                                @endif
                                            </td>
                                            <td>
                                                <input type="number" class="form-control sell-input" name="units[{{ $index }}][sell_price]" value="{{ $productUnit->sell_price }}" min="0" step="0.01" required>
                                            </td>
                                            <td class="text-center">
                                                @if($productUnit->is_base_unit)
                                                    <span class="badge bg-primary">أساسية</span>
                                                @else
                                                    <button type="button" class="btn btn-danger btn-sm remove-row">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-success mt-2" id="addUnitRow">
                            <i class="ti ti-plus me-1"></i>
                            إضافة وحدة أخرى
                        </button>
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
                                حفظ التعديلات
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
    const totalSteps = 2;
    let rowIndex = {{ $product->productUnits->count() }};
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
            if (stepNum == currentStep) {
                step.classList.add('active');
            } else if (stepNum < currentStep) {
                step.classList.add('completed');
            }
        });

        contents.forEach(content => {
            content.classList.remove('active');
            if (parseInt(content.dataset.step) == currentStep) {
                content.classList.add('active');
            }
        });

        prevBtn.style.display = currentStep == 1 ? 'none' : 'inline-flex';
        nextBtn.style.display = currentStep == totalSteps ? 'none' : 'inline-flex';
        submitBtn.style.display = currentStep == totalSteps ? 'inline-flex' : 'none';
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
            if (stepNum < currentStep || (stepNum == currentStep + 1 && validateStep(currentStep))) {
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
        if (e.target.id != 'removeImage' && !e.target.closest('#removeImage')) {
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
                <input type="number" class="form-control multiplier-input" name="units[${rowIndex}][multiplier]" value="1" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <input type="text" class="form-control calculated-cost" value="${baseCost.toFixed(2)}" readonly disabled>
            </td>
            <td>
                <input type="number" class="form-control sell-input" name="units[${rowIndex}][sell_price]" value="0" min="0" step="0.01" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-row">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;
        unitsTableBody.appendChild(row);
        rowIndex++;

        row.querySelector('.multiplier-input').addEventListener('input', function() {
            updateCalculatedCost(row);
        });
    }

    function updateCalculatedCost(row) {
        const multiplier = parseFloat(row.querySelector('.multiplier-input').value) || 0;
        const baseCost = parseFloat(baseCostInput.value) || 0;
        const calculatedCostInput = row.querySelector('.calculated-cost');
        if (calculatedCostInput) {
            calculatedCostInput.value = (baseCost * multiplier).toFixed(2);
        }
    }

    function updateAllCalculatedCosts() {
        document.querySelectorAll('.unit-row').forEach((row, index) => {
            if (!row.querySelector('.base-cost')) {
                updateCalculatedCost(row);
            }
        });
    }

    baseCostInput.addEventListener('input', updateAllCalculatedCosts);

    document.querySelectorAll('.multiplier-input').forEach(input => {
        if (!input.hasAttribute('readonly')) {
            input.addEventListener('input', function() {
                updateCalculatedCost(input.closest('.unit-row'));
            });
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
