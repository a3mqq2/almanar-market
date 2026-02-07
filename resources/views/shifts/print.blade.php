<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير الورديات</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            padding: 20px;
            direction: rtl;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 14px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        .stat-item .label {
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px 8px;
            text-align: right;
            border: 1px solid #ddd;
        }
        th {
            background: #f0f0f0;
            font-weight: 600;
            font-size: 11px;
        }
        td {
            font-size: 11px;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        .text-warning { color: #ffc107; }
        .text-left { text-align: left; }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">طباعة</button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-right: 10px;">إغلاق</button>
    </div>

    <div class="header">
        <h1>تقرير الورديات</h1>
        <p>تاريخ التقرير: {{ date('Y/m/d H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-item">
            <div class="value">{{ number_format($stats['total_shifts']) }}</div>
            <div class="label">إجمالي الورديات</div>
        </div>
        <div class="stat-item">
            <div class="value">{{ number_format($stats['total_sales'], 2) }}</div>
            <div class="label">إجمالي المبيعات</div>
        </div>
        <div class="stat-item">
            <div class="value {{ $stats['total_difference'] > 0 ? 'text-success' : ($stats['total_difference'] < 0 ? 'text-danger' : '') }}">{{ number_format($stats['total_difference'], 2) }}</div>
            <div class="label">إجمالي الفروقات</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>الكاشير</th>
                <th>الجهاز</th>
                <th>الخزينة</th>
                <th>تاريخ الفتح</th>
                <th>تاريخ الإغلاق</th>
                <th>الحالة</th>
                <th class="text-left">المبيعات</th>
                <th class="text-left">الكاش</th>
                <th class="text-left">الفرق</th>
                <th>الحالة المالية</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shifts as $shift)
            <tr>
                <td>{{ $shift['رقم الشيفت'] }}</td>
                <td>{{ $shift['الكاشير'] }}</td>
                <td>{{ $shift['الجهاز'] }}</td>
                <td>{{ $shift['الخزينة'] }}</td>
                <td>{{ $shift['تاريخ الفتح'] }}</td>
                <td>{{ $shift['تاريخ الإغلاق'] }}</td>
                <td>{{ $shift['الحالة'] }}</td>
                <td class="text-left">{{ $shift['إجمالي المبيعات'] }}</td>
                <td class="text-left">{{ $shift['إجمالي الكاش'] }}</td>
                <td class="text-left {{ floatval(str_replace(',', '', $shift['الفرق'])) > 0 ? 'text-success' : (floatval(str_replace(',', '', $shift['الفرق'])) < 0 ? 'text-danger' : '') }}">{{ $shift['الفرق'] }}</td>
                <td>{{ $shift['الحالة المالية'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        تم إنشاء هذا التقرير بواسطة نظام نقاط البيع - {{ date('Y') }}
    </div>
</body>
</html>
