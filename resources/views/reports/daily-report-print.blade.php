<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التقرير اليومي - {{ $date }}</title>
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
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #fff;
            direction: rtl;
        }

        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }

        .header {
            text-align: center;
            margin-bottom: 8mm;
            padding-bottom: 5mm;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 3mm;
        }

        .header .subtitle {
            font-size: 12px;
            color: #666;
        }

        .header .date-info {
            margin-top: 3mm;
            font-size: 13px;
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4mm;
            margin-bottom: 8mm;
        }

        .summary-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4mm;
            text-align: center;
        }

        .summary-box.highlight {
            background: #f8f9fa;
            border-color: #333;
        }

        .summary-box .label {
            font-size: 9px;
            color: #666;
            margin-bottom: 2mm;
        }

        .summary-box .value {
            font-size: 14px;
            font-weight: 700;
        }

        .summary-box .sub-value {
            font-size: 9px;
            color: #666;
            margin-top: 1mm;
        }

        .summary-box .value.positive { color: #28a745; }
        .summary-box .value.negative { color: #dc3545; }

        .section {
            margin-bottom: 8mm;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            padding: 3mm 0;
            border-bottom: 1px solid #333;
            margin-bottom: 4mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
            text-align: right;
            padding: 2.5mm 3mm;
            border: 1px solid #ddd;
        }

        table td {
            padding: 2mm 3mm;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        table tfoot td {
            background: #f8f9fa;
            font-weight: 600;
        }

        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: 600; }
        .text-positive { color: #28a745; }
        .text-negative { color: #dc3545; }
        .text-muted { color: #666; }

        .payment-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3mm;
        }

        .payment-box {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 3mm;
            text-align: center;
        }

        .payment-box .name {
            font-size: 9px;
            color: #666;
            margin-bottom: 1mm;
        }

        .payment-box .amount {
            font-size: 12px;
            font-weight: 700;
        }

        .payment-box .count {
            font-size: 8px;
            color: #999;
        }

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
        .status-expiring_soon { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }

        .footer {
            margin-top: 10mm;
            padding-top: 5mm;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #666;
        }

        .no-print {
            margin-bottom: 10mm;
            text-align: center;
        }

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

        .no-print button:hover {
            background: #0b5ed7;
        }

        .no-print button.secondary {
            background: #6c757d;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-container {
                padding: 0;
            }
        }

        @media screen {
            body {
                background: #eee;
                padding: 20px;
            }

            .print-container {
                background: #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                border-radius: 4px;
            }
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
            <h1>التقرير اليومي للمبيعات</h1>
            <div class="subtitle">تقرير شامل لأداء المتجر</div>
            <div class="date-info">
                التاريخ: {{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}
                @if($cashier)
                    | الكاشير: {{ $cashier->name }}
                @endif
            </div>
        </div>

        @php
            $summary = $reportData['summary'];
            $soldItems = $reportData['sold_items'];
            $paymentMethods = $reportData['payment_methods'];
            $inventory = $reportData['inventory_status'];
            $returns = $reportData['returns'];
        @endphp

        <div class="summary-grid">
            <div class="summary-box highlight">
                <div class="label">إجمالي المبيعات</div>
                <div class="value">{{ number_format($summary['total_sales'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">إجمالي التكلفة</div>
                <div class="value">{{ number_format($summary['total_cost'], 2) }}</div>
            </div>
            <div class="summary-box highlight">
                <div class="label">إجمالي الربح</div>
                <div class="value positive">{{ number_format($summary['total_profit'], 2) }}</div>
                <div class="sub-value">{{ $summary['profit_margin'] }}%</div>
            </div>
            <div class="summary-box">
                <div class="label">عدد الفواتير</div>
                <div class="value">{{ $summary['invoice_count'] }}</div>
                <div class="sub-value">{{ number_format($summary['items_count'], 2) }} قطعة</div>
            </div>
        </div>

        <div class="summary-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="summary-box">
                <div class="label">إجمالي الخصم</div>
                <div class="value">{{ number_format($summary['total_discount'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">متوسط الفاتورة</div>
                <div class="value">{{ number_format($summary['average_invoice'], 2) }}</div>
            </div>
            <div class="summary-box">
                <div class="label">المرتجعات</div>
                <div class="value negative">{{ number_format($returns['total_amount'], 2) }}</div>
                <div class="sub-value">{{ $returns['count'] }} عملية</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">طرق الدفع</div>
            <div class="payment-grid">
                @foreach($paymentMethods as $method)
                <div class="payment-box">
                    <div class="name">{{ $method['name'] }}</div>
                    <div class="amount">{{ number_format($method['total_amount'], 2) }}</div>
                    <div class="count">{{ $method['transaction_count'] }} عملية</div>
                </div>
                @endforeach
            </div>
        </div>

        @if(count($soldItems) > 0)
        <div class="section">
            <div class="section-title">الأصناف المباعة ({{ count($soldItems) }} صنف)</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">#</th>
                        <th>الصنف</th>
                        <th style="width: 70px;">الكمية</th>
                        <th style="width: 80px;">إجمالي البيع</th>
                        <th style="width: 80px;">التكلفة</th>
                        <th style="width: 80px;">الربح</th>
                        <th style="width: 50px;">الهامش</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($soldItems as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-left">{{ number_format($item['total_revenue'], 2) }}</td>
                        <td class="text-left">{{ number_format($item['total_cost'], 2) }}</td>
                        <td class="text-left {{ $item['profit'] >= 0 ? 'text-positive' : 'text-negative' }}">{{ number_format($item['profit'], 2) }}</td>
                        <td class="text-center">{{ $item['profit_margin'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right">المجموع</td>
                        <td class="text-left">{{ number_format($summary['total_sales'], 2) }}</td>
                        <td class="text-left">{{ number_format($summary['total_cost'], 2) }}</td>
                        <td class="text-left text-positive">{{ number_format($summary['total_profit'], 2) }}</td>
                        <td class="text-center">{{ $summary['profit_margin'] }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        @if(count($inventory) > 0)
        <div class="section">
            <div class="section-title">تنبيهات المخزون ({{ count($inventory) }} صنف)</div>
            <table>
                <thead>
                    <tr>
                        <th>الصنف</th>
                        <th style="width: 80px;">المخزون</th>
                        <th style="width: 100px;">تاريخ الانتهاء</th>
                        <th style="width: 100px;">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusLabels = [
                            'ok' => 'طبيعي',
                            'low_stock' => 'مخزون منخفض',
                            'out_of_stock' => 'نفد المخزون',
                            'expiring_soon' => 'قارب على الانتهاء',
                            'expired' => 'منتهي الصلاحية'
                        ];
                    @endphp
                    @foreach($inventory as $item)
                    <tr>
                        <td>{{ $item['product_name'] }}</td>
                        <td class="text-center">{{ $item['current_stock'] }}</td>
                        <td class="text-center">{{ $item['expiry_date'] ?? '-' }}</td>
                        <td class="text-center"><span class="status-badge status-{{ $item['status'] }}">{{ $statusLabels[$item['status']] ?? $item['status'] }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($returns['count'] > 0)
        <div class="section">
            <div class="section-title">المرتجعات ({{ $returns['count'] }} عملية)</div>
            <table>
                <thead>
                    <tr>
                        <th>رقم المرتجع</th>
                        <th>رقم الفاتورة</th>
                        <th>الصنف</th>
                        <th style="width: 60px;">الكمية</th>
                        <th style="width: 80px;">المبلغ</th>
                        <th>السبب</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returns['items'] as $item)
                    <tr>
                        <td>{{ $item['return_number'] }}</td>
                        <td>{{ $item['invoice_number'] }}</td>
                        <td>{{ $item['product_name'] }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-left text-negative">{{ number_format($item['amount'], 2) }}</td>
                        <td>{{ $item['reason'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right">إجمالي المرتجعات</td>
                        <td class="text-left text-negative">{{ number_format($returns['total_amount'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <div class="footer">
            <p>تم إنشاء هذا التقرير بتاريخ {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
