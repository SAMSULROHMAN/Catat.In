<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Catat.In') }} - @yield('title')</title>
    @fonts
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18] min-h-screen">
    <nav class="border-b border-[#e3e3e0] dark:border-[#3E3E3A] px-4 py-3">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="font-semibold text-lg">{{ config('app.name', 'Catat.In') }}</a>
                <a href="{{ route('home') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Dashboard</a>
                <a href="{{ route('transactions.index') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Riwayat</a>
                <a href="{{ route('budgets.index') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Budget</a>
                <a href="{{ route('exports.index') }}" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Ekspor</a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Logout</button>
            </form>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-6">
        <div id="globalNotification" class="hidden mb-4 p-4 rounded-sm text-sm font-medium"></div>
        @yield('content')
    </main>

    <button id="fab"
        class="fixed bottom-6 right-6 w-14 h-14 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-full shadow-lg flex items-center justify-center text-2xl hover:bg-black dark:hover:bg-white transition-colors z-50">
        +
    </button>

    <div id="transactionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-[#FDFDFC] dark:bg-[#161615] rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium" id="modalTitle">Transaksi Baru</h2>
                <button id="closeModal" class="text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] text-xl leading-none">&times;</button>
            </div>

            <form id="transactionForm" class="space-y-4">
                @csrf
                <input type="hidden" id="transactionId" name="id" value="">

                <div>
                    <label for="type" class="block text-sm font-medium mb-1">Jenis</label>
                    <select id="type" name="type" required
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                        <option value="expense">Pengeluaran</option>
                        <option value="income">Pemasukan</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden" id="typeError"></p>
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium mb-1">Kategori</label>
                    <select id="category_id" name="category_id" required
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                        <option value="">Pilih kategori</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden" id="categoryError"></p>
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium mb-1">Nominal (Rp)</label>
                    <input id="amount" type="number" name="amount" min="0" step="0.01" required placeholder="50000"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="amountError"></p>
                </div>

                <div>
                    <label for="note" class="block text-sm font-medium mb-1">Catatan</label>
                    <input id="note" type="text" name="note" maxlength="1000" placeholder="Catatan singkat (opsional)"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                </div>

                <div>
                    <label for="transaction_date" class="block text-sm font-medium mb-1">Tanggal</label>
                    <input id="transaction_date" type="date" name="transaction_date" required
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="dateError"></p>
                </div>

                <div class="flex gap-2">
                    <button type="submit" id="submitBtn"
                        class="flex-1 px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        Simpan
                    </button>
                    <button type="button" id="cancelBtn"
                        class="px-5 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded-sm hover:bg-[#f5f5f4] dark:hover:bg-[#252524] transition-colors">
                        Batal
                    </button>
                </div>
            </form>

            <div id="formSuccess" class="hidden text-center py-4">
                <p class="text-green-600 dark:text-green-400 mb-2">Transaksi berhasil disimpan!</p>
                <button type="button" id="addAnotherBtn"
                    class="px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                    Tambah Lagi
                </button>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-[#FDFDFC] dark:bg-[#161615] rounded-lg shadow-xl w-full max-w-sm mx-4 p-6 text-center">
            <h3 class="text-lg font-medium mb-2">Hapus Transaksi?</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-4">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-2 justify-center">
                <button id="confirmDelete"
                    class="px-5 py-2 bg-red-600 text-white rounded-sm hover:bg-red-700 transition-colors">Hapus</button>
                <button id="cancelDelete"
                    class="px-5 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded-sm hover:bg-[#f5f5f4] dark:hover:bg-[#252524] transition-colors">Batal</button>
            </div>
        </div>
    </div>

    <script>
    const API_BASE = '/api';

    function formatCurrency(n) {
        return 'Rp ' + Number(n).toLocaleString('id-ID');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr.slice(0, 10) + 'T00:00:00');
        return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    function toDateInputValue(dateStr) {
        if (!dateStr) return '';
        return dateStr.slice(0, 10);
    }

    function showBudgetNotification(notification) {
        var banner = document.getElementById('globalNotification');
        if (!banner) return;
        banner.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800', 'bg-blue-100', 'text-blue-800');
        if (notification.level === 'warning') {
            banner.classList.add('bg-yellow-100', 'text-yellow-800');
        } else if (notification.level === 'danger') {
            banner.classList.add('bg-red-100', 'text-red-800');
        }
        banner.textContent = notification.message;
        setTimeout(function () { banner.classList.add('hidden'); }, 8000);
    }

    function todayStr() {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    function openModal(transaction) {
        document.getElementById('modalTitle').textContent = transaction ? 'Edit Transaksi' : 'Transaksi Baru';
        document.getElementById('transactionId').value = transaction ? transaction.id : '';
        document.getElementById('type').value = transaction ? transaction.type : 'expense';
        document.getElementById('category_id').value = transaction ? transaction.category_id : '';
        document.getElementById('amount').value = transaction ? transaction.amount : '';
        document.getElementById('note').value = transaction ? (transaction.note || '') : '';
        document.getElementById('transaction_date').value = transaction ? toDateInputValue(transaction.transaction_date) : todayStr();
        document.getElementById('submitBtn').textContent = transaction ? 'Update' : 'Simpan';
        document.getElementById('transactionModal').classList.remove('hidden');
        document.getElementById('transactionModal').classList.add('flex');
        document.getElementById('formSuccess').classList.add('hidden');
        document.getElementById('transactionForm').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('transactionModal').classList.add('hidden');
        document.getElementById('transactionModal').classList.remove('flex');
        document.getElementById('transactionForm').reset();
        document.getElementById('transactionId').value = '';
        document.querySelectorAll('#transactionForm .text-red-500').forEach(function (e) { e.classList.add('hidden'); });
    }

    function loadCategories() {
        fetch(API_BASE + '/categories', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var categories = res.data || [];
                var selects = ['category_id', 'filterCategory'];
                selects.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    var currentVal = el.value;
                    el.innerHTML = '<option value="">Pilih kategori</option>';
                    categories.forEach(function (c) {
                        el.innerHTML += '<option value="' + c.id + '">' + (c.icon ? c.icon + ' ' : '') + c.name + '</option>';
                    });
                    if (currentVal) el.value = currentVal;
                });
            });
    }

    loadCategories();

    document.getElementById('fab')?.addEventListener('click', function () { openModal(null); });

    document.getElementById('closeModal')?.addEventListener('click', closeModal);
    document.getElementById('cancelBtn')?.addEventListener('click', closeModal);

    document.getElementById('addAnotherBtn')?.addEventListener('click', function () {
        document.getElementById('transactionForm').reset();
        document.getElementById('transactionId').value = '';
        document.getElementById('submitBtn').textContent = 'Simpan';
        document.getElementById('modalTitle').textContent = 'Transaksi Baru';
        document.getElementById('transaction_date').value = todayStr();
        document.getElementById('formSuccess').classList.add('hidden');
        document.getElementById('transactionForm').classList.remove('hidden');
    });

    var deleteTargetId = null;

    document.getElementById('confirmDelete')?.addEventListener('click', function () {
        if (deleteTargetId) {
            deleteTransaction(deleteTargetId);
            deleteTargetId = null;
        }
        document.getElementById('confirmModal').classList.add('hidden');
        document.getElementById('confirmModal').classList.remove('flex');
    });

    document.getElementById('cancelDelete')?.addEventListener('click', function () {
        deleteTargetId = null;
        document.getElementById('confirmModal').classList.add('hidden');
        document.getElementById('confirmModal').classList.remove('flex');
    });

    document.getElementById('transactionForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        var id = document.getElementById('transactionId').value;
        var method = id ? 'PUT' : 'POST';
        var url = id ? API_BASE + '/transactions/' + id : API_BASE + '/transactions';

        var data = {
            category_id: document.getElementById('category_id').value,
            type: document.getElementById('type').value,
            amount: document.getElementById('amount').value,
            note: document.getElementById('note').value,
            transaction_date: document.getElementById('transaction_date').value,
        };

        document.querySelectorAll('#transactionForm .text-red-500').forEach(function (el) { el.classList.add('hidden'); });

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
            credentials: 'same-origin',
        })
        .then(function (r) {
            if (!r.ok) return r.json().then(function (err) { throw err; });
            return r.json();
        })
        .then(function (res) {
            document.getElementById('transactionForm').classList.add('hidden');
            document.getElementById('formSuccess').classList.remove('hidden');
            if (typeof loadTransactions === 'function') loadTransactions();
            if (typeof loadDashboard === 'function') loadDashboard();
            if (typeof loadSummary === 'function') loadSummary();
            if (typeof loadExpensePieChart === 'function') loadExpensePieChart();
            if (typeof loadMonthlyBarChart === 'function') loadMonthlyBarChart();
            if (typeof loadCashFlow === 'function') loadCashFlow();
            if (typeof loadWeeklySummary === 'function') loadWeeklySummary();
            if (typeof loadRecentTransactions === 'function') loadRecentTransactions();
            if (res.notification && typeof showBudgetNotification === 'function') {
                showBudgetNotification(res.notification);
            }
        })
        .catch(function (err) {
            if (err.errors) {
                Object.keys(err.errors).forEach(function (key) {
                    var errorEl = document.getElementById(key + 'Error');
                    if (errorEl) {
                        errorEl.textContent = err.errors[key][0];
                        errorEl.classList.remove('hidden');
                    }
                });
            }
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
