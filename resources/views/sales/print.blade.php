<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $sale->invoice_number }}</title>
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
            font-size: 14px;
            line-height: 1.5;
            direction: rtl;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .invoice-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-box {
            width: 48%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .info-box h4 {
            font-size: 14px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 12px;
        }
        .info-row .label {
            color: #666;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background: #333;
            color: white;
            padding: 10px;
            font-size: 12px;
            text-align: center;
        }
        .items-table th:first-child {
            text-align: right;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
            text-align: center;
        }
        .items-table td:first-child {
            text-align: right;
        }
        .items-table tr:hover {
            background: #f9f9f9;
        }
        .totals-section {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-box {
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            border-bottom: 1px solid #eee;
        }
        .totals-row:last-child {
            border-bottom: none;
        }
        .totals-row.total {
            background: #333;
            color: white;
            font-size: 16px;
            font-weight: bold;
        }
        .payments-section {
            margin-top: 20px;
        }
        .payments-section h4 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
            border-bottom: 1px dashed #ddd;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
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

    <div class="invoice-title">
        فاتورة مبيعات رقم: {{ $sale->invoice_number }}
        <span class="status-badge status-{{ $sale->status }}">{{ $sale->status_arabic }}</span>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h4>معلومات الفاتورة</h4>
            <div class="info-row">
                <span class="label">التاريخ:</span>
                <span>{{ $sale->sale_date->format('Y-m-d') }}</span>
            </div>
            <div class="info-row">
                <span class="label">الوقت:</span>
                <span>{{ $sale->created_at->format('H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="label">الكاشير:</span>
                <span>{{ $sale->cashier?->name ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="label">حالة الدفع:</span>
                <span>{{ $sale->payment_status_arabic }}</span>
            </div>
        </div>
        <div class="info-box">
            <h4>معلومات الزبون</h4>
            @if($sale->customer)
            <div class="info-row">
                <span class="label">الاسم:</span>
                <span>{{ $sale->customer->name }}</span>
            </div>
            <div class="info-row">
                <span class="label">الهاتف:</span>
                <span>{{ $sale->customer->phone }}</span>
            </div>
            @else
            <div class="info-row">
                <span>زبون عادي</span>
            </div>
            @endif
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">الصنف</th>
                <th>الوحدة</th>
                <th>الكمية</th>
                <th>سعر الوحدة</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->unitName }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td style="font-weight: bold;">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-box">
            <div class="totals-row">
                <span>المجموع الفرعي:</span>
                <span>{{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if($sale->discount_amount > 0)
            <div class="totals-row">
                <span>الخصم @if($sale->discount_type === 'percentage')({{ $sale->discount_value }}%)@endif:</span>
                <span>-{{ number_format($sale->discount_amount, 2) }}</span>
            </div>
            @endif
            @if($sale->tax_amount > 0)
            <div class="totals-row">
                <span>الضريبة ({{ $sale->tax_rate }}%):</span>
                <span>{{ number_format($sale->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="totals-row total">
                <span>الإجمالي:</span>
                <span>{{ number_format($sale->total, 2) }} د.ل</span>
            </div>
            <div class="totals-row">
                <span>المدفوع:</span>
                <span style="color: green;">{{ number_format($sale->paid_amount, 2) }}</span>
            </div>
            @if($sale->credit_amount > 0)
            <div class="totals-row">
                <span>آجل:</span>
                <span style="color: red;">{{ number_format($sale->credit_amount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    @if($sale->payments->count() > 0)
    <div class="payments-section">
        <h4>طرق الدفع</h4>
        @foreach($sale->payments as $payment)
        <div class="payment-row">
            <span>{{ $payment->paymentMethod->name }}</span>
            <span>{{ number_format($payment->amount, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if($sale->notes)
    <div style="margin-top: 20px; padding: 10px; background: #f9f9f9; border-radius: 5px;">
        <strong>ملاحظات:</strong> {{ $sale->notes }}
    </div>
    @endif

    <div class="footer">
        <p>شكراً لتسوقكم معنا - نتمنى لكم يوماً سعيداً</p>
        <p style="margin-top: 10px; font-size: 10px;">طُبعت بتاريخ: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
