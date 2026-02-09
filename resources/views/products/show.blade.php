@extends('layouts.app')

@section('title', $product->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
    <li class="breadcrumb-item"><a href="{{ route('products.index') }}">الأصناف</a></li>
    <li class="breadcrumb-item active">{{ $product->name }}</li>
@endsection

@push('styles')
<style>
    :root {
        --header-bg: var(--bs-tertiary-bg);
        --header-border: var(--bs-border-color);
        --placeholder-bg: var(--bs-secondary-bg);
        --placeholder-color: var(--bs-secondary-color);
        --label-color: var(--bs-secondary-color);
        --tab-color: var(--bs-secondary-color);
        --tab-active: var(--bs-primary);
    }

    .product-header {
        background: #fff;
        border: 1px solid var(--header-border);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    [data-bs-theme="dark"] .product-header {
        background: var(--bs-tertiary-bg);
    }
    .product-image {
        width: 70px;
        height: 70px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid var(--header-border);
    }
    .product-image-placeholder {
        width: 70px;
        height: 70px;
        border-radius: 8px;
        background: var(--placeholder-bg);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--placeholder-color);
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
    .info-label {
        color: var(--label-color);
        font-size: 0.875rem;
    }
    .movement-in { color: var(--bs-success); }
    .movement-out { color: var(--bs-danger); }
    .table thead {
        background: var(--bs-tertiary-bg);
    }
    .table tfoot {
        background: var(--bs-tertiary-bg);
    }
    .toast-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 9999;
    }
    .barcode-label-preview {
        background: #fff !important;
    }
    #printArea {
        position: absolute;
        left: -9999px;
    }
</style>
@endpush

@section('content')
<div class="toast-container" id="toastContainer"></div>

<!-- Product Header -->
<div class="product-header">
    <div class="d-flex align-items-center gap-3">
        @if($product->image)
            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="product-image">
        @else
            <div class="product-image-placeholder">
                <i class="ti ti-package fs-3"></i>
            </div>
        @endif
        <div class="flex-grow-1">
            <h5 class="mb-1">{{ $product->name }}</h5>
            <div class="d-flex gap-2 align-items-center text-muted small">
                @if($product->barcode)
                    <span><i class="ti ti-barcode me-1"></i>{{ $product->barcode }}</span>
                    <span class="text-muted">|</span>
                @endif
                <span class="badge {{ $product->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                    {{ $product->status == 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
        </div>
        <div class="text-end">
            <div class="info-label">المخزون</div>
            <div class="fs-4 fw-bold" id="totalStock">{{ number_format($product->total_stock, 2) }}</div>
            <div class="info-label">{{ $product->baseUnit?->unit?->name ?? 'وحدة' }}</div>
        </div>
        <div class="border-start ps-3 text-end">
            <div class="info-label">سعر البيع</div>
            <div class="fs-5 fw-semibold text-success">{{ number_format($product->baseUnit?->sell_price ?? 0, 2) }}</div>
        </div>
        <div class="border-start ps-3 d-flex gap-2 align-items-center">
            @if($product->barcode)
            <button type="button" class="btn btn-outline-secondary btn-sm" id="printBarcodeBtn" title="طباعة باركود">
                <i class="ti ti-barcode me-1"></i>طباعة باركود
            </button>
            @endif
            <button type="button" class="btn btn-outline-warning btn-sm" id="editProductBtn" title="تعديل">
                <i class="ti ti-edit me-1"></i>تعديل
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" id="deleteProductBtn" title="حذف">
                <i class="ti ti-trash me-1"></i>حذف
            </button>
        </div>
    </div>
</div>

<!-- Tabs Card -->
<div class="card">
    <div class="card-header border-bottom-0 pb-0">
        <ul class="nav nav-tabs" id="productTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview">
                    <i class="ti ti-info-circle me-1"></i>المعلومات
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="units-tab" data-bs-toggle="tab" data-bs-target="#units">
                    <i class="ti ti-ruler me-1"></i>الوحدات والأسعار
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="stock-tab" data-bs-toggle="tab" data-bs-target="#stock">
                    <i class="ti ti-packages me-1"></i>المخزون
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history">
                    <i class="ti ti-history me-1"></i>سجل الحركات
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="barcodes-tab" data-bs-toggle="tab" data-bs-target="#barcodes">
                    <i class="ti ti-barcode me-1"></i>الباركودات
                    @if($product->barcodes->count() > 0)
                        <span class="badge bg-primary ms-1">{{ $product->barcodes->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="info-label" width="140">اسم الصنف</td>
                                <td class="fw-medium">{{ $product->name }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">الباركود</td>
                                <td>{{ $product->barcode ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">الحالة</td>
                                <td>
                                    <span class="badge {{ $product->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $product->status == 'active' ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="info-label">تاريخ الإنشاء</td>
                                <td>{{ $product->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">آخر تحديث</td>
                                <td>{{ $product->updated_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="info-label" width="140">إجمالي المخزون</td>
                                <td class="fw-medium" id="overviewStock">{{ number_format($product->total_stock, 2) }} {{ $product->baseUnit?->unit?->name }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">سعر البيع</td>
                                <td class="text-success fw-medium">{{ number_format($product->baseUnit?->sell_price ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">سعر التكلفة</td>
                                <td>{{ number_format($product->baseUnit?->cost_price ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="info-label">عدد الدفعات</td>
                                <td>{{ $product->inventoryBatches->where('quantity', '>', 0)->count() }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Units Tab -->
            <div class="tab-pane fade" id="units">
                <form id="unitsForm">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="unitsTable">
                            <thead >
                                <tr>
                                    <th>الوحدة</th>
                                    <th width="100">المعامل</th>
                                    <th width="120">سعر التكلفة</th>
                                    <th width="120">سعر البيع</th>
                                    <th width="100">هامش الربح %</th>
                                    <th width="70"></th>
                                </tr>
                            </thead>
                            <tbody id="unitsTableBody">
                                @foreach($product->productUnits->sortBy(fn($pu) => !$pu->is_base_unit)->values() as $index => $productUnit)
                                    @php
                                        $costPrice = $productUnit->is_base_unit ? ($productUnit->cost_price ?? 0) : $productUnit->calculated_cost;
                                        $sellPrice = $productUnit->sell_price ?? 0;
                                        $margin = $costPrice > 0 ? round((($sellPrice - $costPrice) / $costPrice) * 100, 2) : 0;
                                    @endphp
                                    <tr class="unit-row" data-row="{{ $index }}">
                                        <td>
                                            <select class="form-select form-select-sm unit-select" name="units[{{ $index }}][unit_id]" required>
                                                @foreach($units as $unit)
                                                    <option value="{{ $unit->id }}" {{ $productUnit->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm multiplier-input" name="units[{{ $index }}][multiplier]" value="{{ number_format((float)$productUnit->multiplier, 4, '.', '') }}" min="0.0001" step="0.0001" {{ $productUnit->is_base_unit ? 'readonly' : '' }} required>
                                        </td>
                                        <td>
                                            @if($productUnit->is_base_unit)
                                                <input type="number" class="form-control form-control-sm base-cost" name="units[{{ $index }}][cost_price]" value="{{ number_format((float)($productUnit->cost_price ?? 0), 2, '.', '') }}" min="0" step="0.01">
                                            @else
                                                <input type="text" class="form-control form-control-sm calculated-cost" value="{{ number_format($productUnit->calculated_cost, 2, '.', '') }}" readonly disabled>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm sell-price-input" name="units[{{ $index }}][sell_price]" value="{{ number_format((float)($productUnit->sell_price ?? 0), 2, '.', '') }}" min="0" step="0.01">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm margin-display" value="{{ number_format($margin, 2, '.', '') }}%" readonly disabled>
                                        </td>
                                        <td class="text-center">
                                            @if($productUnit->is_base_unit)
                                                <span class="badge bg-primary">أساسية</span>
                                            @else
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-unit-row">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between mt-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" id="addUnitRowBtn">
                                <i class="ti ti-plus me-1"></i>إضافة وحدة
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createUnitModal">
                                <i class="ti ti-tag me-1"></i>وحدة جديدة
                            </button>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ti ti-device-floppy me-1"></i>حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Stock Tab (Merged) -->
            <div class="tab-pane fade" id="stock">
                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addStockModal">
                            <i class="ti ti-plus me-1"></i>إضافة مخزون
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#removeStockModal">
                            <i class="ti ti-minus me-1"></i>خصم مخزون
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adjustStockModal">
                            <i class="ti ti-adjustments me-1"></i>تعديل يدوي
                        </button>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small">إجمالي المخزون:</span>
                        <span class="fw-bold fs-5 ms-1" id="stockTabTotal">{{ number_format($product->total_stock, 2) }}</span>
                    </div>
                </div>

                <!-- Batches Table -->
                <div class="table-responsive" id="batchesContainer">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead >
                            <tr>
                                <th>رقم الدفعة</th>
                                <th>النوع</th>
                                <th>الكمية</th>
                                <th>سعر التكلفة</th>
                                <th>إجمالي التكلفة</th>
                                <th>تاريخ الصلاحية</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th>ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->inventoryBatches->where('quantity', '>', 0)->sortBy('expiry_date') as $batch)
                                @php
                                    $status = ['label' => 'جيد', 'class' => 'success'];
                                    if ($batch->expiry_date) {
                                        $days = now()->diffInDays($batch->expiry_date, false);
                                        if ($days < 0) $status = ['label' => 'منتهي', 'class' => 'danger'];
                                        elseif ($days <= 30) $status = ['label' => 'قريب الانتهاء', 'class' => 'warning'];
                                        elseif ($days <= 90) $status = ['label' => 'يحتاج متابعة', 'class' => 'info'];
                                    }
                                    $typeLabels = [
                                        'opening_balance' => 'رصيد افتتاحي',
                                        'purchase' => 'شراء',
                                        'adjustment' => 'تعديل',
                                        'return' => 'مرتجع',
                                    ];
                                @endphp
                                <tr>
                                    <td><code>{{ $batch->batch_number }}</code></td>
                                    <td>{{ $typeLabels[$batch->type] ?? $batch->type }}</td>
                                    <td class="fw-bold">{{ number_format($batch->quantity, 2) }}</td>
                                    <td>{{ number_format($batch->cost_price, 2) }}</td>
                                    <td>{{ number_format($batch->quantity * $batch->cost_price, 2) }}</td>
                                    <td>
                                        @if($batch->expiry_date)
                                            {{ $batch->expiry_date->format('Y-m-d') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-{{ $status['class'] }}">{{ $status['label'] }}</span></td>
                                    <td>{{ $batch->created_at->format('Y-m-d') }}</td>
                                    <td>{{ $batch->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ti ti-package-off fs-1 d-block mb-2"></i>
                                        لا توجد دفعات متوفرة
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($product->inventoryBatches->where('quantity', '>', 0)->count() > 0)
                        <tfoot >
                            <tr>
                                <th colspan="2">الإجمالي</th>
                                <th>{{ number_format($product->total_stock, 2) }}</th>
                                <th>-</th>
                                <th>{{ number_format($product->inventoryBatches->where('quantity', '>', 0)->sum(fn($b) => $b->quantity * $b->cost_price), 2) }}</th>
                                <th colspan="4"></th>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Summary Stats -->
                <div class="row mt-3 g-2">
                    <div class="col-md-3">
                        <div class="border rounded p-2 text-center small">
                            <div class="text-muted">عدد الدفعات</div>
                            <div class="fw-bold" id="batchCount">{{ $product->inventoryBatches->where('quantity', '>', 0)->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 text-center small">
                            <div class="text-muted">قريبة الانتهاء</div>
                            <div class="fw-bold text-warning" id="expiringCount">{{ $product->inventoryBatches->filter(fn($b) => $b->expiry_date && $b->expiry_date->diffInDays(now(), false) <= 30 && $b->expiry_date->diffInDays(now(), false) >= 0 && $b->quantity > 0)->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 text-center small">
                            <div class="text-muted">منتهية الصلاحية</div>
                            <div class="fw-bold text-danger">{{ $product->inventoryBatches->filter(fn($b) => $b->expiry_date && $b->expiry_date->lt(now()) && $b->quantity > 0)->count() }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-2 text-center small">
                            <div class="text-muted">متوسط التكلفة</div>
                            <div class="fw-bold">{{ $product->total_stock > 0 ? number_format($product->inventoryBatches->where('quantity', '>', 0)->sum(fn($b) => $b->quantity * $b->cost_price) / $product->total_stock, 2) : '0.00' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history">
                <div class="row mb-3 g-2">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="filterType">
                            <option value="">كل الأنواع</option>
                            <option value="opening_balance">رصيد افتتاحي</option>
                            <option value="purchase">شراء</option>
                            <option value="sale">بيع</option>
                            <option value="adjustment">تعديل</option>
                            <option value="return">مرتجع</option>
                            <option value="damage">تالف</option>
                            <option value="loss">فقدان</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" id="filterDateFrom" placeholder="من تاريخ">
                    </div>
                    <div class="col-md-3">
                        <input type="date" class="form-control form-control-sm" id="filterDateTo" placeholder="إلى تاريخ">
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="filterHistoryBtn">
                            <i class="ti ti-filter me-1"></i>تصفية
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="historyTable">
                        <thead >
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>الكمية</th>
                                <th>قبل</th>
                                <th>بعد</th>
                                <th>المستخدم</th>
                                <th>السبب</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            @foreach($product->stockMovements->sortByDesc('created_at')->take(20) as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $movement->quantity >= 0 ? 'success' : 'danger' }}">
                                            {{ $movement->type_arabic }}
                                        </span>
                                    </td>
                                    <td class="{{ $movement->quantity >= 0 ? 'movement-in' : 'movement-out' }} fw-bold">
                                        {{ $movement->quantity >= 0 ? '+' : '' }}{{ number_format($movement->quantity, 2) }}
                                    </td>
                                    <td>{{ number_format($movement->before_quantity, 2) }}</td>
                                    <td>{{ number_format($movement->after_quantity, 2) }}</td>
                                    <td>{{ $movement->user?->name ?? '-' }}</td>
                                    <td>{{ $movement->reason ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Barcodes Tab -->
            <div class="tab-pane fade" id="barcodes">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <p class="text-muted mb-0 small">يمكنك إضافة باركودات متعددة للمنتج (مثل: نكهات مختلفة، ألوان، أحجام) وسيتم التعرف عليها عند البيع أو الشراء</p>
                    </div>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addBarcodeModal">
                        <i class="ti ti-plus me-1"></i>إضافة باركود
                    </button>
                </div>

                @if($product->barcode)
                <div class="alert alert-light border mb-3">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-barcode fs-4 me-2"></i>
                        <div>
                            <div class="fw-bold">الباركود الأساسي</div>
                            <code class="fs-5">{{ $product->barcode }}</code>
                        </div>
                    </div>
                </div>
                @endif

                <div class="table-responsive" id="barcodesContainer">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>الباركود</th>
                                <th>التسمية / الوصف</th>
                                <th>الحالة</th>
                                <th>تاريخ الإضافة</th>
                                <th width="100"></th>
                            </tr>
                        </thead>
                        <tbody id="barcodesTableBody">
                            @forelse($product->barcodes as $barcode)
                                <tr data-barcode-id="{{ $barcode->id }}">
                                    <td><code class="fs-6">{{ $barcode->barcode }}</code></td>
                                    <td>{{ $barcode->label ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $barcode->is_active ? 'success' : 'secondary' }}">
                                            {{ $barcode->is_active ? 'نشط' : 'غير نشط' }}
                                        </span>
                                    </td>
                                    <td>{{ $barcode->created_at->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-barcode-btn" data-id="{{ $barcode->id }}" data-barcode="{{ $barcode->barcode }}" data-label="{{ $barcode->label }}" data-active="{{ $barcode->is_active ? '1' : '0' }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-barcode-btn" data-id="{{ $barcode->id }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noBarcodesRow">
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="ti ti-barcode-off fs-1 d-block mb-2"></i>
                                        لا توجد باركودات إضافية
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

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-plus me-1"></i>إضافة مخزون</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addStockForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="0.0001" step="0.0001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">سعر التكلفة</label>
                        <input type="number" class="form-control" name="cost_price" min="0" step="0.01" value="{{ $product->baseUnit?->cost_price ?? 0 }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الصلاحية</label>
                        <input type="date" class="form-control" name="expiry_date">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السبب <span class="text-danger">*</span></label>
                        <select class="form-select" name="reason" required>
                            <option value="شراء">شراء</option>
                            <option value="مرتجع من عميل">مرتجع من عميل</option>
                            <option value="تحويل مخزني">تحويل مخزني</option>
                            <option value="تعديل جرد">تعديل جرد</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-plus me-1"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Stock Modal -->
<div class="modal fade" id="removeStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-minus me-1"></i>خصم مخزون</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="removeStockForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الكمية <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="0.0001" step="0.0001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">نوع العملية <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="sale">بيع</option>
                            <option value="damage">تالف</option>
                            <option value="loss">فقدان</option>
                            <option value="return">مرتجع للمورد</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">من دفعة</label>
                        <select class="form-select" name="batch_id" id="batchSelect">
                            <option value="">تلقائي (FIFO)</option>
                            @foreach($product->inventoryBatches->where('quantity', '>', 0) as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->batch_number }} ({{ $batch->quantity }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السبب <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reason" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="ti ti-minus me-1"></i>خصم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-adjustments me-1"></i>تعديل يدوي</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustStockForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الكمية الحالية</label>
                        <input type="text" class="form-control" value="{{ number_format($product->total_stock, 2) }}" readonly id="currentStockInput">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الكمية الجديدة <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="new_quantity" min="0" step="0.0001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السبب <span class="text-danger">*</span></label>
                        <select class="form-select" name="reason" required>
                            <option value="جرد فعلي">جرد فعلي</option>
                            <option value="تصحيح خطأ">تصحيح خطأ</option>
                            <option value="فرق جرد">فرق جرد</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="ti ti-adjustments me-1"></i>تعديل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Unit Modal -->
<div class="modal fade" id="createUnitModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-tag me-1"></i>إضافة وحدة جديدة</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUnitForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الوحدة <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newUnitName" name="name" placeholder="مثال: كرتونة" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الرمز <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newUnitSymbol" name="symbol" placeholder="مثال: كرتونة" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="createUnitSubmit">
                        <i class="ti ti-check me-1"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-edit me-1"></i>تعديل الصنف</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الصنف <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editProductName" name="name" value="{{ $product->name }}" required>
                        <div class="invalid-feedback">اسم الصنف مطلوب</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الباركود</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editProductBarcode" name="barcode" value="{{ $product->barcode }}">
                            <button type="button" class="btn btn-outline-secondary" id="editGenerateBarcodeBtn">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                        <div class="text-danger small" id="editBarcodeFeedback" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="editProductStatus" name="status" {{ $product->status == 'active' ? 'checked' : '' }}>
                            <label class="form-check-label" for="editProductStatus">نشط</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="editProductSubmit">
                        <i class="ti ti-check me-1"></i>حفظ التغييرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($product->barcode)
<div class="modal fade" id="barcodeLabelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-printer me-1"></i>طباعة باركود</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">عدد الملصقات</label>
                        <input type="number" class="form-control" id="labelQuantity" value="1" min="1" max="100">
                    </div>
                    <div class="col-6">
                        <label class="form-label">حجم الملصق</label>
                        <select class="form-select" id="labelSize">
                            <option value="50x30">50 × 30 مم</option>
                            <option value="40x25">40 × 25 مم</option>
                            <option value="60x40">60 × 40 مم</option>
                            <option value="70x50">70 × 50 مم</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">معاينة (الحجم الفعلي)</label>
                    <div class="d-flex justify-content-center p-3" style="background: #f5f5f5; border-radius: 8px;">
                        <div id="labelPreviewContainer" class="barcode-label-preview" style="width: 50mm; height: 30mm; background: #fff; border: 1px solid #000; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 2mm;">
                            <div id="previewName" style="font-weight: 600; font-size: 9px; margin-bottom: 2px; color: #000; text-align: center; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $product->name }}</div>
                            <svg id="barcodePreview"></svg>
                            <div id="previewPrice" style="font-weight: 700; font-size: 11px; margin-top: 2px; color: #000;">{{ number_format($product->baseUnit?->sell_price ?? 0, 2) }} د.ل</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary btn-sm" id="printLabelsBtn">
                    <i class="ti ti-printer me-1"></i>طباعة
                </button>
            </div>
        </div>
    </div>
</div>

<div id="printArea" style="display: none;"></div>
@endif

<!-- Add Barcode Modal -->
<div class="modal fade" id="addBarcodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-barcode me-1"></i>إضافة باركود جديد</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addBarcodeForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الباركود <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newBarcodeValue" name="barcode" placeholder="امسح أو أدخل الباركود" required autofocus>
                            <button type="button" class="btn btn-outline-secondary" id="generateNewBarcodeBtn" title="توليد باركود">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                        <div class="text-danger small" id="barcodeFeedback" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التسمية / الوصف</label>
                        <input type="text" class="form-control" id="newBarcodeLabel" name="label" placeholder="مثال: نكهة الفراولة، اللون الأحمر، حجم كبير...">
                        <small class="text-muted">التسمية تظهر عند البيع لتمييز المنتج</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm" id="addBarcodeSubmit">
                        <i class="ti ti-plus me-1"></i>إضافة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Barcode Modal -->
<div class="modal fade" id="editBarcodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="ti ti-edit me-1"></i>تعديل الباركود</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editBarcodeForm">
                <input type="hidden" id="editBarcodeId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الباركود <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="editBarcodeValue" name="barcode" required>
                            <button type="button" class="btn btn-outline-secondary" id="generateEditBarcodeBtn" title="توليد باركود">
                                <i class="ti ti-refresh"></i>
                            </button>
                        </div>
                        <div class="text-danger small" id="editBarcodeFeedbackNew" style="display: none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التسمية / الوصف</label>
                        <input type="text" class="form-control" id="editBarcodeLabel" name="label" placeholder="مثال: نكهة الفراولة، اللون الأحمر، حجم كبير...">
                        <small class="text-muted">التسمية تظهر عند البيع لتمييز المنتج</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الحالة</label>
                        <select class="form-select" id="editBarcodeActive" name="is_active">
                            <option value="1">نشط</option>
                            <option value="0">غير نشط</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="editBarcodeSubmit">
                        <i class="ti ti-check me-1"></i>حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productId = {{ $product->id }};
    const csrfToken = '{{ csrf_token() }}';
    let editBarcodeValid = true;
    let editBarcodeCheckTimeout = null;

    const tabStorageKey = `product_${productId}_active_tab`;
    const savedTab = localStorage.getItem(tabStorageKey);
    if (savedTab) {
        const tabEl = document.querySelector(`#productTabs button[data-bs-target="${savedTab}"]`);
        if (tabEl) new bootstrap.Tab(tabEl).show();
    }
    document.querySelectorAll('#productTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem(tabStorageKey, e.target.getAttribute('data-bs-target'));
        });
    });

    document.getElementById('editProductBtn').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('editProductModal')).show();
    });

    document.getElementById('editGenerateBarcodeBtn').addEventListener('click', async function() {
        try {
            const response = await fetch('{{ route("products.generate-barcode") }}');
            const result = await response.json();
            document.getElementById('editProductBarcode').value = result.barcode;
            document.getElementById('editProductBarcode').classList.remove('is-invalid');
            document.getElementById('editProductBarcode').classList.add('is-valid');
            document.getElementById('editBarcodeFeedback').style.display = 'none';
            editBarcodeValid = true;
        } catch (error) {
            showToast('حدث خطأ في توليد الباركود', 'danger');
        }
    });

    document.getElementById('editProductBarcode').addEventListener('input', function() {
        clearTimeout(editBarcodeCheckTimeout);
        const barcode = this.value.trim();

        if (!barcode) {
            this.classList.remove('is-invalid', 'is-valid');
            document.getElementById('editBarcodeFeedback').style.display = 'none';
            editBarcodeValid = true;
            return;
        }

        editBarcodeCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("products.check-barcode") }}?barcode=${encodeURIComponent(barcode)}&exclude_id=${productId}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('editBarcodeFeedback').textContent = 'هذا الباركود مستخدم بالفعل';
                    document.getElementById('editBarcodeFeedback').style.display = 'block';
                    editBarcodeValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('editBarcodeFeedback').style.display = 'none';
                    editBarcodeValid = true;
                }
            } catch (error) {
                console.error('Error checking barcode:', error);
            }
        }, 300);
    });

    document.getElementById('editProductForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const name = document.getElementById('editProductName').value.trim();
        const barcode = document.getElementById('editProductBarcode').value.trim();
        const status = document.getElementById('editProductStatus').checked ? 'active' : 'inactive';

        if (!name) {
            document.getElementById('editProductName').classList.add('is-invalid');
            return;
        }

        if (!editBarcodeValid) {
            document.getElementById('editProductBarcode').focus();
            return;
        }

        const btn = document.getElementById('editProductSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/products/${productId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name, barcode, status })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
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

    document.getElementById('editProductName').addEventListener('input', function() {
        if (this.value.trim()) {
            this.classList.remove('is-invalid');
        }
    });

    document.getElementById('deleteProductBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'تأكيد الحذف',
            html: `هل أنت متأكد من حذف الصنف:<br><strong>{{ $product->name }}</strong>`,
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

                    const response = await fetch(`/products/${productId}`, {
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
                            window.location.href = '{{ route("products.index") }}';
                        });
                    } else {
                        Swal.fire('خطأ', data.message || 'حدث خطأ', 'error');
                    }
                } catch (error) {
                    Swal.fire('خطأ', 'حدث خطأ في حذف الصنف', 'error');
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

    function activateTab(tabId) {
        const tabButton = document.getElementById(tabId + '-tab');
        if (tabButton) {
            const tab = new bootstrap.Tab(tabButton);
            tab.show();
        }
    }

    const hash = window.location.hash.substring(1);
    if (hash) {
        activateTab(hash);
        if (hash == 'units') {
            showToast('تم إنشاء الصنف بنجاح! يرجى إكمال بيانات الوحدات والأسعار', 'success');
        }
        history.replaceState(null, null, window.location.pathname);
    }

    function updateStockDisplays(newStock) {
        const formatted = parseFloat(newStock).toFixed(2);
        document.getElementById('totalStock').textContent = formatted;
        document.getElementById('overviewStock').textContent = formatted + ' {{ $product->baseUnit?->unit?->name ?? "وحدة" }}';
        document.getElementById('stockTabTotal').textContent = formatted;
        document.getElementById('currentStockInput').value = formatted;
    }

    document.getElementById('addStockForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`/products/${productId}/inventory/add`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                updateStockDisplays(result.new_stock);
                this.reset();
                bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
                loadBatches();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    document.getElementById('removeStockForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`/products/${productId}/inventory/remove`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                updateStockDisplays(result.new_stock);
                this.reset();
                bootstrap.Modal.getInstance(document.getElementById('removeStockModal')).hide();
                loadBatches();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    document.getElementById('adjustStockForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);

        try {
            const response = await fetch(`/products/${productId}/inventory/adjust`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                updateStockDisplays(result.new_stock);
                this.reset();
                bootstrap.Modal.getInstance(document.getElementById('adjustStockModal')).hide();
                loadBatches();
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    async function loadBatches() {
        try {
            const response = await fetch(`/products/${productId}/inventory/batches`);
            const result = await response.json();
            if (result.success) {
                const container = document.getElementById('batchesContainer');
                const batchSelect = document.getElementById('batchSelect');

                const typeLabels = {
                    'opening_balance': 'رصيد افتتاحي',
                    'purchase': 'شراء',
                    'adjustment': 'تعديل',
                    'return': 'مرتجع'
                };

                if (result.batches.length == 0) {
                    container.innerHTML = `
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead >
                                <tr>
                                    <th>رقم الدفعة</th><th>النوع</th><th>الكمية</th><th>سعر التكلفة</th>
                                    <th>إجمالي التكلفة</th><th>تاريخ الصلاحية</th><th>الحالة</th>
                                    <th>تاريخ الإضافة</th><th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ti ti-package-off fs-1 d-block mb-2"></i>
                                        لا توجد دفعات متوفرة
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    `;
                } else {
                    let totalQty = 0;
                    let totalCost = 0;

                    const rows = result.batches.map(batch => {
                        const qty = parseFloat(batch.quantity);
                        const cost = parseFloat(batch.cost_price || 0);
                        totalQty += qty;
                        totalCost += qty * cost;

                        return `
                            <tr>
                                <td><code>${batch.batch_number}</code></td>
                                <td>${typeLabels[batch.type] || batch.type || '-'}</td>
                                <td class="fw-bold">${qty.toFixed(2)}</td>
                                <td>${cost.toFixed(2)}</td>
                                <td>${(qty * cost).toFixed(2)}</td>
                                <td>${batch.expiry_date || '<span class="text-muted">-</span>'}</td>
                                <td><span class="badge bg-${batch.status.class}">${batch.status.label}</span></td>
                                <td>${batch.created_at ? batch.created_at.split('T')[0] : '-'}</td>
                                <td>${batch.notes || '-'}</td>
                            </tr>
                        `;
                    }).join('');

                    container.innerHTML = `
                        <table class="table table-sm table-hover table-bordered mb-0">
                            <thead >
                                <tr>
                                    <th>رقم الدفعة</th><th>النوع</th><th>الكمية</th><th>سعر التكلفة</th>
                                    <th>إجمالي التكلفة</th><th>تاريخ الصلاحية</th><th>الحالة</th>
                                    <th>تاريخ الإضافة</th><th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                            <tfoot >
                                <tr>
                                    <th colspan="2">الإجمالي</th>
                                    <th>${totalQty.toFixed(2)}</th>
                                    <th>-</th>
                                    <th>${totalCost.toFixed(2)}</th>
                                    <th colspan="4"></th>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                }

                document.getElementById('batchCount').textContent = result.batches.length;
                document.getElementById('stockTabTotal').textContent = result.total_stock.toFixed(2);

                batchSelect.innerHTML = '<option value="">تلقائي (FIFO)</option>' +
                    result.batches.map(batch =>
                        `<option value="${batch.id}">${batch.batch_number} (${parseFloat(batch.quantity).toFixed(2)})</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Error loading batches:', error);
        }
    }

    document.getElementById('filterHistoryBtn').addEventListener('click', loadHistory);

    async function loadHistory() {
        const type = document.getElementById('filterType').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;

        const params = new URLSearchParams();
        if (type) params.append('type', type);
        if (dateFrom) params.append('date_from', dateFrom);
        if (dateTo) params.append('date_to', dateTo);

        try {
            const response = await fetch(`/products/${productId}/inventory/history?${params}`);
            const result = await response.json();
            if (result.success) {
                const tbody = document.getElementById('historyTableBody');
                tbody.innerHTML = result.movements.data.map(m => `
                    <tr>
                        <td>${new Date(m.created_at).toLocaleString('ar-LY')}</td>
                        <td>
                            <span class="badge bg-${parseFloat(m.quantity) >= 0 ? 'success' : 'danger'}">
                                ${getTypeArabic(m.type)}
                            </span>
                        </td>
                        <td class="${parseFloat(m.quantity) >= 0 ? 'movement-in' : 'movement-out'} fw-bold">
                            ${parseFloat(m.quantity) >= 0 ? '+' : ''}${parseFloat(m.quantity).toFixed(2)}
                        </td>
                        <td>${parseFloat(m.before_quantity).toFixed(2)}</td>
                        <td>${parseFloat(m.after_quantity).toFixed(2)}</td>
                        <td>${m.user?.name ?? '-'}</td>
                        <td>${m.reason ?? '-'}</td>
                    </tr>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }

    function getTypeArabic(type) {
        const types = {
            'opening_balance': 'رصيد افتتاحي',
            'purchase': 'شراء',
            'sale': 'بيع',
            'adjustment': 'تعديل',
            'return': 'مرتجع',
            'damage': 'تالف',
            'loss': 'فقدان'
        };
        return types[type] || type;
    }

    document.getElementById('unitsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const units = [];
        let index = 0;

        while (formData.has(`units[${index}][unit_id]`)) {
            units.push({
                unit_id: formData.get(`units[${index}][unit_id]`),
                multiplier: formData.get(`units[${index}][multiplier]`),
                sell_price: formData.get(`units[${index}][sell_price]`),
                cost_price: formData.get(`units[${index}][cost_price]`) || null,
            });
            index++;
        }

        try {
            const response = await fetch(`/products/${productId}/inventory/units`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ units })
            });
            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }
    });

    let unitRowIndex = {{ $product->productUnits->count() }};

    function calculateMargin(row) {
        const costInput = row.querySelector('.base-cost') || row.querySelector('.calculated-cost');
        const sellPriceInput = row.querySelector('.sell-price-input');
        const marginDisplay = row.querySelector('.margin-display');

        if (!costInput || !sellPriceInput || !marginDisplay) return;

        const cost = parseFloat(costInput.value) || 0;
        const sellPrice = parseFloat(sellPriceInput.value) || 0;
        const margin = cost > 0 ? ((sellPrice - cost) / cost) * 100 : 0;
        marginDisplay.value = margin.toFixed(2) + '%';
    }

    function updateAllMargins() {
        document.querySelectorAll('.unit-row').forEach(row => {
            calculateMargin(row);
        });
    }

    function updateCalculatedCostsAndMargins() {
        const baseCostInput = document.querySelector('.base-cost');
        const baseCost = parseFloat(baseCostInput?.value) || 0;

        document.querySelectorAll('.unit-row').forEach((row, index) => {
            if (index == 0) {
                calculateMargin(row);
                return;
            }

            const multiplierInput = row.querySelector('.multiplier-input');
            const calculatedCostInput = row.querySelector('.calculated-cost');
            const multiplier = parseFloat(multiplierInput?.value) || 1;

            if (calculatedCostInput) {
                calculatedCostInput.value = (baseCost * multiplier).toFixed(2);
            }

            calculateMargin(row);
        });
    }

    document.getElementById('unitsTableBody').addEventListener('input', function(e) {
        if (e.target.classList.contains('base-cost')) {
            updateCalculatedCostsAndMargins();
        } else if (e.target.classList.contains('sell-price-input')) {
            calculateMargin(e.target.closest('.unit-row'));
        } else if (e.target.classList.contains('multiplier-input')) {
            updateCalculatedCostsAndMargins();
        }
    });

    document.getElementById('addUnitRowBtn').addEventListener('click', function() {
        const tbody = document.getElementById('unitsTableBody');
        const unitOptions = Array.from(document.querySelectorAll('.unit-select')[0].options)
            .map(opt => `<option value="${opt.value}">${opt.text}</option>`)
            .join('');

        const row = document.createElement('tr');
        row.className = 'unit-row';
        row.innerHTML = `
            <td>
                <select class="form-select form-select-sm unit-select" name="units[${unitRowIndex}][unit_id]" required>
                    ${unitOptions}
                </select>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm multiplier-input" name="units[${unitRowIndex}][multiplier]" value="1.0000" min="0.0001" step="0.0001" required>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm calculated-cost" value="0.00" readonly disabled>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm sell-price-input" name="units[${unitRowIndex}][sell_price]" value="0.00" min="0" step="0.01">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm margin-display" value="0.00%" readonly disabled>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm remove-unit-row">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        unitRowIndex++;
        updateCalculatedCostsAndMargins();
    });

    document.getElementById('unitsTableBody').addEventListener('click', function(e) {
        if (e.target.closest('.remove-unit-row')) {
            e.target.closest('.unit-row').remove();
        }
    });

    document.getElementById('createUnitForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('newUnitName').value.trim();
        const symbol = document.getElementById('newUnitSymbol').value.trim();

        if (!name || !symbol) return;

        const submitBtn = document.getElementById('createUnitSubmit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإضافة...';

        try {
            const response = await fetch('{{ route("units.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ name, symbol })
            });
            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'تم إضافة الوحدة بنجاح', 'success');
                bootstrap.Modal.getInstance(document.getElementById('createUnitModal')).hide();
                this.reset();

                const newOption = `<option value="${result.unit.id}">${result.unit.name}</option>`;
                document.querySelectorAll('.unit-select').forEach(select => {
                    select.insertAdjacentHTML('beforeend', newOption);
                });
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-check me-1"></i>إضافة';
    });

    document.getElementById('newUnitName').addEventListener('input', function() {
        document.getElementById('newUnitSymbol').value = this.value;
    });

    @if($product->barcode)
    const barcodeValue = '{{ $product->barcode }}';
    const productName = '{{ addslashes($product->name) }}';
    const productPrice = '{{ number_format($product->baseUnit?->sell_price ?? 0, 2) }}';

    const labelSizes = {
        '50x30': { width: 50, height: 30, barcodeWidth: 1.2, barcodeHeight: 25, fontSize: 9, priceSize: 11 },
        '40x25': { width: 40, height: 25, barcodeWidth: 1, barcodeHeight: 20, fontSize: 8, priceSize: 10 },
        '60x40': { width: 60, height: 40, barcodeWidth: 1.5, barcodeHeight: 35, fontSize: 10, priceSize: 12 },
        '70x50': { width: 70, height: 50, barcodeWidth: 1.8, barcodeHeight: 40, fontSize: 11, priceSize: 14 }
    };

    function updatePreview() {
        const size = document.getElementById('labelSize').value;
        const config = labelSizes[size];
        const container = document.getElementById('labelPreviewContainer');

        container.style.width = config.width + 'mm';
        container.style.height = config.height + 'mm';
        document.getElementById('previewName').style.fontSize = config.fontSize + 'px';
        document.getElementById('previewPrice').style.fontSize = config.priceSize + 'px';

        JsBarcode("#barcodePreview", barcodeValue, {
            format: "CODE128",
            width: config.barcodeWidth,
            height: config.barcodeHeight,
            displayValue: true,
            fontSize: config.fontSize,
            margin: 0
        });
    }

    document.getElementById('labelSize').addEventListener('change', updatePreview);

    document.getElementById('printBarcodeBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('barcodeLabelModal'));
        modal.show();
        setTimeout(updatePreview, 100);
    });

    document.getElementById('printLabelsBtn').addEventListener('click', function() {
        const quantity = parseInt(document.getElementById('labelQuantity').value) || 1;
        const size = document.getElementById('labelSize').value;
        const config = labelSizes[size];
        const printArea = document.getElementById('printArea');

        let labelsHtml = '';
        for (let i = 0; i < quantity; i++) {
            labelsHtml += `
                <div class="barcode-label">
                    <div class="label-name">${productName}</div>
                    <svg class="barcode-svg" data-value="${barcodeValue}"></svg>
                    <div class="label-price">${productPrice} د.ل</div>
                </div>
            `;
        }

        printArea.innerHTML = labelsHtml;

        printArea.querySelectorAll('.barcode-svg').forEach(svg => {
            JsBarcode(svg, svg.dataset.value, {
                format: "CODE128",
                width: config.barcodeWidth,
                height: config.barcodeHeight,
                displayValue: true,
                fontSize: config.fontSize,
                margin: 0
            });
        });

        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>طباعة باركود - ${productName}</title>
                <style>
                    @page {
                        size: ${config.width}mm ${config.height}mm;
                        margin: 0;
                    }
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    html, body {
                        width: ${config.width}mm;
                        margin: 0;
                        padding: 0;
                    }
                    body { font-family: Arial, sans-serif; }
                    .barcode-label {
                        width: ${config.width}mm;
                        height: ${config.height}mm;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        text-align: center;
                        padding: 1mm;
                        page-break-after: always;
                        box-sizing: border-box;
                    }
                    .barcode-label:last-child {
                        page-break-after: auto;
                    }
                    .label-name {
                        font-weight: 600;
                        font-size: ${config.fontSize}px;
                        margin-bottom: 1mm;
                        max-width: 100%;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                    .label-price {
                        font-weight: 700;
                        font-size: ${config.priceSize}px;
                        margin-top: 1mm;
                    }
                    svg { display: block; }
                </style>
            </head>
            <body>${printArea.innerHTML}</body>
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() { window.close(); };
                };
            <\/script>
            </html>
        `);
        printWindow.document.close();

        bootstrap.Modal.getInstance(document.getElementById('barcodeLabelModal')).hide();
    });
    @endif

    // Barcodes Management
    let barcodeValid = true;
    let barcodeCheckTimeout = null;

    document.getElementById('generateNewBarcodeBtn').addEventListener('click', async function() {
        try {
            const response = await fetch('{{ route("products.generate-barcode") }}');
            const result = await response.json();
            document.getElementById('newBarcodeValue').value = result.barcode;
            document.getElementById('newBarcodeValue').classList.remove('is-invalid');
            document.getElementById('newBarcodeValue').classList.add('is-valid');
            document.getElementById('barcodeFeedback').style.display = 'none';
            barcodeValid = true;
        } catch (error) {
            showToast('حدث خطأ في توليد الباركود', 'danger');
        }
    });

    document.getElementById('newBarcodeValue').addEventListener('input', function() {
        clearTimeout(barcodeCheckTimeout);
        const barcode = this.value.trim();

        if (!barcode) {
            this.classList.remove('is-invalid', 'is-valid');
            document.getElementById('barcodeFeedback').style.display = 'none';
            barcodeValid = false;
            return;
        }

        barcodeCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("products.check-barcode") }}?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('barcodeFeedback').textContent = 'هذا الباركود مستخدم بالفعل';
                    document.getElementById('barcodeFeedback').style.display = 'block';
                    barcodeValid = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('barcodeFeedback').style.display = 'none';
                    barcodeValid = true;
                }
            } catch (error) {
                console.error('Error checking barcode:', error);
            }
        }, 300);
    });

    document.getElementById('addBarcodeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const barcode = document.getElementById('newBarcodeValue').value.trim();
        const label = document.getElementById('newBarcodeLabel').value.trim();

        if (!barcode) {
            document.getElementById('newBarcodeValue').classList.add('is-invalid');
            return;
        }

        if (!barcodeValid) {
            document.getElementById('newBarcodeValue').focus();
            return;
        }

        const btn = document.getElementById('addBarcodeSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإضافة...';

        try {
            const response = await fetch(`/products/${productId}/barcodes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ barcode, label })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('addBarcodeModal')).hide();
                this.reset();
                document.getElementById('newBarcodeValue').classList.remove('is-valid', 'is-invalid');

                const noRow = document.getElementById('noBarcodesRow');
                if (noRow) noRow.remove();

                const tbody = document.getElementById('barcodesTableBody');
                const newRow = document.createElement('tr');
                newRow.setAttribute('data-barcode-id', result.barcode.id);
                newRow.innerHTML = `
                    <td><code class="fs-6">${result.barcode.barcode}</code></td>
                    <td>${result.barcode.label || '-'}</td>
                    <td><span class="badge bg-success">نشط</span></td>
                    <td>${new Date().toISOString().split('T')[0]}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-primary btn-sm edit-barcode-btn" data-id="${result.barcode.id}" data-barcode="${result.barcode.barcode}" data-label="${result.barcode.label || ''}" data-active="1">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm delete-barcode-btn" data-id="${result.barcode.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(newRow);

                const badge = document.querySelector('#barcodes-tab .badge');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else {
                    document.querySelector('#barcodes-tab').innerHTML += ' <span class="badge bg-primary ms-1">1</span>';
                }
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-plus me-1"></i>إضافة';
    });

    document.getElementById('barcodesTableBody').addEventListener('click', async function(e) {
        const editBtn = e.target.closest('.edit-barcode-btn');
        const deleteBtn = e.target.closest('.delete-barcode-btn');

        if (editBtn) {
            const barcodeId = editBtn.dataset.id;
            const barcode = editBtn.dataset.barcode;
            const label = editBtn.dataset.label;
            const active = editBtn.dataset.active;

            document.getElementById('editBarcodeId').value = barcodeId;
            document.getElementById('editBarcodeValue').value = barcode;
            document.getElementById('editBarcodeLabel').value = label || '';
            document.getElementById('editBarcodeActive').value = active;
            document.getElementById('editBarcodeValue').classList.remove('is-valid', 'is-invalid');
            document.getElementById('editBarcodeFeedbackNew').style.display = 'none';
            editBarcodeValidNew = true;
            originalEditBarcode = barcode;

            new bootstrap.Modal(document.getElementById('editBarcodeModal')).show();
            return;
        }

        if (!deleteBtn) return;

        const barcodeId = deleteBtn.dataset.id;
        const row = deleteBtn.closest('tr');

        const result = await Swal.fire({
            title: 'تأكيد الحذف',
            text: 'هل أنت متأكد من حذف هذا الباركود؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، حذف',
            cancelButtonText: 'إلغاء'
        });

        if (!result.isConfirmed) return;

        try {
            const response = await fetch(`/products/${productId}/barcodes/${barcodeId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();
            if (data.success) {
                showToast(data.message, 'success');
                row.remove();

                const badge = document.querySelector('#barcodes-tab .badge');
                if (badge) {
                    const newCount = parseInt(badge.textContent) - 1;
                    if (newCount <= 0) {
                        badge.remove();
                    } else {
                        badge.textContent = newCount;
                    }
                }

                const tbody = document.getElementById('barcodesTableBody');
                if (tbody.children.length == 0) {
                    tbody.innerHTML = `
                        <tr id="noBarcodesRow">
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="ti ti-barcode-off fs-1 d-block mb-2"></i>
                                لا توجد باركودات إضافية
                            </td>
                        </tr>
                    `;
                }
            } else {
                showToast(data.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في حذف الباركود', 'danger');
        }
    });

    let editBarcodeValidNew = true;
    let editBarcodeCheckTimeoutNew = null;
    let originalEditBarcode = '';

    document.getElementById('generateEditBarcodeBtn').addEventListener('click', async function() {
        try {
            const response = await fetch('{{ route("products.generate-barcode") }}');
            const result = await response.json();
            document.getElementById('editBarcodeValue').value = result.barcode;
            document.getElementById('editBarcodeValue').classList.remove('is-invalid');
            document.getElementById('editBarcodeValue').classList.add('is-valid');
            document.getElementById('editBarcodeFeedbackNew').style.display = 'none';
            editBarcodeValidNew = true;
        } catch (error) {
            showToast('حدث خطأ في توليد الباركود', 'danger');
        }
    });

    document.getElementById('editBarcodeValue').addEventListener('input', function() {
        clearTimeout(editBarcodeCheckTimeoutNew);
        const barcode = this.value.trim();

        if (!barcode) {
            this.classList.remove('is-invalid', 'is-valid');
            document.getElementById('editBarcodeFeedbackNew').style.display = 'none';
            editBarcodeValidNew = true;
            return;
        }

        if (barcode === originalEditBarcode) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            document.getElementById('editBarcodeFeedbackNew').style.display = 'none';
            editBarcodeValidNew = true;
            return;
        }

        editBarcodeCheckTimeoutNew = setTimeout(async () => {
            try {
                const response = await fetch(`{{ route("products.check-barcode") }}?barcode=${encodeURIComponent(barcode)}`);
                const result = await response.json();

                if (result.exists) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    document.getElementById('editBarcodeFeedbackNew').textContent = 'هذا الباركود مستخدم بالفعل';
                    document.getElementById('editBarcodeFeedbackNew').style.display = 'block';
                    editBarcodeValidNew = false;
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    document.getElementById('editBarcodeFeedbackNew').style.display = 'none';
                    editBarcodeValidNew = true;
                }
            } catch (error) {
                console.error('Error checking barcode:', error);
            }
        }, 300);
    });

    document.getElementById('editBarcodeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const barcodeId = document.getElementById('editBarcodeId').value;
        const barcode = document.getElementById('editBarcodeValue').value.trim();
        const label = document.getElementById('editBarcodeLabel').value.trim();
        const isActive = document.getElementById('editBarcodeActive').value;

        if (!barcode) {
            document.getElementById('editBarcodeValue').classList.add('is-invalid');
            return;
        }

        if (!editBarcodeValidNew) {
            document.getElementById('editBarcodeValue').focus();
            return;
        }

        const btn = document.getElementById('editBarcodeSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

        try {
            const response = await fetch(`/products/${productId}/barcodes/${barcodeId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ barcode, label, is_active: isActive })
            });

            const result = await response.json();
            if (result.success) {
                showToast(result.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('editBarcodeModal')).hide();

                const row = document.querySelector(`tr[data-barcode-id="${barcodeId}"]`);
                if (row) {
                    row.innerHTML = `
                        <td><code class="fs-6">${result.barcode.barcode}</code></td>
                        <td>${result.barcode.label || '-'}</td>
                        <td><span class="badge bg-${result.barcode.is_active ? 'success' : 'secondary'}">${result.barcode.is_active ? 'نشط' : 'غير نشط'}</span></td>
                        <td>${row.cells[3].textContent}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-primary btn-sm edit-barcode-btn" data-id="${result.barcode.id}" data-barcode="${result.barcode.barcode}" data-label="${result.barcode.label || ''}" data-active="${result.barcode.is_active ? '1' : '0'}">
                                <i class="ti ti-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm delete-barcode-btn" data-id="${result.barcode.id}">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    `;
                }
            } else {
                showToast(result.message || 'حدث خطأ', 'danger');
            }
        } catch (error) {
            showToast('حدث خطأ في الاتصال', 'danger');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ التعديلات';
    });
});
</script>
@endpush
