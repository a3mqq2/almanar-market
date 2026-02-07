<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة استرجاع {{ $return->return_number }}</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', 'Lucida Console', Monaco, monospace;
        }

        body {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 2mm;
            font-size: 12px;
            line-height: 1.3;
            color: #000;
            background: #fff;
            direction: rtl;
        }

        body.width-58mm {
            width: 58mm;
            max-width: 58mm;
            font-size: 10px;
        }

        .receipt {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .left {
            text-align: left;
        }

        .bold {
            font-weight: bold;
        }

        .store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        body.width-58mm .store-name {
            font-size: 14px;
        }

        .store-info {
            font-size: 10px;
            margin-bottom: 2mm;
        }

        body.width-58mm .store-info {
            font-size: 9px;
        }

        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .divider-double {
            border-top: 2px solid #000;
        }

        .return-title {
            font-size: 16px;
            font-weight: bold;
            padding: 2mm;
            border: 2px solid #000;
            margin: 2mm 0;
        }

        body.width-58mm .return-title {
            font-size: 14px;
        }

        .return-number {
            font-size: 14px;
            font-weight: bold;
            padding: 1mm 0;
        }

        body.width-58mm .return-number {
            font-size: 12px;
        }

        .info-table {
            width: 100%;
            font-size: 11px;
        }

        body.width-58mm .info-table {
            font-size: 9px;
        }

        .info-table td {
            padding: 0.5mm 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }

        .items-table th {
            font-size: 10px;
            font-weight: bold;
            padding: 1mm 0;
            border-bottom: 1px solid #000;
            text-align: right;
        }

        body.width-58mm .items-table th {
            font-size: 9px;
        }

        .items-table th:last-child {
            text-align: left;
        }

        .items-table td {
            font-size: 11px;
            padding: 1mm 0;
            vertical-align: top;
            border-bottom: 1px dotted #ccc;
        }

        body.width-58mm .items-table td {
            font-size: 9px;
        }

        .items-table .item-name {
            max-width: 35mm;
            word-wrap: break-word;
        }

        body.width-58mm .items-table .item-name {
            max-width: 25mm;
        }

        .items-table .item-qty {
            text-align: center;
            font-size: 10px;
        }

        body.width-58mm .items-table .item-qty {
            font-size: 8px;
        }

        .items-table .item-total {
            text-align: left;
            font-weight: bold;
        }

        .total-box {
            border: 2px solid #000;
            padding: 2mm;
            margin: 2mm 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            font-weight: bold;
        }

        body.width-58mm .total-row {
            font-size: 12px;
        }

        .refund-info {
            margin: 2mm 0;
            padding: 1mm;
            border: 1px dashed #000;
            font-size: 10px;
        }

        body.width-58mm .refund-info {
            font-size: 9px;
        }

        .reason-box {
            margin: 2mm 0;
            padding: 1mm;
            font-size: 10px;
        }

        body.width-58mm .reason-box {
            font-size: 9px;
        }

        .footer {
            margin-top: 3mm;
            padding-top: 2mm;
        }

        .footer-message {
            font-size: 11px;
            margin: 1mm 0;
        }

        body.width-58mm .footer-message {
            font-size: 9px;
        }

        .barcode-section {
            margin: 3mm 0;
        }

        .barcode-section svg {
            max-width: 100%;
            height: auto;
        }

        .barcode-text {
            font-size: 10px;
            letter-spacing: 1px;
            margin-top: 1mm;
        }

        .timestamp {
            font-size: 8px;
            color: #666;
            margin-top: 2mm;
        }

        @media print {
            body {
                width: 80mm;
                max-width: 80mm;
            }

            body.width-58mm {
                width: 58mm;
                max-width: 58mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: auto;
                margin: 0;
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

        .print-controls .btn-width {
            background: #007bff;
            color: white;
        }

        .print-controls .btn-close {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body class="width-80mm">
    <div class="print-controls no-print">
        <button class="btn-print" onclick="printReceipt()">طباعة</button>
        <button class="btn-width" onclick="toggleWidth()">تبديل 58mm/80mm</button>
        <button class="btn-close" onclick="window.close()">إغلاق</button>
    </div>

    <div class="receipt">
        <div class="center store-name">{{ config('app.store_name', 'المنار ماركت') }}</div>

        <div class="center store-info">
            <div>{{ config('app.store_address', 'طرابلس - ليبيا') }}</div>
            <div>هاتف: {{ config('app.store_phone', '091-1234567') }}</div>
        </div>

        <hr class="divider divider-double">

        <div class="center return-title">فاتورة استرجاع</div>

        <div class="center return-number">{{ $return->return_number }}</div>

        <hr class="divider">

        <table class="info-table">
            <tr>
                <td>التاريخ:</td>
                <td class="left">{{ $return->return_date->format('Y/m/d') }}</td>
            </tr>
            <tr>
                <td>الوقت:</td>
                <td class="left">{{ $return->created_at->format('H:i') }}</td>
            </tr>
            <tr>
                <td>الكاشير:</td>
                <td class="left">{{ $return->creator?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>الفاتورة الأصلية:</td>
                <td class="left">{{ $return->sale->invoice_number }}</td>
            </tr>
        </table>

        <hr class="divider">

        <table class="items-table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>الكمية × السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $item)
                <tr>
                    <td class="item-name">{{ $item->product->name }}</td>
                    <td class="item-qty">{{ number_format($item->quantity, $item->quantity == floor($item->quantity) ? 0 : 2) }} × {{ number_format($item->unit_price, 2) }}</td>
                    <td class="item-total">{{ number_format($item->total_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <hr class="divider">

        <div class="total-box center">
            <div class="total-row">
                <span>إجمالي الاسترداد:</span>
                <span>{{ number_format($return->total_amount, 2) }} د.ل</span>
            </div>
        </div>

        <div class="refund-info">
            <div><strong>طريقة الاسترداد:</strong> {{ $return->refund_method_arabic }}</div>
            @if($return->cashbox)
            <div><strong>الخزينة:</strong> {{ $return->cashbox->name }}</div>
            @endif
        </div>

        <div class="reason-box">
            <div><strong>سبب الاسترجاع:</strong> {{ $return->reason_arabic }}</div>
            @if($return->reason_notes)
            <div>{{ $return->reason_notes }}</div>
            @endif
        </div>

        @if($return->customer)
        <hr class="divider">
        <div class="refund-info">
            <div><strong>الزبون:</strong> {{ $return->customer->name }}</div>
            <div><strong>الهاتف:</strong> {{ $return->customer->phone }}</div>
        </div>
        @endif

        @if($return->notes)
        <hr class="divider">
        <div class="center" style="font-size: 10px;">
            <strong>ملاحظات:</strong> {{ $return->notes }}
        </div>
        @endif

        <hr class="divider divider-double">

        <div class="footer center">
            <div class="footer-message bold">شكراً لتعاملكم معنا</div>
        </div>

        <div class="barcode-section center">
            <svg id="barcode"></svg>
            <div class="barcode-text">{{ $return->return_number }}</div>
        </div>

        <div class="timestamp center">
            {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode("#barcode", "{{ $return->return_number }}", {
            format: "CODE128",
            width: 1.5,
            height: 40,
            displayValue: false,
            margin: 0
        });

        function printReceipt() {
            window.print();
        }

        function toggleWidth() {
            document.body.classList.toggle('width-58mm');
            document.body.classList.toggle('width-80mm');
        }

        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.get('width') === '58') {
            document.body.classList.remove('width-80mm');
            document.body.classList.add('width-58mm');
        }

        if (urlParams.get('auto') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 300);
            };
        }

        window.onafterprint = function() {
            if (urlParams.get('close') === '1') {
                window.close();
            }
        };
    </script>
</body>
</html>
