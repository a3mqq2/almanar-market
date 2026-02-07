@extends('layouts.app')

@section('title', 'لوحة التحكم')

@section('breadcrumb')
    <li class="breadcrumb-item active">لوحة التحكم</li>
@endsection

@push('styles')
<style>
    :root {
        --widget-bg: #ffffff;
        --widget-border: #e5e7eb;
    }
    [data-bs-theme="dark"] {
        --widget-bg: #1e1e1e;
        --widget-border: #2d2d2d;
    }

    .stat-widget {
        background: var(--widget-bg);
        border: 1px solid var(--widget-border);
        border-radius: 10px;
        padding: 1.25rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    .stat-widget .widget-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-widget .widget-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin: 0.5rem 0 0.25rem;
    }
    .stat-widget .widget-label {
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
        margin-bottom: 0;
    }
    .stat-widget .widget-change {
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    .stat-widget .sparkline-container {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 50px;
        opacity: 0.15;
    }

    .widget-icon.primary { background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary); }
    .widget-icon.success { background: rgba(var(--bs-success-rgb), 0.12); color: var(--bs-success); }
    .widget-icon.warning { background: rgba(var(--bs-warning-rgb), 0.12); color: var(--bs-warning); }
    .widget-icon.danger { background: rgba(var(--bs-danger-rgb), 0.12); color: var(--bs-danger); }
    .widget-icon.info { background: rgba(var(--bs-info-rgb), 0.12); color: var(--bs-info); }

    .chart-card {
        background: var(--widget-bg);
        border: 1px solid var(--widget-border);
        border-radius: 10px;
        height: 100%;
    }
    .chart-card .card-header {
        background: transparent;
        border-bottom: 1px solid var(--widget-border);
        padding: 1rem 1.25rem;
    }
    .chart-card .card-body {
        padding: 1.25rem;
    }
    .chart-card .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        color: var(--bs-body-color);
    }

    .table-widget {
        background: var(--widget-bg);
        border: 1px solid var(--widget-border);
        border-radius: 10px;
    }
    .table-widget .table {
        margin: 0;
    }
    .table-widget .table th {
        background: var(--bs-tertiary-bg);
        font-weight: 600;
        font-size: 0.8rem;
        color: var(--bs-secondary-color);
        text-transform: uppercase;
        padding: 0.75rem 1rem;
        border: none;
    }
    .table-widget .table td {
        padding: 0.75rem 1rem;
        vertical-align: middle;
        border-color: var(--widget-border);
        color: var(--bs-body-color);
    }

    .welcome-widget {
        background: var(--widget-bg);
        border: 1px solid var(--widget-border);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    .welcome-widget .greeting {
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
        margin-bottom: 0;
    }
    .welcome-widget .user-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin-bottom: 0;
    }
    .welcome-widget .date-time {
        display: flex;
        gap: 1.5rem;
        font-size: 0.85rem;
        color: var(--bs-secondary-color);
    }

    .loading-placeholder {
        background: linear-gradient(90deg, var(--bs-tertiary-bg) 25%, var(--bs-secondary-bg) 50%, var(--bs-tertiary-bg) 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .low-stock-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--widget-border);
    }
    .low-stock-item:last-child {
        border-bottom: none;
    }
    .low-stock-item .item-name {
        font-size: 0.9rem;
        color: var(--bs-body-color);
    }
    .low-stock-item .item-stock {
        font-size: 0.85rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="welcome-widget">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="greeting">مرحباً بك،</div>
                        <div class="user-name">{{ Auth::user()->name }}</div>
                    </div>
                    <div class="date-time">
                        <span><i class="ti ti-calendar me-1"></i><span id="current-date"></span></span>
                        <span><i class="ti ti-clock me-1"></i><span id="current-time"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-sales">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon primary">
                            <i class="ti ti-currency-dollar"></i>
                        </div>
                        <div class="widget-value" id="sales-value">-</div>
                        <p class="widget-label">مبيعات اليوم</p>
                        <div class="widget-change" id="sales-change"></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted fs-sm" id="invoices-count">-</div>
                        <small class="text-muted">فاتورة</small>
                    </div>
                </div>
                <div class="sparkline-container">
                    <div id="sales-sparkline"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-cashbox">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon info">
                            <i class="ti ti-building-bank"></i>
                        </div>
                        <div class="widget-value" id="cashbox-value">-</div>
                        <p class="widget-label">رصيد الخزينة</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-inventory">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon warning">
                            <i class="ti ti-alert-triangle"></i>
                        </div>
                        <div class="widget-value" id="low-stock-value">-</div>
                        <p class="widget-label">مخزون منخفض</p>
                    </div>
                    <div class="text-end">
                        <div class="text-danger fs-sm fw-bold" id="out-stock-value">-</div>
                        <small class="text-muted">نفد المخزون</small>
                    </div>
                </div>
            </div>
        </div>

        @if(Auth::user()->role === 'manager')
        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-profit">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon success">
                            <i class="ti ti-trending-up"></i>
                        </div>
                        <div class="widget-value" id="profit-value">-</div>
                        <p class="widget-label">صافي الربح</p>
                        <div class="widget-change" id="profit-margin"></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted fs-sm" id="gross-profit">-</div>
                        <small class="text-muted">إجمالي الربح</small>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="col-xl-3 col-md-6"></div>
        @endif
    </div>

    @if(Auth::user()->role === 'manager')
    <div class="row g-3 mb-3">
        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-expenses">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon danger">
                            <i class="ti ti-receipt-2"></i>
                        </div>
                        <div class="widget-value" id="expenses-value">-</div>
                        <p class="widget-label">مصروفات اليوم</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-customer-debts">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon warning">
                            <i class="ti ti-users"></i>
                        </div>
                        <div class="widget-value" id="customer-debts-value">-</div>
                        <p class="widget-label">ديون الزبائن</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted fs-sm" id="customer-debts-count">-</div>
                        <small class="text-muted">زبون</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-widget" id="widget-supplier-debts">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="widget-icon danger">
                            <i class="ti ti-truck"></i>
                        </div>
                        <div class="widget-value" id="supplier-debts-value">-</div>
                        <p class="widget-label">ديون الموردين</p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted fs-sm" id="supplier-debts-count">-</div>
                        <small class="text-muted">مورد</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6"></div>
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">المبيعات اليومية</h5>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary active" data-chart-period="daily">اليوم</button>
                        <button type="button" class="btn btn-outline-secondary" data-chart-period="weekly">الأسبوع</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="sales-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="chart-card">
                <div class="card-header">
                    <h5 class="card-title">طرق الدفع اليوم</h5>
                </div>
                <div class="card-body">
                    <div id="payment-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="table-widget">
                <div class="card-header d-flex justify-content-between align-items-center p-3">
                    <h5 class="card-title m-0">أكثر المنتجات مبيعاً (هذا الشهر)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-end">المبلغ</th>
                            </tr>
                        </thead>
                        <tbody id="top-products-body">
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">جاري التحميل...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="table-widget">
                <div class="card-header d-flex justify-content-between align-items-center p-3">
                    <h5 class="card-title m-0">آخر الفواتير</h5>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">عرض الكل</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>الزبون</th>
                                <th class="text-end">المبلغ</th>
                                <th>الوقت</th>
                            </tr>
                        </thead>
                        <tbody id="recent-sales-body">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">جاري التحميل...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isManager = {{ Auth::user()->role === 'manager' ? 'true' : 'false' }};
    let salesChart = null;
    let paymentChart = null;
    let currentPeriod = 'daily';

    function formatNumber(num) {
        return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0);
    }

    function formatInteger(num) {
        return new Intl.NumberFormat('en-US').format(num || 0);
    }

    function updateDateTime() {
        const now = new Date();
        document.getElementById('current-date').textContent = now.toLocaleDateString('ar-SA', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        document.getElementById('current-time').textContent = now.toLocaleTimeString('ar-SA', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function getChartColors() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            text: isDark ? '#adb5bd' : '#6c757d',
            grid: isDark ? '#2d2d2d' : '#e9ecef',
            primary: '#0d6efd',
            success: '#198754',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#0dcaf0',
        };
    }

    function loadAllStats() {
        fetch('/dashboard/stats/all', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSalesWidget(data.data.sales);
                updateInventoryWidget(data.data.inventory);
                updateCashboxWidget(data.data.cashbox);
                updatePaymentWidget(data.data.payment_methods);

                if (data.is_manager) {
                    if (data.data.profit) updateProfitWidget(data.data.profit);
                    if (data.data.expenses) updateExpensesWidget(data.data.expenses);
                    if (data.data.debts) updateDebtsWidget(data.data.debts);
                }
            }
        })
        .catch(error => console.error('Error loading stats:', error));
    }

    function updateSalesWidget(data) {
        document.getElementById('sales-value').textContent = formatNumber(data.today);
        document.getElementById('invoices-count').textContent = formatInteger(data.invoices);

        const changeEl = document.getElementById('sales-change');
        if (data.change_percent >= 0) {
            changeEl.innerHTML = `<i class="ti ti-arrow-up text-success"></i><span class="text-success">${data.change_percent}%</span><span class="text-muted ms-1">من أمس</span>`;
        } else {
            changeEl.innerHTML = `<i class="ti ti-arrow-down text-danger"></i><span class="text-danger">${Math.abs(data.change_percent)}%</span><span class="text-muted ms-1">من أمس</span>`;
        }

        if (data.sparkline && data.sparkline.length > 0) {
            renderSparkline('sales-sparkline', data.sparkline);
        }
    }

    function updateProfitWidget(data) {
        const profitEl = document.getElementById('profit-value');
        if (profitEl) {
            profitEl.textContent = formatNumber(data.net_profit);
            profitEl.className = 'widget-value ' + (data.net_profit >= 0 ? 'text-success' : 'text-danger');
        }
        const grossEl = document.getElementById('gross-profit');
        if (grossEl) grossEl.textContent = formatNumber(data.gross_profit);
        const marginEl = document.getElementById('profit-margin');
        if (marginEl) marginEl.innerHTML = `<span class="text-muted">هامش الربح: ${data.profit_margin}%</span>`;
    }

    function updateCashboxWidget(data) {
        document.getElementById('cashbox-value').textContent = formatNumber(data.total_balance);
    }

    function updateExpensesWidget(data) {
        const el = document.getElementById('expenses-value');
        if (el) el.textContent = formatNumber(data.today);
    }

    function updateInventoryWidget(data) {
        document.getElementById('low-stock-value').textContent = formatInteger(data.low_stock_count);
        document.getElementById('out-stock-value').textContent = formatInteger(data.out_of_stock_count);
    }

    function updateDebtsWidget(data) {
        const custDebtEl = document.getElementById('customer-debts-value');
        const custCountEl = document.getElementById('customer-debts-count');
        const suppDebtEl = document.getElementById('supplier-debts-value');
        const suppCountEl = document.getElementById('supplier-debts-count');

        if (custDebtEl) custDebtEl.textContent = formatNumber(data.customer_debts);
        if (custCountEl) custCountEl.textContent = formatInteger(data.customer_count);
        if (suppDebtEl) suppDebtEl.textContent = formatNumber(data.supplier_debts);
        if (suppCountEl) suppCountEl.textContent = formatInteger(data.supplier_count);
    }

    function updatePaymentWidget(data) {
        if (paymentChart) paymentChart.destroy();

        const colors = getChartColors();
        const chartColors = [colors.primary, colors.success, colors.warning, colors.danger, colors.info];

        if (data.methods && data.methods.length > 0) {
            const options = {
                series: data.methods.map(m => m.total),
                labels: data.methods.map(m => m.name),
                chart: {
                    type: 'donut',
                    height: 300,
                    background: 'transparent',
                },
                colors: chartColors.slice(0, data.methods.length),
                legend: {
                    position: 'bottom',
                    labels: { colors: colors.text }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) { return val.toFixed(1) + '%'; }
                },
                tooltip: {
                    y: { formatter: function(val) { return formatNumber(val); } }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'الإجمالي',
                                    color: colors.text,
                                    formatter: function(w) {
                                        return formatNumber(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                                    }
                                }
                            }
                        }
                    }
                },
                stroke: { show: false }
            };

            paymentChart = new ApexCharts(document.querySelector('#payment-chart'), options);
            paymentChart.render();
        } else {
            document.getElementById('payment-chart').innerHTML = '<div class="text-center text-muted py-5">لا توجد بيانات</div>';
        }
    }

    function renderSparkline(elementId, data) {
        const colors = getChartColors();
        const options = {
            series: [{ data: data }],
            chart: {
                type: 'area',
                height: 50,
                sparkline: { enabled: true },
                background: 'transparent',
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0, stops: [0, 100] }
            },
            colors: [colors.primary],
            tooltip: { enabled: false }
        };

        const chart = new ApexCharts(document.querySelector('#' + elementId), options);
        chart.render();
    }

    function loadSalesChart(period) {
        const url = period === 'daily' ? '/dashboard/stats/daily-chart' : '/dashboard/stats/weekly-chart';

        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderSalesChart(result.data, period);
            }
        })
        .catch(error => console.error('Error loading chart:', error));
    }

    function renderSalesChart(data, period) {
        if (salesChart) salesChart.destroy();

        const colors = getChartColors();

        let series, categories;

        if (period === 'daily') {
            series = [{ name: 'المبيعات', data: data.data }];
            categories = data.labels;
        } else {
            series = data.datasets.map(ds => ({ name: ds.name, data: ds.data }));
            categories = data.labels;
        }

        const options = {
            series: series,
            chart: {
                type: period === 'daily' ? 'area' : 'bar',
                height: 300,
                background: 'transparent',
                toolbar: { show: false },
                fontFamily: 'inherit',
            },
            colors: period === 'daily' ? [colors.primary] : [colors.primary, colors.warning],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: period === 'daily' ? 3 : 0 },
            fill: period === 'daily' ? {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1, stops: [0, 100] }
            } : {},
            xaxis: {
                categories: categories,
                labels: { style: { colors: colors.text } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: colors.text },
                    formatter: function(val) { return formatNumber(val); }
                }
            },
            grid: {
                borderColor: colors.grid,
                strokeDashArray: 3
            },
            tooltip: {
                theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light',
                y: { formatter: function(val) { return formatNumber(val); } }
            },
            legend: {
                labels: { colors: colors.text },
                position: 'top'
            }
        };

        salesChart = new ApexCharts(document.querySelector('#sales-chart'), options);
        salesChart.render();
    }

    function loadTopProducts() {
        fetch('/dashboard/stats/top-products?t=' + Date.now(), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data && result.data.length > 0) {
                const tbody = document.getElementById('top-products-body');
                tbody.innerHTML = result.data.map(p => `
                    <tr>
                        <td>${p.name || '-'}</td>
                        <td class="text-center">${formatInteger(p.quantity)}</td>
                        <td class="text-end">${formatNumber(p.total)}</td>
                    </tr>
                `).join('');
            } else {
                document.getElementById('top-products-body').innerHTML = '<tr><td colspan="3" class="text-center py-4 text-muted">لا توجد بيانات</td></tr>';
            }
        })
        .catch(error => console.error('Error loading top products:', error));
    }

    function loadRecentSales() {
        fetch('/dashboard/stats/recent-sales', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data.length > 0) {
                const tbody = document.getElementById('recent-sales-body');
                tbody.innerHTML = result.data.map(s => `
                    <tr>
                        <td><a href="/sales/${s.id}" class="text-primary">${s.invoice_number}</a></td>
                        <td>${s.customer}</td>
                        <td class="text-end">${formatNumber(s.total)}</td>
                        <td class="text-muted">${s.created_at}</td>
                    </tr>
                `).join('');
            } else {
                document.getElementById('recent-sales-body').innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">لا توجد فواتير</td></tr>';
            }
        })
        .catch(error => console.error('Error loading recent sales:', error));
    }

    document.querySelectorAll('[data-chart-period]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-chart-period]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = this.dataset.chartPeriod;
            loadSalesChart(currentPeriod);
        });
    });

    const themeObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'data-bs-theme') {
                loadSalesChart(currentPeriod);
                loadAllStats();
            }
        });
    });
    themeObserver.observe(document.documentElement, { attributes: true });

    updateDateTime();
    setInterval(updateDateTime, 60000);

    loadAllStats();
    loadSalesChart(currentPeriod);
    loadTopProducts();
    loadRecentSales();

    setInterval(function() {
        loadAllStats();
        loadSalesChart(currentPeriod);
    }, 60000);
});
</script>
@endpush
