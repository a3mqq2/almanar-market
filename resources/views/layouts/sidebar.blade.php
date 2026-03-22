<div id="sidenav-menu">
    <div class="side-nav">
        <li class="side-nav-item">
            <a href="{{route('dashboard')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                <span class="menu-text">الرئيسية</span>
            </a>
        </li>

        @if(Auth::id() === 1 || Auth::user()->username === 'admin')
        <li class="side-nav-item">
            <a href="{{route('pos.screen')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-cash-register"></i></span>
                <span class="menu-text">نقطة البيع</span>
            </a>
        </li>
        @endif

        <li class="side-nav-title">المبيعات</li>
   
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('sales'))
        <li class="side-nav-item">
            <a href="{{route('sales.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-receipt"></i></span>
                <span class="menu-text">الفواتير</span>
            </a>
        </li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('customers'))
        <li class="side-nav-item">
            <a href="{{route('customers.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-users"></i></span>
                <span class="menu-text">الزبائن</span>
            </a>
        </li>
        @endif

        @if(Auth::user()->isManager() && Auth::user()->hasAnyPermission(['products', 'purchases', 'suppliers', 'inventory_counts']))
        <li class="side-nav-title">المخزون</li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('products'))
        <li class="side-nav-item">
            <a href="{{route('products.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-package"></i></span>
                <span class="menu-text">الأصناف</span>
            </a>
        </li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('purchases'))
        <li class="side-nav-item">
            <a href="{{route('purchases.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-file-invoice"></i></span>
                <span class="menu-text">المشتريات</span>
            </a>
        </li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('suppliers'))
        <li class="side-nav-item">
            <a href="{{route('suppliers.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-truck"></i></span>
                <span class="menu-text">الموردين</span>
            </a>
        </li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('inventory_counts'))
        <li class="side-nav-item">
            <a href="{{route('inventory-counts.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-clipboard-check"></i></span>
                <span class="menu-text">جرد المخزون</span>
            </a>
        </li>
        @endif

        @if(Auth::user()->isManager() && Auth::user()->hasAnyPermission(['cashboxes', 'expenses']))
        <li class="side-nav-title">المالية</li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('cashboxes'))
        <li class="side-nav-item">
            <a href="{{route('cashboxes.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-building-bank"></i></span>
                <span class="menu-text">الخزينة</span>
            </a>
        </li>
        @endif
        @if(Auth::user()->isManager() && Auth::user()->hasPermission('expenses'))
        <li class="side-nav-item">
            <a href="{{route('expenses.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-receipt-2"></i></span>
                <span class="menu-text">المصروفات</span>
            </a>
        </li>
        @endif

        @if(Auth::user()->isManager() && Auth::user()->hasPermission('reports'))
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
        <li class="side-nav-item">
            <a href="{{route('reports.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-report-analytics"></i></span>
                <span class="menu-text">التقارير المتقدمة</span>
            </a>
        </li>
        @endif

        @if(Auth::user()->isManager() && Auth::user()->hasPermission('users'))
        <li class="side-nav-title">الإعدادات</li>
        <li class="side-nav-item">
            <a href="{{route('users.index')}}" class="side-nav-link">
                <span class="menu-icon"><i class="ti ti-user-cog"></i></span>
                <span class="menu-text">المستخدمين</span>
            </a>
        </li>
        @endif
    </div>
</div>
