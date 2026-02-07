@php
    $summary = $data['summary'];
    $soldItems = $data['sold_items'];
    $paymentMethods = $data['payment_methods'];
    $inventory = $data['inventory_status'];
    $returns = $data['returns'];
@endphp

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label">إجمالي المبيعات</div>
                    <div class="stat-value">{{ number_format($summary['total_sales'], 2) }}</div>
                </div>
                <div class="stat-icon"><i class="ti ti-cash"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label">إجمالي التكلفة</div>
                    <div class="stat-value">{{ number_format($summary['total_cost'], 2) }}</div>
                </div>
                <div class="stat-icon"><i class="ti ti-receipt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label">إجمالي الربح</div>
                    <div class="stat-value">{{ number_format($summary['total_profit'], 2) }}</div>
                    <div class="text-muted small">{{ $summary['profit_margin'] }}%</div>
                </div>
                <div class="stat-icon"><i class="ti ti-trending-up"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label">عدد الفواتير</div>
                    <div class="stat-value">{{ $summary['invoice_count'] }}</div>
                    <div class="text-muted small">{{ number_format($summary['items_count'], 2) }} قطعة</div>
                </div>
                <div class="stat-icon"><i class="ti ti-file-invoice"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(var(--bs-secondary-rgb), 0.15); color: var(--bs-secondary);">
                    <i class="ti ti-discount-2"></i>
                </div>
                <div>
                    <div class="stat-label">إجمالي الخصم</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['total_discount'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info);">
                    <i class="ti ti-calculator"></i>
                </div>
                <div>
                    <div class="stat-label">متوسط الفاتورة</div>
                    <div class="fs-4 fw-bold">{{ number_format($summary['average_invoice'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card {{ $returns['count'] > 0 ? 'danger' : '' }}">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: rgba(var(--bs-danger-rgb), 0.15); color: var(--bs-danger);">
                    <i class="ti ti-receipt-refund"></i>
                </div>
                <div>
                    <div class="stat-label">المرتجعات</div>
                    <div class="fs-4 fw-bold">{{ number_format($returns['total_amount'], 2) }}</div>
                    <div class="text-muted small">{{ $returns['count'] }} عملية</div>
                </div>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs nav-tabs-report mb-4" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabPayments">
            <i class="ti ti-credit-card me-1"></i>طرق الدفع
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabItems">
            <i class="ti ti-package me-1"></i>الأصناف المباعة
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInventory">
            <i class="ti ti-alert-triangle me-1"></i>تنبيهات المخزون
        </button>
    </li>
    @if($returns['count'] > 0)
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabReturns">
            <i class="ti ti-receipt-refund me-1"></i>المرتجعات
        </button>
    </li>
    @endif
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="tabPayments">
        <div class="row g-3">
            @foreach($paymentMethods as $method)
            <div class="col-md-3 col-6">
                <div class="payment-method-card">
                    <div class="method-name">{{ $method['name'] }}</div>
                    <div class="method-amount">{{ number_format($method['total_amount'], 2) }}</div>
                    <div class="method-count">{{ $method['transaction_count'] }} عملية</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="tab-pane fade" id="tabItems">
        <div class="section-card">
            <div class="section-body">
                @if(count($soldItems) > 0)
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصنف</th>
                                <th>الباركود</th>
                                <th>الكمية</th>
                                <th>إجمالي البيع</th>
                                <th>التكلفة</th>
                                <th>الربح</th>
                                <th>هامش الربح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($soldItems as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-medium">{{ $item['product_name'] }}</td>
                                <td class="text-muted">{{ $item['barcode'] }}</td>
                                <td>{{ $item['quantity'] }}</td>
                                <td class="amount-neutral">{{ number_format($item['total_revenue'], 2) }}</td>
                                <td>{{ number_format($item['total_cost'], 2) }}</td>
                                <td class="{{ $item['profit'] >= 0 ? 'amount-positive' : 'amount-negative' }}">{{ number_format($item['profit'], 2) }}</td>
                                <td>{{ $item['profit_margin'] }}%</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">المجموع</td>
                                <td class="amount-neutral">{{ number_format($summary['total_sales'], 2) }}</td>
                                <td>{{ number_format($summary['total_cost'], 2) }}</td>
                                <td class="amount-positive">{{ number_format($summary['total_profit'], 2) }}</td>
                                <td>{{ $summary['profit_margin'] }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <i class="ti ti-package-off d-block"></i>
                    <p class="mb-0">لا توجد مبيعات في هذا اليوم</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="tabInventory">
        <div class="section-card">
            <div class="section-body">
                @if(count($inventory) > 0)
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>الصنف</th>
                                <th>الباركود</th>
                                <th>المخزون الحالي</th>
                                <th>تاريخ الانتهاء</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inventory as $item)
                            @php
                                $statusLabels = [
                                    'ok' => 'طبيعي',
                                    'low_stock' => 'مخزون منخفض',
                                    'out_of_stock' => 'نفد المخزون',
                                    'expiring_soon' => 'قارب على الانتهاء',
                                    'expired' => 'منتهي الصلاحية'
                                ];
                            @endphp
                            <tr>
                                <td class="fw-medium">{{ $item['product_name'] }}</td>
                                <td class="text-muted">{{ $item['barcode'] ?? '-' }}</td>
                                <td>{{ $item['current_stock'] }}</td>
                                <td>{{ $item['expiry_date'] ?? '-' }}</td>
                                <td><span class="status-badge status-{{ $item['status'] }}">{{ $statusLabels[$item['status']] ?? $item['status'] }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    <i class="ti ti-check d-block" style="color: var(--bs-success);"></i>
                    <p class="mb-0">لا توجد تنبيهات للمخزون</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($returns['count'] > 0)
    <div class="tab-pane fade" id="tabReturns">
        <div class="section-card">
            <div class="section-body">
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>رقم المرتجع</th>
                                <th>رقم الفاتورة</th>
                                <th>الصنف</th>
                                <th>الكمية</th>
                                <th>المبلغ</th>
                                <th>السبب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returns['items'] as $item)
                            <tr>
                                <td class="fw-medium">{{ $item['return_number'] }}</td>
                                <td>{{ $item['invoice_number'] }}</td>
                                <td>{{ $item['product_name'] }}</td>
                                <td>{{ $item['quantity'] }}</td>
                                <td class="amount-negative">{{ number_format($item['amount'], 2) }}</td>
                                <td>{{ $item['reason'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">إجمالي المرتجعات</td>
                                <td class="amount-negative">{{ number_format($returns['total_amount'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
