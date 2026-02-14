<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: 80mm auto;
        }
        * {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            width: 80mm;
            padding: 5mm;
            font-size: 12px;
            line-height: 1.4;
            direction: rtl;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
            color: #333;
        }
        .invoice-info {
            margin-bottom: 10px;
            font-size: 11px;
        }
        .invoice-info table {
            width: 100%;
        }
        .invoice-info td {
            padding: 2px 0;
        }
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            background: #f0f0f0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .items-table th {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 2px;
            font-size: 10px;
            text-align: center;
        }
        .items-table td {
            padding: 4px 2px;
            font-size: 10px;
            vertical-align: top;
        }
        .items-table .item-name {
            text-align: right;
            max-width: 30mm;
            word-wrap: break-word;
        }
        .items-table .item-qty {
            text-align: center;
        }
        .items-table .item-price,
        .items-table .item-total {
            text-align: left;
        }
        .totals {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 11px;
        }
        .totals-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 8px 0;
            margin: 5px 0;
        }
        .payments {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        .payments h4 {
            font-size: 11px;
            margin-bottom: 5px;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            padding: 2px 0;
        }
        .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 15px;
            font-size: 10px;
        }
        .footer p {
            margin: 3px 0;
        }
        .barcode {
            text-align: center;
            margin: 10px 0;
            font-family: 'Libre Barcode 39', cursive;
            font-size: 36px;
        }
        .thank-you {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
        @media print {
            body {
                width: 80mm;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            padding: 10px 20px;
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
        .logo {
            width: 50mm;
            height: auto;
            margin-bottom: 8px;
            filter: brightness(0);
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">طباعة</button>

    <div class="header">
        <img src="{{ asset('logo-dark.png') }}" alt="المنار ماركت" class="logo">
        <p>البيضاء - ليبيا</p>
        <p>هاتف: 0916698784</p>
    </div>

    <div class="invoice-number">
        فاتورة رقم: {{ $sale->invoice_number }}
    </div>

    <div class="invoice-info">
        <table>
            <tr>
                <td>التاريخ:</td>
                <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <td>الوقت:</td>
                <td>{{ $sale->created_at->format('H:i') }}</td>
            </tr>
            <tr>
                <td>الكاشير:</td>
                <td>{{ $sale->cashier?->name ?? '-' }}</td>
            </tr>
            @if($sale->customer)
            <tr>
                <td>الزبون:</td>
                <td>{{ $sale->customer->name }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align: right;">الصنف</th>
                <th>الكمية</th>
                <th>السعر</th>
                <th>الإجمالي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td class="item-name">{{ $item->product->name }}</td>
                <td class="item-qty">{{ number_format($item->quantity, 2) }}</td>
                <td class="item-price">{{ number_format($item->unit_price, 2) }}</td>
                <td class="item-total">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>المجموع الفرعي:</span>
            <span>{{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if($sale->discount_amount > 0)
        <div class="totals-row">
            <span>الخصم:</span>
            <span>{{ number_format($sale->discount_amount, 2) }}-</span>
        </div>
        @endif
        @if($sale->tax_amount > 0)
        <div class="totals-row">
            <span>الضريبة ({{ $sale->tax_rate }}%):</span>
            <span>{{ number_format($sale->tax_amount, 2) }}</span>
        </div>
        @endif
        <div class="totals-row grand-total">
            <span>الإجمالي:</span>
            <span>{{ number_format($sale->total, 2) }} د.ل</span>
        </div>
    </div>

    @if($sale->payments->count() > 0)
    <div class="payments">
        <h4>طرق الدفع:</h4>
        @foreach($sale->payments as $payment)
        <div class="payment-row">
            <span>{{ $payment->paymentMethod->name }}:</span>
            <span>{{ number_format($payment->amount, 2) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if($sale->paid_amount > 0 || $sale->credit_amount > 0)
    <div class="totals" style="border-top: none; padding-top: 5px; margin-top: 5px;">
        <div class="totals-row">
            <span>المدفوع:</span>
            <span>{{ number_format($sale->paid_amount, 2) }}</span>
        </div>
        @if($sale->credit_amount > 0)
        <div class="totals-row" style="color: red;">
            <span>آجل:</span>
            <span>{{ number_format($sale->credit_amount, 2) }}</span>
        </div>
        @endif
        @if($sale->paid_amount > $sale->total)
        <div class="totals-row" style="font-weight: bold;">
            <span>الباقي:</span>
            <span>{{ number_format($sale->paid_amount - $sale->total, 2) }}</span>
        </div>
        @endif
    </div>
    @endif

    <div class="barcode">
        *{{ $sale->invoice_number }}*
    </div>

    <div class="footer">
        <p>شكراً لتسوقكم معنا</p>
        <p>نتمنى لكم يوماً سعيداً</p>
        <p style="margin-top: 10px; font-size: 9px;">{{ now()->format('Y-m-d H:i:s') }}</p>
    </div>

    <script>
        window.onload = function() {
            if (window.opener) {
                window.print();
            }
        };
    </script>
</body>
</html>
