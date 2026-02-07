<div id="sidenav-menu">
    <div class="side-nav">
        <li class="side-nav-item">
            <a href="{{route('dashboard')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                <span class="menu-text">الرئيسية</span>
            </a>
        </li>

        <li class="side-nav-title">المبيعات</li>
        <li class="side-nav-item">
            <a href="{{route('pos.screen')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-cash-register"></i></span>
                <span class="menu-text">نقطة البيع</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('sales.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-receipt"></i></span>
                <span class="menu-text">الفواتير</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('customers.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-users"></i></span>
                <span class="menu-text">الزبائن</span>
            </a>
        </li>

        <li class="side-nav-title">المخزون</li>
        <li class="side-nav-item">
            <a href="{{route('products.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-package"></i></span>
                <span class="menu-text">الأصناف</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('purchases.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-file-invoice"></i></span>
                <span class="menu-text">المشتريات</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('suppliers.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-truck"></i></span>
                <span class="menu-text">الموردين</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('inventory-counts.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-clipboard-check"></i></span>
                <span class="menu-text">جرد المخزون</span>
            </a>
        </li>

        <li class="side-nav-title">المالية</li>
        <li class="side-nav-item">
            <a href="{{route('cashboxes.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-building-bank"></i></span>
                <span class="menu-text">الخزينة</span>
            </a>
        </li>
        @if(Auth::user()->role === 'manager')
        <li class="side-nav-item">
            <a href="{{route('expenses.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-receipt-2"></i></span>
                <span class="menu-text">المصروفات</span>
            </a>
        </li>
        @endif

        <li class="side-nav-title">التقارير</li>
        <li class="side-nav-item">
            <a href="{{route('reports.daily-report')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-report"></i></span>
                <span class="menu-text">التقرير اليومي</span>
            </a>
        </li>
        <li class="side-nav-item">
            <a href="{{route('reports.financial-trace')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-route"></i></span>
                <span class="menu-text">تتبع العمليات</span>
            </a>
        </li>
        @if(Auth::user()->role === 'manager')
        <li class="side-nav-item">
            <a href="{{route('reports.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-report-analytics"></i></span>
                <span class="menu-text">التقارير المتقدمة</span>
            </a>
        </li>
        @endif

        <li class="side-nav-title">الإعدادات</li>
        <li class="side-nav-item">
            <a href="{{route('users.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-user-cog"></i></span>
                <span class="menu-text">المستخدمين</span>
            </a>
        </li>
    </div>
</div>
