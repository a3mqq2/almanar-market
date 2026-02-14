<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>جهاز الأسعار</title>
    <link href="{{ asset('assets/fonts/fonts.css') }}" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f5f5f5;
            --card-bg: #ffffff;
            --text-primary: #1a1a1a;
            --text-secondary: #666666;
            --border-color: #e0e0e0;
            --primary-color: #2563eb;
            --success-color: #16a34a;
            --error-color: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
            background: var(--bg-color);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: var(--card-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: var(--error-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .search-container {
            width: 100%;
            max-width: 600px;
            margin-bottom: 2rem;
        }

        .barcode-input {
            width: 100%;
            padding: 1.5rem 2rem;
            font-size: 2rem;
            font-family: 'Cairo', sans-serif;
            text-align: center;
            border: 3px solid var(--border-color);
            border-radius: 12px;
            outline: none;
            transition: border-color 0.2s;
        }

        .barcode-input:focus {
            border-color: var(--primary-color);
        }

        .barcode-input::placeholder {
            color: #aaa;
        }

        .result-container {
            width: 100%;
            max-width: 700px;
            min-height: 300px;
        }

        .result-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            display: none;
        }

        .result-card.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .product-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .price-main {
            font-size: 4rem;
            font-weight: 800;
            color: var(--success-color);
            margin-bottom: 0.5rem;
        }

        .price-unit {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .units-section {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
            margin-top: 1rem;
        }

        .units-title {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .units-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }

        .unit-item {
            background: var(--bg-color);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            min-width: 150px;
        }

        .unit-name {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .unit-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--error-color);
            padding: 2rem;
            border-radius: 12px;
            font-size: 1.5rem;
            text-align: center;
            display: none;
        }

        .error-message.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .idle-message {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.2rem;
        }

        .idle-message i {
            font-size: 4rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .footer {
            background: var(--card-bg);
            padding: 1rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .clock {
            font-size: 1rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <header class="header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <img src="{{ asset('logo-dark.png') }}" alt="Logo" style="height: 40px;">
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="clock" id="clock"></span>
            <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" class="logout-btn">خروج</button>
            </form>
        </div>
    </header>

    <main class="main-content">
        <div class="search-container">
            <input
                type="text"
                class="barcode-input"
                id="barcodeInput"
                placeholder="مرر الباركود هنا..."
                autofocus
                autocomplete="off"
            >
        </div>

        <div class="result-container">
            <div class="idle-message" id="idleMessage">
                <span style="font-size: 4rem; opacity: 0.3;">&#128722;</span>
                <p>قم بمسح باركود المنتج لمعرفة السعر</p>
            </div>

            <div class="result-card" id="resultCard">
                <div class="product-name" id="productName"></div>
                <div class="price-main" id="priceMain"></div>
                <div class="price-unit" id="priceUnit"></div>
                <div class="units-section" id="unitsSection" style="display: none;">
                    <div class="units-title">أسعار الوحدات الأخرى</div>
                    <div class="units-grid" id="unitsGrid"></div>
                </div>
            </div>

            <div class="error-message" id="errorMessage"></div>
        </div>
    </main>

    <footer class="footer">
        <span>{{ config('app.name', 'Market POS') }} - جهاز فحص الأسعار</span>
    </footer>

    <script>
        const barcodeInput = document.getElementById('barcodeInput');
        const resultCard = document.getElementById('resultCard');
        const errorMessage = document.getElementById('errorMessage');
        const idleMessage = document.getElementById('idleMessage');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        let debounceTimer;
        let clearTimer;

        function formatPrice(price) {
            return parseFloat(price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function hideAll() {
            resultCard.classList.remove('show');
            errorMessage.classList.remove('show');
            idleMessage.style.display = 'none';
        }

        function showIdle() {
            hideAll();
            idleMessage.style.display = 'block';
        }

        function showError(message) {
            hideAll();
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            scheduleReset();
        }

        function showResult(data) {
            hideAll();

            document.getElementById('productName').textContent = data.name;
            document.getElementById('priceMain').textContent = formatPrice(data.price) + ' د.ل';
            document.getElementById('priceUnit').textContent = 'السعر لكل ' + data.unit_name;

            const unitsSection = document.getElementById('unitsSection');
            const unitsGrid = document.getElementById('unitsGrid');

            if (data.units && data.units.length > 0) {
                unitsGrid.innerHTML = data.units.map(u => `
                    <div class="unit-item">
                        <div class="unit-name">${u.name}</div>
                        <div class="unit-price">${formatPrice(u.price)} د.ل</div>
                    </div>
                `).join('');
                unitsSection.style.display = 'block';
            } else if (data.is_unit && data.base_price) {
                unitsGrid.innerHTML = `
                    <div class="unit-item">
                        <div class="unit-name">${data.base_unit || 'الوحدة الأساسية'}</div>
                        <div class="unit-price">${formatPrice(data.base_price)} د.ل</div>
                    </div>
                `;
                unitsSection.style.display = 'block';
            } else {
                unitsSection.style.display = 'none';
            }

            resultCard.classList.add('show');
            scheduleReset();
        }

        function scheduleReset() {
            clearTimeout(clearTimer);
            clearTimer = setTimeout(() => {
                showIdle();
                barcodeInput.value = '';
                barcodeInput.focus();
            }, 10000);
        }

        async function lookupBarcode(barcode) {
            if (!barcode.trim()) return;

            try {
                const response = await fetch('{{ route("price-checker.lookup") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ barcode: barcode.trim() })
                });

                const result = await response.json();

                if (result.success) {
                    showResult(result.data);
                } else {
                    showError(result.message || 'المنتج غير موجود');
                }
            } catch (error) {
                showError('حدث خطأ في الاتصال');
            }

            barcodeInput.value = '';
            barcodeInput.focus();
        }

        barcodeInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (this.value.length >= 3) {
                    lookupBarcode(this.value);
                }
            }, 300);
        });

        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key == 'Enter') {
                clearTimeout(debounceTimer);
                lookupBarcode(this.value);
            }
        });

        document.addEventListener('click', function() {
            barcodeInput.focus();
        });

        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString('en-US');
        }
        updateClock();
        setInterval(updateClock, 1000);

        barcodeInput.focus();
    </script>
</body>
</html>
