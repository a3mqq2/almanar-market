<!DOCTYPE html>
<html lang="ar" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ rtrim(url('/'), '/') }}" />
    <title>جرد المخزون | {{ $inventoryCount->reference_number }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/vendors.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
    <style>
        :root {
            --header-height: 60px;
        }
        html, body {
            height: 100%;
            overflow: hidden;
        }
        .count-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: var(--bs-body-bg);
        }
        .count-header {
            height: var(--header-height);
            background: var(--bs-primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            flex-shrink: 0;
        }
        .count-header .logo {
            font-size: 1.1rem;
            font-weight: 600;
        }
        .count-main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        .count-products {
            width: 400px;
            background: var(--bs-card-bg, #fff);
            border-left: 1px solid var(--bs-border-color);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .products-header {
            padding: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .products-list {
            flex: 1;
            overflow-y: auto;
        }
        .product-item {
            padding: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        .product-item:hover {
            background: var(--bs-tertiary-bg);
        }
        .product-item.active {
            background: rgba(var(--bs-primary-rgb), 0.1);
            border-right: 3px solid var(--bs-primary);
        }
        .product-item.surplus {
            border-right: 3px solid var(--bs-success);
        }
        .product-item.shortage {
            border-right: 3px solid var(--bs-danger);
        }
        .product-item.match {
            border-right: 3px solid var(--bs-secondary);
        }
        .product-item .product-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .product-item .product-barcode {
            font-size: 0.8rem;
            color: var(--bs-secondary-color);
        }
        .count-entry {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .entry-panel {
            width: 100%;
            max-width: 500px;
        }
        .empty-state {
            text-align: center;
            color: var(--bs-secondary-color);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }
        .product-details {
            background: var(--bs-card-bg, #fff);
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            padding: 2rem;
        }
        .product-details .current-product-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .product-details .current-product-barcode {
            color: var(--bs-secondary-color);
            margin-bottom: 1.5rem;
        }
        .qty-display {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .qty-box {
            flex: 1;
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: var(--bs-tertiary-bg);
        }
        .qty-box label {
            display: block;
            font-size: 0.85rem;
            color: var(--bs-secondary-color);
            margin-bottom: 0.5rem;
        }
        .qty-box.system span {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .qty-box.counted input {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            border: 2px solid var(--bs-primary);
        }
        .variance-display {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            background: var(--bs-tertiary-bg);
        }
        .variance-display.surplus {
            background: rgba(var(--bs-success-rgb), 0.1);
            color: var(--bs-success);
        }
        .variance-display.shortage {
            background: rgba(var(--bs-danger-rgb), 0.1);
            color: var(--bs-danger);
        }
        .variance-display.match {
            background: rgba(var(--bs-secondary-rgb), 0.1);
        }
        .variance-display .label {
            font-size: 0.9rem;
        }
        .variance-display .value {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        .filter-tabs .btn {
            flex: 1;
            font-size: 0.8rem;
        }
        .filter-tabs .btn.active {
            background: var(--bs-primary);
            color: #fff;
        }
        .progress-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .progress-section .progress {
            background: rgba(255,255,255,0.2);
        }
        .progress-section .progress-bar {
            background: #fff;
            color: var(--bs-primary);
            font-weight: 600;
        }
        .numpad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .numpad .btn {
            padding: 1rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .count-products {
                width: 100%;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: 50%;
                z-index: 100;
                border-top: 1px solid var(--bs-border-color);
                border-left: none;
            }
            .count-entry {
                padding-bottom: 50%;
            }
        }

        /* Dark mode specific styles */
        [data-bs-theme="dark"] .count-container {
            background: #1a1d21;
        }
        [data-bs-theme="dark"] .count-products {
            background: #212529;
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .product-item {
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .product-item:hover {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .product-item.active {
            background: rgba(13, 110, 253, 0.15);
        }
        [data-bs-theme="dark"] .product-details {
            background: #212529;
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .qty-box {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .qty-box.counted input {
            background: #1a1d21;
            color: #e9ecef;
            border-color: #0d6efd;
        }
        [data-bs-theme="dark"] .variance-display {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .variance-display.surplus {
            background: rgba(25, 135, 84, 0.2);
        }
        [data-bs-theme="dark"] .variance-display.shortage {
            background: rgba(220, 53, 69, 0.2);
        }
        [data-bs-theme="dark"] .products-header {
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .form-control {
            background: #1a1d21;
            border-color: #373b3e;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .form-control:focus {
            background: #1a1d21;
            border-color: #0d6efd;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .current-product-name {
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .filter-tabs .btn {
            color: #adb5bd;
            border-color: #373b3e;
        }
        [data-bs-theme="dark"] .filter-tabs .btn:hover {
            background: #2b3035;
        }
        [data-bs-theme="dark"] .numpad .btn {
            background: #2b3035;
            border-color: #373b3e;
            color: #e9ecef;
        }
        [data-bs-theme="dark"] .numpad .btn:hover {
            background: #373b3e;
        }
    </style>
</head>
<body>
    <div class="count-container">
        <header class="count-header">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('inventory-counts.show', $inventoryCount) }}" class="btn btn-sm btn-outline-light">
                    <i class="ti ti-arrow-right"></i>
                </a>
                <span class="logo">{{ $inventoryCount->reference_number }}</span>
                <span class="badge bg-warning text-dark">جاري الجرد</span>
            </div>

            <div class="progress-section">
                <div class="progress" style="height: 24px; width: 200px;">
                    <div class="progress-bar" id="progressBar" style="width: 0%">
                        <span id="progressText">0 / 0</span>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light btn-sm" id="themeToggle">
                    <i class="ti ti-moon"></i>
                </button>
                <button class="btn btn-success btn-sm" id="completeBtn" disabled>
                    <i class="ti ti-check me-1"></i>إكمال الجرد
                </button>
            </div>
        </header>

        <div class="count-main">
            <div class="count-products">
                <div class="products-header">
                    <input type="text" id="searchInput" class="form-control" placeholder="بحث أو مسح الباركود..." autofocus>
                    <div class="filter-tabs">
                        <button class="btn btn-sm btn-outline-secondary active" data-filter="all">الكل</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="pending">غير مجرود</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="counted">تم الجرد</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="variance">فروقات</button>
                    </div>
                </div>
                <div class="products-list" id="productsList"></div>
            </div>

            <div class="count-entry">
                <div class="entry-panel">
                    <div class="empty-state" id="emptyState">
                        <i class="ti ti-scan d-block"></i>
                        <p>امسح الباركود أو اختر منتج من القائمة</p>
                    </div>

                    <div class="product-details d-none" id="productDetails">
                        <div class="current-product-name" id="currentProductName"></div>
                        <div class="current-product-barcode" id="currentProductBarcode"></div>

                        <div class="qty-display">
                            <div class="qty-box system">
                                <label>كمية النظام</label>
                                <span id="systemQty">0</span>
                            </div>
                            <div class="qty-box counted">
                                <label>الكمية المجرودة</label>
                                <input type="number" id="countedQty" class="form-control form-control-lg"
                                       step="0.01" min="0" inputmode="decimal">
                            </div>
                        </div>

                        <div class="variance-display" id="varianceDisplay">
                            <span class="label">الفرق:</span>
                            <span class="value" id="varianceValue">0</span>
                        </div>

                        <div class="mb-3">
                            <input type="text" id="itemNotes" class="form-control" placeholder="ملاحظات (اختياري)">
                        </div>

                        <button class="btn btn-lg btn-success w-100" id="saveCountBtn">
                            <i class="ti ti-check me-1"></i>حفظ والتالي
                        </button>

                        <div class="numpad d-none" id="numpad">
                            <button class="btn btn-outline-secondary" onclick="appendNum('1')">1</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('2')">2</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('3')">3</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('4')">4</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('5')">5</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('6')">6</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('7')">7</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('8')">8</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('9')">9</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('.')">.</button>
                            <button class="btn btn-outline-secondary" onclick="appendNum('0')">0</button>
                            <button class="btn btn-outline-danger" onclick="clearNum()"><i class="ti ti-backspace"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed top-0 start-0 p-3" id="toastContainer"></div>

    @php
        $itemsData = $inventoryCount->items->map(function($i) {
            return [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_name' => $i->product->name,
                'barcode' => $i->product->barcode,
                'unit_name' => $i->product->baseUnit?->unit?->name ?? 'وحدة',
                'system_qty' => floatval($i->system_qty),
                'system_cost' => floatval($i->system_cost),
                'counted_qty' => $i->counted_qty != null ? floatval($i->counted_qty) : null,
                'difference' => floatval($i->difference),
                'variance_status' => $i->variance_status,
                'notes' => $i->notes,
            ];
        });
    @endphp
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>
    <script>
        (function() {
            var base = document.querySelector('meta[name="base-url"]').getAttribute('content');
            var _fetch = window.fetch;
            window.fetch = function(url, opts) {
                if (typeof url === 'string' && url.startsWith('/')) { url = base + url; }
                return _fetch.call(this, url, opts);
            };
        })();
    </script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let currentItem = null;
        let items = @json($itemsData);
        let filter = 'all';

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0 show`;
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        function init() {
            renderProductList();
            updateProgress();
            setupEventListeners();
            initTheme();
        }

        function initTheme() {
            const config = JSON.parse(sessionStorage.getItem('__THEME_CONFIG__') || '{}');
            let theme = config.theme || 'light';
            if (theme == 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', theme);
            updateThemeIcon();
        }

        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('keypress', async (e) => {
                if (e.key == 'Enter') {
                    const value = e.target.value.trim();
                    if (value) {
                        await findByBarcode(value);
                        e.target.value = '';
                    }
                }
            });

            document.getElementById('searchInput').addEventListener('input', debounce(function() {
                const search = this.value.toLowerCase();
                renderProductList(search);
            }, 300));

            document.getElementById('saveCountBtn').addEventListener('click', saveCount);

            document.getElementById('countedQty').addEventListener('input', calculateVariance);

            document.getElementById('countedQty').addEventListener('keypress', (e) => {
                if (e.key == 'Enter') saveCount();
            });

            document.getElementById('completeBtn').addEventListener('click', confirmComplete);

            document.querySelectorAll('[data-filter]').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    filter = btn.dataset.filter;
                    renderProductList();
                });
            });

            document.getElementById('themeToggle').addEventListener('click', toggleTheme);
        }

        async function findByBarcode(barcode) {
            const item = items.find(i => i.barcode == barcode);
            if (item) {
                selectItem(item);
            } else {
                showToast('المنتج غير موجود في هذا الجرد', 'warning');
            }
        }

        function selectItem(item) {
            currentItem = item;

            document.getElementById('emptyState').classList.add('d-none');
            document.getElementById('productDetails').classList.remove('d-none');

            document.getElementById('currentProductName').textContent = item.product_name;
            document.getElementById('currentProductBarcode').textContent = item.barcode || 'بدون باركود';
            document.getElementById('systemQty').textContent = formatNumber(item.system_qty);
            document.getElementById('countedQty').value = item.counted_qty != null ? item.counted_qty : '';
            document.getElementById('itemNotes').value = item.notes || '';

            calculateVariance();
            document.getElementById('countedQty').focus();
            document.getElementById('countedQty').select();

            document.querySelectorAll('.product-item').forEach(el => {
                el.classList.toggle('active', parseInt(el.dataset.id) == item.id);
            });
        }

        function calculateVariance() {
            if (!currentItem) return;

            const countedQty = parseFloat(document.getElementById('countedQty').value) || 0;
            const systemQty = currentItem.system_qty;
            const difference = countedQty - systemQty;

            const varianceEl = document.getElementById('varianceValue');
            varianceEl.textContent = formatNumber(difference);

            const displayEl = document.getElementById('varianceDisplay');
            displayEl.classList.remove('surplus', 'shortage', 'match');

            if (difference > 0) {
                displayEl.classList.add('surplus');
            } else if (difference < 0) {
                displayEl.classList.add('shortage');
            } else {
                displayEl.classList.add('match');
            }
        }

        async function saveCount() {
            if (!currentItem) return;

            const countedQty = document.getElementById('countedQty').value;
            const notes = document.getElementById('itemNotes').value;

            if (countedQty == '') {
                showToast('يرجى إدخال الكمية المجرودة', 'warning');
                return;
            }

            const btn = document.getElementById('saveCountBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الحفظ...';

            try {
                const response = await fetch(`/inventory-counts/items/${currentItem.id}/count`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        counted_qty: parseFloat(countedQty),
                        notes: notes || null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    showToast('تم حفظ الجرد', 'success');

                    const index = items.findIndex(i => i.id == currentItem.id);
                    if (index != -1) {
                        items[index].counted_qty = data.item.counted_qty;
                        items[index].difference = data.item.difference;
                        items[index].variance_status = data.item.variance_status;
                    }

                    updateProgress(data.progress);
                    renderProductList();
                    moveToNextItem();
                } else {
                    showToast(data.message, 'danger');
                }
            } catch (error) {
                showToast('خطأ في الحفظ', 'danger');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>حفظ والتالي';
        }

        function moveToNextItem() {
            const uncounted = items.find(i => i.counted_qty == null);
            if (uncounted) {
                selectItem(uncounted);
            } else {
                document.getElementById('productDetails').classList.add('d-none');
                document.getElementById('emptyState').classList.remove('d-none');
                document.getElementById('emptyState').innerHTML = `
                    <i class="ti ti-circle-check text-success d-block"></i>
                    <p>تم جرد جميع المنتجات!</p>
                `;
                document.getElementById('completeBtn').disabled = false;
            }
        }

        function updateProgress(progress = null) {
            if (!progress) {
                const counted = items.filter(i => i.counted_qty != null).length;
                progress = {
                    counted_items: counted,
                    total_items: items.length,
                    percentage: items.length > 0 ? Math.round((counted / items.length) * 100) : 0,
                };
            }

            document.getElementById('progressBar').style.width = `${progress.percentage}%`;
            document.getElementById('progressText').textContent = `${progress.counted_items} / ${progress.total_items}`;
            document.getElementById('completeBtn').disabled = progress.counted_items < progress.total_items;
        }

        function renderProductList(search = '') {
            const listEl = document.getElementById('productsList');
            let filtered = items;

            switch (filter) {
                case 'pending':
                    filtered = items.filter(i => i.counted_qty == null);
                    break;
                case 'counted':
                    filtered = items.filter(i => i.counted_qty != null);
                    break;
                case 'variance':
                    filtered = items.filter(i => i.counted_qty != null && i.difference != 0);
                    break;
            }

            if (search) {
                filtered = filtered.filter(i =>
                    i.product_name.toLowerCase().includes(search) ||
                    (i.barcode && i.barcode.toLowerCase().includes(search))
                );
            }

            listEl.innerHTML = filtered.map(item => `
                <div class="product-item ${item.id == currentItem?.id ? 'active' : ''} ${getStatusClass(item)}"
                     data-id="${item.id}" onclick='selectItem(${JSON.stringify(item)})'>
                    <div>
                        <div class="product-name">${item.product_name}</div>
                        <div class="product-barcode">${item.barcode || 'بدون باركود'}</div>
                    </div>
                    <div>
                        ${item.counted_qty != null
                            ? `<span class="badge bg-${getStatusBadge(item)}">${formatNumber(item.counted_qty)}</span>`
                            : '<span class="badge bg-secondary">-</span>'}
                    </div>
                </div>
            `).join('');
        }

        function getStatusClass(item) {
            if (item.counted_qty == null) return '';
            if (item.difference > 0) return 'surplus';
            if (item.difference < 0) return 'shortage';
            return 'match';
        }

        function getStatusBadge(item) {
            if (item.difference > 0) return 'success';
            if (item.difference < 0) return 'danger';
            return 'secondary';
        }

        function formatNumber(num) {
            return parseFloat(num).toFixed(2).replace(/\.?0+$/, '');
        }

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        async function confirmComplete() {
            if (!confirm('سيتم إغلاق الجرد ولن يمكن تعديله. هل تريد المتابعة؟')) return;

            const btn = document.getElementById('completeBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>جاري الإكمال...';

            try {
                const response = await fetch('{{ route("inventory-counts.complete", $inventoryCount) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    showToast(data.message, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-check me-1"></i>إكمال الجرد';
                }
            } catch (error) {
                showToast('خطأ في إكمال الجرد', 'danger');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check me-1"></i>إكمال الجرد';
            }
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = current == 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-bs-theme', newTheme);

            let config = JSON.parse(sessionStorage.getItem('__THEME_CONFIG__') || '{}');
            config.theme = newTheme;
            sessionStorage.setItem('__THEME_CONFIG__', JSON.stringify(config));

            updateThemeIcon();
        }

        function updateThemeIcon() {
            const theme = document.documentElement.getAttribute('data-bs-theme');
            const icon = document.querySelector('#themeToggle i');
            icon.className = theme == 'dark' ? 'ti ti-sun' : 'ti ti-moon';
        }

        function appendNum(num) {
            const input = document.getElementById('countedQty');
            input.value += num;
            calculateVariance();
        }

        function clearNum() {
            const input = document.getElementById('countedQty');
            input.value = input.value.slice(0, -1);
            calculateVariance();
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>
