<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=272px, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>فاتورة {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: 72mm auto;
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Tahoma', 'Arial', sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html {
            width: 72mm;
            max-width: 72mm;
            overflow-x: hidden;
        }

        body {
            width: 72mm;
            max-width: 72mm;
            min-width: 72mm;
            margin: 0;
            padding: 2mm 3mm 1mm;
            font-size: 9px;
            line-height: 1.2;
            color: #000;
            background: #fff;
            direction: rtl;
            overflow-x: hidden;
            -webkit-text-size-adjust: none;
            text-size-adjust: none;
        }

        .receipt {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }

        .store-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .store-header-text {
            text-align: right;
            flex: 1;
            min-width: 0;
        }

        .store-logo {
            flex-shrink: 0;
            margin-right: 2mm;
        }

        .store-logo img {
            height: 32px;
            filter: grayscale(1) contrast(3) brightness(0);
        }

        .store-name {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .store-info {
            font-size: 8px;
            margin-top: 0.5mm;
            color: #333;
        }

        .divider {
            border: none;
            border-top: 1px dashed #555;
            margin: 1.5mm 0;
        }

        .divider-bold {
            border-top: 1.5px solid #000;
        }

        .invoice-number-box {
            font-size: 12px;
            font-weight: bold;
            padding: 1mm 0;
            letter-spacing: 1px;
        }

        .invoice-meta-table {
            width: 100%;
            font-size: 8px;
        }

        .invoice-meta-table td {
            padding: 0.3mm 0;
        }

        .invoice-meta-table .meta-label {
            text-align: right;
            color: #555;
            white-space: nowrap;
        }

        .invoice-meta-table .meta-value {
            text-align: left;
            font-weight: 600;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1mm 0;
            table-layout: fixed;
        }

        .items-table th {
            font-size: 8px;
            font-weight: bold;
            padding: 0.8mm 1mm;
            border-bottom: 1.5px solid #000;
            border-top: 1.5px solid #000;
            text-align: right;
            background: #000;
            color: #fff;
        }

        .items-table th:last-child {
            text-align: left;
        }

        .items-table td {
            font-size: 8px;
            padding: 0.8mm 0.5mm;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
            overflow: hidden;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table .col-name { width: 45%; }
        .items-table .col-qty { width: 30%; }
        .items-table .col-total { width: 25%; }

        .items-table .item-name {
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 600;
        }

        .items-table .item-qty {
            text-align: center;
            white-space: nowrap;
            font-size: 7px;
            font-weight: bold;
            color: #444;
        }

        .items-table .item-total {
            text-align: left;
            white-space: nowrap;
            font-weight: bold;
        }

        .summary-table {
            width: 100%;
            margin: 1mm 0;
        }

        .summary-table td {
            padding: 0.3mm 0;
            font-size: 9px;
        }

        .summary-table .label {
            text-align: right;
        }

        .summary-table .value {
            text-align: left;
            font-weight: bold;
        }

        .summary-table .total-row td {
            font-size: 11px;
            font-weight: bold;
            padding: 1mm 0;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
        }

        .payment-info {
            margin: 1mm 0;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 8px;
            padding: 0.3mm 0;
        }

        .customer-box {
            font-size: 8px;
            margin: 1mm 0;
            padding: 1mm;
            border: 1px dashed #999;
        }

        .credit-warning {
            font-weight: bold;
            padding: 1mm;
            border: 1.5px solid #000;
            margin: 1mm 0;
            font-size: 9px;
            background: #000;
            color: #fff;
        }

        .footer {
            margin-top: 1.5mm;
        }

        .footer-message {
            font-size: 9px;
        }

        .barcode-section {
            margin: 1.5mm 0 0.5mm;
        }

        .barcode-section svg {
            max-width: 100%;
            height: auto;
        }

        .barcode-text {
            font-size: 8px;
            letter-spacing: 1px;
            margin-top: 0.5mm;
        }

        .timestamp {
            font-size: 7px;
            color: #999;
            margin-top: 0.5mm;
        }

        .notes-section {
            font-size: 9px;
            margin: 0.5mm 0;
            color: #333;
        }

        @media print {
            html, body {
                width: 72mm;
                max-width: 72mm;
                min-width: 72mm;
            }

            .no-print {
                display: none !important;
            }
        }

        .print-controls {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .print-controls button {
            padding: 8px 15px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .print-controls .btn-print {
            background: #28a745;
            color: white;
        }

        .print-controls .btn-close {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">طباعة</button>
        <button class="btn-close" onclick="window.close()">إغلاق</button>
    </div>

    <div class="receipt">
        <div class="store-header">
            <div class="store-header-text">
                <div class="store-name">{{ config('app.store_name', 'المنار ماركت') }}</div>
                <div class="store-info">
                    {{ config('app.store_address', 'البيضاء - ليبيا') }}<br>
                    {{ config('app.store_phone', '0916698784') }}
                    @if(config('app.store_tax_number'))
                    <br>ض: {{ config('app.store_tax_number') }}
                    @endif
                </div>
            </div>
            <div class="store-logo">
                <img src="{{ asset('assets/images/logo-sm.png') }}" alt="logo">
            </div>
        </div>

        <hr class="divider divider-bold">

        <div class="center invoice-number-box">{{ $sale->invoice_number }}</div>

        <table class="invoice-meta-table">
            <tr>
                <td class="meta-label">التاريخ:</td>
                <td class="meta-value">{{ $sale->sale_date->format('Y/m/d') }} - {{ $sale->created_at->format('h:i A') }}</td>
            </tr>
            <tr>
                <td class="meta-label">الكاشير:</td>
                <td class="meta-value">{{ $sale->cashier?->name ?? '-' }}</td>
            </tr>
            @if($sale->customer)
            <tr>
                <td class="meta-label">الزبون:</td>
                <td class="meta-value">{{ $sale->customer->name }}</td>
            </tr>
            @endif
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-name">البيان</th>
                    <th class="col-qty">الكمية</th>
                    <th class="col-total">القيمة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td class="item-name">{{ $item->product->name }}@if($item->barcode_label) ({{ $item->barcode_label }})@endif</td>
                    <td class="item-qty">{{ number_format($item->quantity, $item->quantity == floor($item->quantity) ? 0 : 2) }} × {{ number_format($item->unit_price, 2) }}</td>
                    <td class="item-total">{{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            @if($sale->discount_amount > 0 || $sale->tax_amount > 0)
            <tr>
                <td class="label">المجموع:</td>
                <td class="value">{{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @endif
            @if($sale->discount_amount > 0)
            <tr>
                <td class="label">الخصم{{ $sale->discount_type == 'percentage' ? ' ('.$sale->discount_value.'%)' : '' }}:</td>
                <td class="value">-{{ number_format($sale->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($sale->tax_amount > 0)
            <tr>
                <td class="label">الضريبة ({{ $sale->tax_rate }}%):</td>
                <td class="value">{{ number_format($sale->tax_amount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td class="label">الإجمالي:</td>
                <td class="value">{{ number_format($sale->total, 2) }} د.ل</td>
            </tr>
        </table>

        @if($sale->payments->count() > 0)
        <div class="payment-info">
            @foreach($sale->payments as $payment)
            <div class="payment-row">
                <span>{{ $payment->paymentMethod->name }}:</span>
                <span class="bold">{{ number_format($payment->amount, 2) }}</span>
            </div>
            @endforeach
            @if($sale->paid_amount > $sale->total)
            <div class="payment-row">
                <span>الباقي:</span>
                <span class="bold">{{ number_format($sale->paid_amount - $sale->total, 2) }}</span>
            </div>
            @endif
        </div>
        @endif

        @if($sale->credit_amount > 0)
        <div class="center credit-warning">
            مبلغ آجل: {{ number_format($sale->credit_amount, 2) }} د.ل
        </div>
        @endif

        @if($sale->customer && $sale->credit_amount > 0)
        <div class="customer-box center">
            رصيد {{ $sale->customer->name }}: {{ number_format($sale->customer->current_balance, 2) }} د.ل
        </div>
        @endif

        @if($sale->notes)
        <div class="center notes-section">{{ $sale->notes }}</div>
        @endif

        <hr class="divider divider-bold">

        <div class="footer center">
            <div class="footer-message bold">شكراً لتسوقكم معنا</div>
        </div>

        <div class="barcode-section center">
            <svg id="barcode"></svg>
            <div class="barcode-text">{{ $sale->invoice_number }}</div>
        </div>

        <div class="timestamp center">{{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <script src="{{ asset('assets/plugins/jsbarcode/JsBarcode.all.min.js') }}"></script>
    <script>
        JsBarcode("#barcode", "{{ $sale->invoice_number }}", {
            format: "CODE128",
            width: 1.5,
            height: 30,
            displayValue: false,
            margin: 0
        });

        const urlParams = new URLSearchParams(window.location.search);

        if (window.opener) {
            window.opener = null;
        }

        if (urlParams.get('auto') == '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 300);
            };
        }

        window.onafterprint = function() {
            if (urlParams.get('close') == '1') {
                setTimeout(function() {
                    window.close();
                }, 100);
            }
        };
    </script>
</body>
</html>
