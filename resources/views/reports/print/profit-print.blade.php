<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الأرباح</title>
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
        .summary-box .value.positive { color: #28a745; }
        .summary-box .value.negative { color: #dc3545; }
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
        table tfoot td { background: #f8f9fa; font-weight: 600; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-positive { color: #28a745; }
        .text-negative { color: #dc3545; }
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
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()">طباعة التقرير</button>
            <button class="secondary" onclick="window.close()">إغلاق</button>
        </div>

        <div class="header">
            <h1>تقرير الأرباح</h1>
            <div class="subtitle">تحليل الأرباح والخسائر</div>
            <div class="date-info">
                الفترة: {{ $reportData['period']['from'] }} إلى {{ $reportData['period']['to'] }}
            </div>
        </div>

        @php $summary = $reportData['summary']; @endphp

        <div class="summary-grid">
            <div class="summary-box">
                <div class="label">الإيرادات</div>
                <div class="value">{{ number_format($summary['revenue'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">التكلفة</div>
                <div class="value">{{ number_format($summary['cost'], 2) }}</div>
            </div>
            <div class="summary-box highlight">
                <div class="label">الربح الإجمالي</div>
                <div class="value positive">{{ number_format($summary['gross_profit'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">المصروفات</div>
                <div class="value negative">{{ number_format($summary['expenses'], 2) }}</div>
            </div>
            <div class="summary-box highlight">
                <div class="label">صافي الربح</div>
                <div class="value {{ $summary['net_profit'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($summary['net_profit'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">هامش الربح</div>
                <div class="value">{{ number_format($summary['gross_margin'], 2) }}%</div>
            </div>
        </div>

        <div class="two-columns">
            @if(!empty($reportData['by_date']))
            <div class="section">
                <div class="section-title">الأرباح حسب التاريخ</div>
                <table>
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الإيرادات</th>
                            <th>المصروفات</th>
                            <th>الربح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['by_date'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td class="text-left">{{ number_format($row['revenue'], 2) }}</td>
                            <td class="text-left">{{ number_format($row['expenses'], 2) }}</td>
                            <td class="text-left {{ $row['profit'] >= 0 ? 'text-positive' : 'text-negative' }}">{{ number_format($row['profit'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if(!empty($reportData['by_product']))
            <div class="section">
                <div class="section-title">أكثر المنتجات ربحية</div>
                <table>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الكمية</th>
                            <th>الربح</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['by_product'] as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td class="text-center">{{ number_format($row['quantity']) }}</td>
                            <td class="text-left text-positive">{{ number_format($row['profit'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        @if(!empty($reportData['expenses_by_category']))
        @php
            $totalExpenses = collect($reportData['expenses_by_category'])->sum('total');
        @endphp
        <div class="section">
            <div class="section-title">المصروفات حسب التصنيف</div>
            <table>
                <thead>
                    <tr>
                        <th>التصنيف</th>
                        <th>العدد</th>
                        <th>المبلغ</th>
                        <th>النسبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['expenses_by_category'] as $row)
                    <tr>
                        <td>{{ $row['category'] }}</td>
                        <td class="text-center">{{ number_format($row['count']) }}</td>
                        <td class="text-left text-negative">{{ number_format($row['total'], 2) }}</td>
                        <td class="text-center">{{ $totalExpenses > 0 ? number_format(($row['total'] / $totalExpenses) * 100, 1) : 0 }}%</td>
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
