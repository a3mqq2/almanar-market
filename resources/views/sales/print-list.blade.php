<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>كشف مبيعات</title>
    <style>
        @page {
            margin: 10mm;
            size: A4;
        }
        * {
            font-family: 'IBM Plex Sans Arabic', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
            direction: rtl;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 11px;
            color: #666;
        }
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .filters-info {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 11px;
        }
        .filter-item .label {
            color: #666;
        }
        .filter-item .value {
            font-weight: bold;
        }
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .sales-table th {
            background: #333;
            color: white;
            padding: 7px 8px;
            font-size: 11px;
            text-align: center;
        }
        .sales-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 11px;
            text-align: center;
        }
        .sales-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .sales-table .text-right {
            text-align: right;
        }
        .sales-table .text-success {
            color: #198754;
        }
        .sales-table .text-danger {
            color: #dc3545;
        }
        .sales-table .fw-bold {
            font-weight: bold;
        }
        .summary-section {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .summary-box {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 12px;
            text-align: center;
        }
        .summary-box .value {
            font-size: 16px;
            font-weight: bold;
            display: block;
        }
        .summary-box .label {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 25px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">طباعة</button>

    <div class="header">
        <h1>المنار ماركت</h1>
        <p>البيضاء - ليبيا | هاتف: 0916698784</p>
    </div>

    <div class="report-title">كشف مبيعات</div>

    <div class="filters-info">
        @if($filters['date_from'] && $filters['date_to'])
            <div class="filter-item">
                <span class="label">الفترة:</span>
                <span class="value">{{ $filters['date_from'] }} إلى {{ $filters['date_to'] }}</span>
            </div>
        @endif
        @if($filters['cashier'])
            <div class="filter-item">
                <span class="label">الكاشير:</span>
                <span class="value">{{ $filters['cashier'] }}</span>
            </div>
        @endif
        @if($filters['payment_method'])
            <div class="filter-item">
                <span class="label">طريقة الدفع:</span>
                <span class="value">
                    @if($filters['payment_method'] === 'cash') نقداً
                    @elseif($filters['payment_method'] === 'bank') خدمات مصرفية
                    @elseif($filters['payment_method'] === 'credit') آجل
                    @endif
                </span>
            </div>
        @endif
        @if($filters['status'])
            <div class="filter-item">
                <span class="label">الحالة:</span>
                <span class="value">{{ $filters['status'] === 'completed' ? 'مكتملة' : 'ملغاة' }}</span>
            </div>
        @endif
        <div class="filter-item">
            <span class="label">عدد الفواتير:</span>
            <span class="value">{{ $summary['total_sales'] }}</span>
        </div>
    </div>

    <table class="sales-table">
        <thead>
            <tr>
                <th>#</th>
                <th>رقم الفاتورة</th>
                <th>التاريخ</th>
                <th>الزبون</th>
                <th>الكاشير</th>
                <th>الإجمالي</th>
                <th>المدفوع</th>
                <th>آجل</th>
                <th>الحالة</th>
                <th>حالة الدفع</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $index => $sale)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $sale->invoice_number }}</td>
                <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                <td class="text-right">{{ $sale->customer?->name ?? 'زبون عادي' }}</td>
                <td>{{ $sale->cashier?->name ?? '-' }}</td>
                <td class="fw-bold">{{ number_format($sale->total, 2) }}</td>
                <td class="text-success">{{ number_format($sale->paid_amount, 2) }}</td>
                <td class="text-danger">{{ number_format($sale->credit_amount, 2) }}</td>
                <td>{{ $sale->status_arabic }}</td>
                <td>{{ $sale->payment_status_arabic }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-section">
        <div class="summary-box">
            <span class="value">{{ number_format($summary['total_amount'], 2) }}</span>
            <span class="label">إجمالي المبيعات</span>
        </div>
        <div class="summary-box">
            <span class="value" style="color: #198754;">{{ number_format($summary['total_cash'], 2) }}</span>
            <span class="label">نقداً</span>
        </div>
        <div class="summary-box">
            <span class="value" style="color: #0d6efd;">{{ number_format($summary['total_bank'], 2) }}</span>
            <span class="label">خدمات مصرفية</span>
        </div>
        <div class="summary-box">
            <span class="value" style="color: #dc3545;">{{ number_format($summary['total_credit'], 2) }}</span>
            <span class="label">آجل</span>
        </div>
    </div>

    <div class="footer">
        <p>طُبع بتاريخ: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
