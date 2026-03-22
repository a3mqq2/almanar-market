<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف المشتريات</title>
    <link href="{{ asset('assets/fonts/fonts.css') }}" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Cairo', 'Tahoma', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #000;
            background: #fff;
            direction: rtl;
            padding: 15px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .store-info {
            text-align: right;
        }
        .store-logo {
            max-height: 60px;
            max-width: 150px;
            margin-bottom: 5px;
        }
        .store-name {
            font-size: 16px;
            font-weight: bold;
        }
        .store-details {
            font-size: 10px;
            color: #333;
        }
        .document-info {
            text-align: left;
            font-size: 10px;
            color: #555;
        }
        .report-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 12px;
        }
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 8px 12px;
            border: 1px solid #000;
            margin-bottom: 15px;
            font-size: 10px;
        }
        .filter-item .label {
            color: #555;
        }
        .filter-item .value {
            font-weight: bold;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background: #000;
            color: #fff;
            padding: 6px 8px;
            font-size: 10px;
            text-align: center;
            white-space: nowrap;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #ccc;
            font-size: 10px;
            text-align: center;
        }
        .data-table tr:nth-child(even) {
            background: #f5f5f5;
        }
        .data-table .text-right {
            text-align: right;
        }
        .data-table .fw-bold {
            font-weight: bold;
        }
        .data-table .text-danger {
            color: #000;
            font-weight: bold;
            text-decoration: underline;
        }
        .summary-section {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .summary-box {
            flex: 1;
            border: 2px solid #000;
            padding: 10px;
            text-align: center;
        }
        .summary-box .value {
            font-size: 16px;
            font-weight: bold;
            display: block;
        }
        .summary-box .label {
            font-size: 9px;
            color: #333;
            margin-top: 3px;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 8px;
        }
        .print-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            padding: 8px 20px;
            background: #333;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 13px;
            border-radius: 4px;
        }
        .print-btn:hover {
            background: #555;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .data-table thead { display: table-header-group; }
            .data-table tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">طباعة</button>

    <div class="header">
        <div class="store-info">
            <img src="{{ asset('logo-dark.png') }}" alt="" class="store-logo">
            <div class="store-name">{{ config('app.name', 'المنار ماركت') }}</div>
            <div class="store-details">للمواد الغذائية والخضروات والفواكه</div>
        </div>
        <div class="document-info">
            تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}<br>
            طُبع بواسطة: {{ auth()->user()->name ?? '-' }}
        </div>
    </div>

    <div class="report-title">كشف المشتريات</div>

    @if(array_filter($filters))
    <div class="filters-bar">
        @if($filters['supplier'])
            <div class="filter-item">
                <span class="label">المورد:</span>
                <span class="value">{{ $filters['supplier'] }}</span>
            </div>
        @endif
        @if($filters['payment_type'])
            <div class="filter-item">
                <span class="label">الدفع:</span>
                <span class="value">{{ $filters['payment_type'] === 'cash' ? 'نقدي' : 'آجل' }}</span>
            </div>
        @endif
        @if($filters['status'])
            <div class="filter-item">
                <span class="label">الحالة:</span>
                <span class="value">{{ $filters['status'] === 'approved' ? 'معتمدة' : 'ملغاة' }}</span>
            </div>
        @endif
        @if($filters['date_from'] && $filters['date_to'])
            <div class="filter-item">
                <span class="label">الفترة:</span>
                <span class="value">{{ $filters['date_from'] }} إلى {{ $filters['date_to'] }}</span>
            </div>
        @elseif($filters['date_from'])
            <div class="filter-item">
                <span class="label">من:</span>
                <span class="value">{{ $filters['date_from'] }}</span>
            </div>
        @elseif($filters['date_to'])
            <div class="filter-item">
                <span class="label">إلى:</span>
                <span class="value">{{ $filters['date_to'] }}</span>
            </div>
        @endif
        @if($filters['search'])
            <div class="filter-item">
                <span class="label">بحث:</span>
                <span class="value">{{ $filters['search'] }}</span>
            </div>
        @endif
        <div class="filter-item">
            <span class="label">عدد الفواتير:</span>
            <span class="value">{{ $summary['total_count'] }}</span>
        </div>
    </div>
    @endif

    @if($groupByInvoice)
    <table class="data-table">
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>التاريخ</th>
                <th>المورد</th>
                <th>الأصناف</th>
                <th>الدفع</th>
                <th>الإجمالي</th>
                <th>المدفوع</th>
                <th>المتبقي</th>
            </tr>
        </thead>
        <tbody>
            @php $grouped = $purchases->groupBy('invoice_number'); @endphp
            @forelse($grouped as $invoice => $items)
            @php $first = $items->first(); @endphp
            <tr>
                <td class="fw-bold">{{ $invoice ?: '-' }}</td>
                <td>{{ $first->purchase_date?->format('Y-m-d') }}</td>
                <td>{{ $first->supplier?->name ?? '-' }}</td>
                <td class="text-right">
                    @foreach($items as $p)
                        @foreach($p->items as $item)
                            {{ $item->product?->name ?? '-' }}
                            ({{ number_format($item->quantity, 2) }} {{ $item->productUnit?->unit?->name ?? '' }}
                            × {{ number_format($item->unit_price, 2) }})@if(!$loop->parent->last || !$loop->last)،@endif
                        @endforeach
                    @endforeach
                </td>
                <td>
                    @if($items->every(fn($p) => $p->payment_type === 'cash')) نقدي
                    @elseif($items->every(fn($p) => $p->payment_type === 'credit')) آجل
                    @else مختلط @endif
                </td>
                <td class="fw-bold">{{ number_format($items->sum('total'), 2) }}</td>
                <td>{{ number_format($items->sum('paid_amount'), 2) }}</td>
                <td @if($items->sum('remaining_amount') > 0) class="text-danger" @endif>{{ number_format($items->sum('remaining_amount'), 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="padding: 30px; text-align: center;">لا توجد عمليات شراء</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @else
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم الفاتورة</th>
                <th>التاريخ</th>
                <th>المنتج</th>
                <th>المورد</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>الدفع</th>
                <th>الإجمالي</th>
                <th>المدفوع</th>
                <th>المتبقي</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $index => $p)
            <tr>
                <td>{{ $p->id }}</td>
                <td>{{ $p->invoice_number ?? '-' }}</td>
                <td>{{ $p->purchase_date?->format('Y-m-d') }}</td>
                <td class="text-right">{{ $p->items->first()?->product?->name ?? '-' }}</td>
                <td>{{ $p->supplier?->name ?? '-' }}</td>
                <td>{{ $p->items->first() ? number_format($p->items->first()->quantity, 2) : '-' }}</td>
                <td>{{ $p->items->first() ? number_format($p->items->first()->unit_price, 2) : '-' }}</td>
                <td>{{ $p->payment_type === 'credit' ? 'آجل' : 'نقدي' }}</td>
                <td class="fw-bold">{{ number_format($p->total, 2) }}</td>
                <td>{{ number_format($p->paid_amount, 2) }}</td>
                <td @if($p->remaining_amount > 0) class="text-danger" @endif>{{ number_format($p->remaining_amount, 2) }}</td>
                <td>{{ $p->status_arabic }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="12" style="padding: 30px; text-align: center;">لا توجد عمليات شراء</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @endif

    <div class="summary-section">
        <div class="summary-box">
            <span class="value">{{ $summary['total_count'] }}</span>
            <span class="label">عدد الفواتير</span>
        </div>
        <div class="summary-box">
            <span class="value">{{ number_format($summary['total_amount'], 2) }}</span>
            <span class="label">إجمالي المشتريات</span>
        </div>
        <div class="summary-box">
            <span class="value">{{ number_format($summary['total_paid'], 2) }}</span>
            <span class="label">إجمالي المدفوع</span>
        </div>
        <div class="summary-box">
            <span class="value">{{ number_format($summary['total_remaining'], 2) }}</span>
            <span class="label">إجمالي المتبقي</span>
        </div>
        <div class="summary-box">
            <span class="value">{{ $summary['cash_count'] }}</span>
            <span class="label">نقدي</span>
        </div>
        <div class="summary-box">
            <span class="value">{{ $summary['credit_count'] }}</span>
            <span class="label">آجل</span>
        </div>
    </div>

    <div class="footer">
        هذا الكشف صادر آلياً من النظام | {{ config('app.name', 'المنار ماركت') }} | {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
