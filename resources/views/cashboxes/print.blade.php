<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب خزينة - {{ $cashbox->name }}</title>
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
        .print-info {
            font-size: 10px;
            color: #555;
        }
        .cashbox-section {
            display: flex;
            justify-content: space-between;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 12px;
            margin-bottom: 20px;
        }
        .cashbox-info, .period-info {
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
        .summary-section {
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 8px 12px;
            text-align: center;
        }
        .summary-table th {
            background: #eee;
            font-weight: bold;
        }
        .summary-table .amount {
            font-weight: bold;
            font-size: 13px;
        }
        .summary-table .in-amount {
            color: #060;
        }
        .summary-table .out-amount {
            color: #c00;
        }
        .ledger-section {
            margin-bottom: 20px;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table th,
        .ledger-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }
        .ledger-table th {
            background: #eee;
            font-weight: bold;
            text-align: center;
        }
        .ledger-table td {
            font-size: 11px;
        }
        .ledger-table .col-num {
            width: 40px;
            text-align: center;
        }
        .ledger-table .col-date {
            width: 80px;
            text-align: center;
        }
        .ledger-table .col-type {
            width: 80px;
            text-align: center;
        }
        .ledger-table .col-desc {
            text-align: right;
        }
        .ledger-table .col-amount {
            width: 90px;
            text-align: left;
            direction: ltr;
        }
        .ledger-table .col-balance {
            width: 100px;
            text-align: left;
            direction: ltr;
            font-weight: bold;
        }
        .ledger-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        .ledger-table .in-amount {
            color: #060;
        }
        .ledger-table .out-amount {
            color: #c00;
        }
        .ledger-table .opening-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        .ledger-table tfoot td {
            background: #eee;
            font-weight: bold;
        }
        .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .type-in {
            background: #d4edda;
            color: #155724;
        }
        .type-out {
            background: #f8d7da;
            color: #721c24;
        }
        .type-transfer-in {
            background: #cce5ff;
            color: #004085;
        }
        .type-transfer-out {
            background: #fff3cd;
            color: #856404;
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
            width: 45%;
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
            .ledger-table {
                page-break-inside: auto;
            }
            .ledger-table thead {
                display: table-header-group;
            }
            .ledger-table tr {
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
            <div class="print-info">
                تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}<br>
                طُبع بواسطة: {{ auth()->user()->name ?? '-' }}
            </div>
        </div>
    </div>

    <div class="document-title">كشف حساب خزينة</div>

    <div class="cashbox-section">
        <div class="cashbox-info">
            <div class="section-title">بيانات الخزينة</div>
            <div class="info-row">
                <span class="info-label">اسم الخزينة:</span>
                <span class="info-value">{{ $cashbox->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">الحالة:</span>
                <span class="info-value">{{ $cashbox->status ? 'نشطة' : 'غير نشطة' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">رقم الخزينة:</span>
                <span class="info-value">{{ $cashbox->id }}</span>
            </div>
        </div>
        <div class="period-info">
            <div class="section-title">فترة التقرير</div>
            <div class="info-row">
                <span class="info-label">من تاريخ:</span>
                <span class="info-value">{{ $dateFrom ?? 'بداية الحساب' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">إلى تاريخ:</span>
                <span class="info-value">{{ $dateTo ?? now()->format('Y-m-d') }}</span>
            </div>
        </div>
    </div>

    <div class="summary-section">
        <div class="section-title">ملخص الحساب</div>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>الرصيد الافتتاحي</th>
                    <th>إجمالي الوارد</th>
                    <th>إجمالي الصادر</th>
                    <th>الرصيد الختامي</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="amount">{{ number_format($openingBalance, 2) }}</td>
                    <td class="amount in-amount">{{ number_format($totalIn, 2) }}</td>
                    <td class="amount out-amount">{{ number_format($totalOut, 2) }}</td>
                    <td class="amount">{{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="ledger-section">
        <div class="section-title">تفاصيل الحركات ({{ $transactions->count() }} حركة)</div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th class="col-num">#</th>
                    <th class="col-date">التاريخ</th>
                    <th class="col-type">النوع</th>
                    <th class="col-desc">البيان</th>
                    <th class="col-amount">وارد</th>
                    <th class="col-amount">صادر</th>
                    <th class="col-balance">الرصيد</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-row">
                    <td class="col-num">-</td>
                    <td class="col-date">{{ $dateFrom ?? $cashbox->created_at->format('Y-m-d') }}</td>
                    <td class="col-type">-</td>
                    <td class="col-desc">رصيد افتتاحي</td>
                    <td class="col-amount">-</td>
                    <td class="col-amount">-</td>
                    <td class="col-balance">{{ number_format($openingBalance, 2) }}</td>
                </tr>
                @foreach($transactions as $index => $transaction)
                @php
                    $typeClass = match($transaction->type) {
                        'in' => 'type-in',
                        'out' => 'type-out',
                        'transfer_in' => 'type-transfer-in',
                        'transfer_out' => 'type-transfer-out',
                        default => ''
                    };
                    $typeArabic = match($transaction->type) {
                        'in' => 'إيداع',
                        'out' => 'سحب',
                        'transfer_in' => 'تحويل وارد',
                        'transfer_out' => 'تحويل صادر',
                        default => $transaction->type
                    };
                    $isIn = in_array($transaction->type, ['in', 'transfer_in']);
                @endphp
                <tr>
                    <td class="col-num">{{ $index + 1 }}</td>
                    <td class="col-date">{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                    <td class="col-type"><span class="type-badge {{ $typeClass }}">{{ $typeArabic }}</span></td>
                    <td class="col-desc">{{ $transaction->description }}</td>
                    <td class="col-amount in-amount">{{ $isIn ? number_format($transaction->amount, 2) : '-' }}</td>
                    <td class="col-amount out-amount">{{ !$isIn ? number_format($transaction->amount, 2) : '-' }}</td>
                    <td class="col-balance">{{ number_format($transaction->balance_after, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: left;">الإجمالي</td>
                    <td class="col-amount in-amount">{{ number_format($totalIn, 2) }}</td>
                    <td class="col-amount out-amount">{{ number_format($totalOut, 2) }}</td>
                    <td class="col-balance">{{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="page-footer">
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">توقيع المسؤول</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">توقيع المدير</div>
            </div>
        </div>
        <div class="legal-note">
            هذا الكشف صادر آلياً من النظام ولا يحتاج إلى توقيع إلا في حالة المصادقة الرسمية.
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
