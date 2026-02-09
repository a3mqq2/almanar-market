<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة مشتريات #{{ $purchase->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: 'Cairo', 'Tahoma', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #000;
            background: #fff;
            direction: rtl;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .store-info {
            text-align: right;
        }
        .store-logo {
            max-height: 80px;
            max-width: 200px;
            margin-bottom: 8px;
        }
        .store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .store-details {
            font-size: 11px;
            color: #333;
        }
        .document-info {
            text-align: left;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .print-info {
            font-size: 10px;
            color: #555;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 5px;
        }
        .status-approved {
            background: #198754;
            color: #fff;
        }
        .status-draft {
            background: #ffc107;
            color: #000;
        }
        .status-cancelled {
            background: #dc3545;
            color: #fff;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 20px;
        }
        .info-block {
            width: 48%;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        .info-row {
            display: flex;
            margin-bottom: 4px;
        }
        .info-label {
            width: 100px;
            font-weight: bold;
            color: #555;
        }
        .info-value {
            flex: 1;
        }
        .items-section {
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        .items-table th {
            background: #eee;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }
        .items-table td {
            font-size: 11px;
        }
        .items-table .col-num {
            width: 30px;
            text-align: center;
        }
        .items-table .col-name {
            text-align: right;
        }
        .items-table .col-qty,
        .items-table .col-unit,
        .items-table .col-price,
        .items-table .col-total {
            text-align: center;
        }
        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .items-table tfoot td {
            background: #eee;
            font-weight: bold;
        }
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 300px;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 10px;
            border: 1px solid #ddd;
        }
        .summary-table .label {
            background: #f5f5f5;
            font-weight: 600;
        }
        .summary-table .value {
            text-align: left;
            direction: ltr;
        }
        .summary-table .total-row {
            font-weight: bold;
            font-size: 14px;
        }
        .summary-table .total-row td {
            background: #eee;
            border: 2px solid #000;
        }
        .page-footer {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .signature-box {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        .legal-note {
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
        }
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .items-table {
                page-break-inside: auto;
            }
            .items-table thead {
                display: table-header-group;
            }
            .items-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .page-footer {
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
        .print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background: #333;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 14px;
            border-radius: 4px;
        }
        .print-btn:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">طباعة</button>

    <div class="page-header">
        <div class="store-info">
            <img src="{{ asset('logo-dark.png') }}" alt="المنار ماركت" class="store-logo">
            <div class="store-name">{{ config('app.name', 'المنار ماركت') }}</div>
            <div class="store-details">للمواد الغذائية والخضروات والفواكه</div>
        </div>
        <div class="document-info">
            <div class="invoice-number">فاتورة مشتريات #{{ $purchase->id }}</div>
            <span class="status-badge status-{{ $purchase->status }}">{{ $purchase->status_arabic }}</span>
            <div class="print-info" style="margin-top: 10px;">
                تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}<br>
                طُبع بواسطة: {{ auth()->user()->name ?? '-' }}
            </div>
        </div>
    </div>

    <div class="document-title">فاتورة مشتريات</div>

    <div class="info-section">
        <div class="info-block">
            <div class="section-title">بيانات المورد</div>
            <div class="info-row">
                <span class="info-label">اسم المورد:</span>
                <span class="info-value">{{ $purchase->supplier->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الهاتف:</span>
                <span class="info-value" dir="ltr" style="text-align: right;">{{ $purchase->supplier->phone }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم فاتورة المورد:</span>
                <span class="info-value">{{ $purchase->invoice_number ?: '-' }}</span>
            </div>
        </div>
        <div class="info-block">
            <div class="section-title">بيانات الفاتورة</div>
            <div class="info-row">
                <span class="info-label">تاريخ الشراء:</span>
                <span class="info-value">{{ $purchase->purchase_date->format('Y-m-d') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">طريقة الدفع:</span>
                <span class="info-value">{{ $purchase->payment_type_arabic }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">أنشئت بواسطة:</span>
                <span class="info-value">{{ $purchase->creator?->name ?? '-' }}</span>
            </div>
            @if($purchase->approved_at)
            <div class="info-row">
                <span class="info-label">اعتمدت بواسطة:</span>
                <span class="info-value">{{ $purchase->approver?->name ?? '-' }}</span>
            </div>
            @endif
        </div>
    </div>

    <div class="items-section">
        <div class="section-title">تفاصيل الأصناف</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-name">الصنف</th>
                    <th class="col-unit">الوحدة</th>
                    <th class="col-qty">الكمية</th>
                    <th class="col-price">السعر</th>
                    <th class="col-total">الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $index => $item)
                <tr>
                    <td class="col-num">{{ $index + 1 }}</td>
                    <td class="col-name">{{ $item->product->name }}</td>
                    <td class="col-unit">{{ $item->productUnit?->unit?->name ?? '-' }}</td>
                    <td class="col-qty">{{ number_format($item->quantity, 4) }}</td>
                    <td class="col-price">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="col-total">{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: left;">إجمالي الأصناف: {{ $purchase->items->count() }}</td>
                    <td class="col-total">{{ number_format($purchase->subtotal, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="label">المجموع الفرعي</td>
                <td class="value">{{ number_format($purchase->subtotal, 2) }}</td>
            </tr>
            @if($purchase->discount_amount > 0)
            <tr>
                <td class="label">
                    الخصم
                    @if($purchase->discount_type == 'percentage')
                        ({{ $purchase->discount_value }}%)
                    @endif
                </td>
                <td class="value" style="color: #c00;">- {{ number_format($purchase->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($purchase->tax_amount > 0)
            <tr>
                <td class="label">الضريبة ({{ $purchase->tax_rate }}%)</td>
                <td class="value">+ {{ number_format($purchase->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label">الإجمالي</td>
                <td class="value">{{ number_format($purchase->total, 2) }}</td>
            </tr>
            @if($purchase->payment_type == 'credit')
            <tr>
                <td class="label">المدفوع</td>
                <td class="value" style="color: #060;">{{ number_format($purchase->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">المتبقي</td>
                <td class="value" style="color: #c00; font-weight: bold;">{{ number_format($purchase->remaining_amount, 2) }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($purchase->notes)
    <div style="margin-bottom: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
        <strong>ملاحظات:</strong> {{ $purchase->notes }}
    </div>
    @endif

    <div class="page-footer">
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">توقيع المستلم</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">توقيع المورد</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">توقيع المسؤول</div>
            </div>
        </div>
        <div class="legal-note">
            هذه الفاتورة صادرة آلياً من النظام ولا تحتاج إلى توقيع إلا في حالة المصادقة الرسمية.
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
