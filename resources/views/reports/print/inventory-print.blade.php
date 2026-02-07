<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير المخزون</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4; margin: 15mm; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            direction: rtl;
        }
        .print-container { max-width: 210mm; margin: 0 auto; padding: 10mm; }
        .header {
            text-align: center;
            margin-bottom: 8mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid #333;
        }
        .header h1 { font-size: 20px; font-weight: 700; margin-bottom: 3mm; }
        .header .subtitle { font-size: 12px; color: #666; }
        .header .date-info { margin-top: 3mm; font-size: 13px; font-weight: 600; }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 4mm;
            margin-bottom: 8mm;
        }
        .summary-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4mm;
            text-align: center;
        }
        .summary-box.highlight { background: #f8f9fa; border-color: #333; }
        .summary-box .label { font-size: 9px; color: #666; margin-bottom: 2mm; }
        .summary-box .value { font-size: 14px; font-weight: 700; }
        .summary-box .value.warning { color: #ffc107; }
        .summary-box .value.danger { color: #dc3545; }
        .section { margin-bottom: 8mm; page-break-inside: avoid; }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            padding: 3mm 0;
            border-bottom: 1px solid #333;
            margin-bottom: 4mm;
        }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        table th {
            background: #f8f9fa;
            font-weight: 600;
            text-align: right;
            padding: 2.5mm 3mm;
            border: 1px solid #ddd;
        }
        table td { padding: 2mm 3mm; border: 1px solid #ddd; vertical-align: middle; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .status-badge {
            display: inline-block;
            padding: 1mm 2mm;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 600;
        }
        .status-ok { background: #d4edda; color: #155724; }
        .status-low_stock { background: #fff3cd; color: #856404; }
        .status-out_of_stock { background: #f8d7da; color: #721c24; }
        .status-expiring { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .footer {
            margin-top: 10mm;
            padding-top: 5mm;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .no-print { margin-bottom: 10mm; text-align: center; }
        .no-print button {
            padding: 8px 24px;
            font-size: 14px;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }
        .no-print button.secondary { background: #6c757d; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media screen {
            body { background: #eee; padding: 20px; }
            .print-container { background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 4px; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()">طباعة التقرير</button>
            <button class="secondary" onclick="window.close()">إغلاق</button>
        </div>

        <div class="header">
            <h1>تقرير المخزون</h1>
            <div class="subtitle">حالة المخزون الحالية</div>
            <div class="date-info">
                تاريخ التقرير: {{ $reportData['generated_at'] }}
            </div>
        </div>

        @php
            $summary = $reportData['summary'];
            $statusLabels = [
                'ok' => 'متوفر',
                'low_stock' => 'منخفض',
                'out_of_stock' => 'نفد',
                'expiring' => 'قارب الانتهاء',
                'expired' => 'منتهي'
            ];
        @endphp

        <div class="summary-grid">
            <div class="summary-box highlight">
                <div class="label">إجمالي المنتجات</div>
                <div class="value">{{ number_format($summary['total_products']) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">قيمة المخزون</div>
                <div class="value">{{ number_format($summary['total_stock_value'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">مخزون منخفض</div>
                <div class="value warning">{{ number_format($summary['low_stock_count']) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">نفد المخزون</div>
                <div class="value danger">{{ number_format($summary['out_of_stock_count']) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">قارب الانتهاء</div>
                <div class="value warning">{{ number_format($summary['expiring_soon_count']) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">منتهي الصلاحية</div>
                <div class="value danger">{{ number_format($summary['expired_count']) }}</div>
            </div>
        </div>

        @if(!empty($reportData['products']))
        <div class="section">
            <div class="section-title">قائمة المنتجات ({{ count($reportData['products']) }} منتج)</div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>المنتج</th>
                        <th>الباركود</th>
                        <th>الكمية</th>
                        <th>التكلفة</th>
                        <th>السعر</th>
                        <th>القيمة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['products'] as $index => $row)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['barcode'] ?? '-' }}</td>
                        <td class="text-center">{{ number_format($row['stock']) }}</td>
                        <td class="text-left">{{ number_format($row['cost'], 2) }}</td>
                        <td class="text-left">{{ number_format($row['price'], 2) }}</td>
                        <td class="text-left">{{ number_format($row['stock_value'], 2) }}</td>
                        <td class="text-center">
                            <span class="status-badge status-{{ $row['status'] }}">{{ $statusLabels[$row['status']] ?? $row['status'] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="footer">
            <p>تم إنشاء هذا التقرير بتاريخ {{ $reportData['generated_at'] }}</p>
        </div>
    </div>
</body>
</html>
