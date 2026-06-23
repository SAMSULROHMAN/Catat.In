@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-medium mb-2">Dashboard</h1>
    <p class="text-[#706f6c] dark:text-[#A1A09A] mb-6">Selamat datang, {{ Auth::user()->name }}!</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Saldo Bulan Ini</p>
            <p class="text-2xl font-medium" id="monthlyBalance">-</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Pemasukan</p>
                <p class="text-lg font-medium text-green-600" id="monthlyIncome">-</p>
            </div>
            <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">
                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Pengeluaran</p>
                <p class="text-lg font-medium text-red-600" id="monthlyExpense">-</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">
            <h2 class="text-lg font-medium mb-4">Proporsi Pengeluaran</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="expensePieChart"></canvas>
            </div>
            <p id="noExpenseData" class="text-sm text-[#706f6c] dark:text-[#A1A09A] text-center py-8 hidden">Belum ada data pengeluaran.</p>
        </div>
        <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">
            <h2 class="text-lg font-medium mb-4">Perbandingan Bulanan</h2>
            <div class="relative" style="height: 280px;">
                <canvas id="monthlyBarChart"></canvas>
            </div>
        </div>
    </div>

    <div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Arus Kas</h2>
            <div class="flex items-center gap-2">
                <button id="cashFlowPrev" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">&larr;</button>
                <span id="cashFlowMonth" class="text-sm font-medium"></span>
                <button id="cashFlowNext" class="text-sm text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">&rarr;</button>
            </div>
        </div>
        <div class="relative" style="height: 280px;">
            <canvas id="cashFlowChart"></canvas>
        </div>
        <p id="noCashFlowData" class="text-sm text-[#706f6c] dark:text-[#A1A09A] text-center py-8 hidden">Belum ada data arus kas.</p>
    </div>

    <div id="dashboardBudgetSection" class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-medium">Progress Budget</h2>
            <a href="{{ route('budgets.index') }}" class="text-sm text-[#f53003] dark:text-[#FF4433] hover:underline">Atur Budget</a>
        </div>
        <div id="dashboardBudgets" class="space-y-2">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
        </div>
    </div>

    <div class="mb-8">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-medium">Ringkasan Mingguan</h2>
        </div>
        <div id="weeklySummary" class="space-y-2">
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
        </div>
    </div>

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-medium">Transaksi Terbaru</h2>
        <a href="{{ route('transactions.index') }}" class="text-sm text-[#f53003] dark:text-[#FF4433] hover:underline">Lihat Semua</a>
    </div>

    <div id="recentTransactions" class="space-y-2">
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
var pieChart = null;
var barChart = null;
var lineChart = null;
var cashFlowMonth = new Date();

function getCurrentMonth() {
    var now = new Date();
    return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
}

function formatMonthLabel(monthStr) {
    var parts = monthStr.split('-');
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
}

var COLORS = ['#f53003', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316'];

function loadSummary() {
    var month = getCurrentMonth();
    fetch(API_BASE + '/dashboard/summary?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var d = res.data;
            document.getElementById('monthlyIncome').textContent = formatCurrency(d.total_income);
            document.getElementById('monthlyExpense').textContent = formatCurrency(d.total_expense);
            document.getElementById('monthlyBalance').textContent = formatCurrency(d.balance);
        })
        .catch(function () {});
}

function loadExpensePieChart() {
    var month = getCurrentMonth();
    fetch(API_BASE + '/dashboard/expense-by-category?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var data = res.data || [];
            var canvas = document.getElementById('expensePieChart');
            var noData = document.getElementById('noExpenseData');

            if (data.length === 0) {
                canvas.style.display = 'none';
                noData.classList.remove('hidden');
                return;
            }

            canvas.style.display = 'block';
            noData.classList.add('hidden');

            var labels = data.map(function (d) { return (d.category_icon || '') + ' ' + d.category_name; });
            var values = data.map(function (d) { return d.total; });
            var colors = data.map(function (_, i) { return COLORS[i % COLORS.length]; });

            if (pieChart) pieChart.destroy();

            pieChart = new Chart(canvas, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                    var pct = ((ctx.raw / total) * 100).toFixed(1);
                                    return ctx.label + ': ' + formatCurrency(ctx.raw) + ' (' + pct + '%)';
                                },
                            },
                        },
                    },
                    onClick: function (evt, elements) {
                        if (elements.length > 0) {
                            var idx = elements[0].index;
                            var catId = data[idx].category_id;
                            window.location.href = '/transactions?category_id=' + catId;
                        }
                    },
                },
            });
        })
        .catch(function () {});
}

function loadMonthlyBarChart() {
    fetch(API_BASE + '/dashboard/monthly-comparison', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var data = res.data || [];
            var canvas = document.getElementById('monthlyBarChart');
            var labels = data.map(function (d) { return formatMonthLabel(d.month); });
            var incomes = data.map(function (d) { return d.income; });
            var expenses = data.map(function (d) { return d.expense; });

            if (barChart) barChart.destroy();

            barChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: incomes,
                            backgroundColor: '#10b981',
                            borderRadius: 2,
                        },
                        {
                            label: 'Pengeluaran',
                            data: expenses,
                            backgroundColor: '#f53003',
                            borderRadius: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (v) {
                                    if (v >= 1000000) return (v / 1000000).toFixed(0) + 'jt';
                                    if (v >= 1000) return (v / 1000).toFixed(0) + 'rb';
                                    return v;
                                },
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + formatCurrency(ctx.raw);
                                },
                            },
                        },
                    },
                },
            });
        })
        .catch(function () {});
}

function loadCashFlow() {
    var month = cashFlowMonth.getFullYear() + '-' + String(cashFlowMonth.getMonth() + 1).padStart(2, '0');
    document.getElementById('cashFlowMonth').textContent = formatMonthLabel(month);

    fetch(API_BASE + '/dashboard/cash-flow?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var d = res.data;
            var daily = d.daily || [];
            var canvas = document.getElementById('cashFlowChart');
            var noData = document.getElementById('noCashFlowData');

            if (daily.length === 0) {
                canvas.style.display = 'none';
                noData.classList.remove('hidden');
                return;
            }

            canvas.style.display = 'block';
            noData.classList.add('hidden');

            var labels = daily.map(function (item) {
                var parts = item.date.split('-');
                return parseInt(parts[2], 10);
            });
            var balances = daily.map(function (item) { return item.balance; });

            if (lineChart) lineChart.destroy();

            lineChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Saldo',
                            data: balances,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 2,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            title: { display: true, text: 'Tanggal' },
                        },
                        y: {
                            ticks: {
                                callback: function (v) {
                                    if (v >= 1000000) return (v / 1000000).toFixed(0) + 'jt';
                                    if (v >= 1000) return (v / 1000).toFixed(0) + 'rb';
                                    return v;
                                },
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (ctx) {
                                    return ctx.dataset.label + ': ' + formatCurrency(Math.abs(ctx.raw));
                                },
                            },
                        },
                    },
                },
            });
        })
        .catch(function () {});
}

document.getElementById('cashFlowPrev')?.addEventListener('click', function () {
    cashFlowMonth.setMonth(cashFlowMonth.getMonth() - 1);
    loadCashFlow();
});

document.getElementById('cashFlowNext')?.addEventListener('click', function () {
    cashFlowMonth.setMonth(cashFlowMonth.getMonth() + 1);
    loadCashFlow();
});

function loadDashboardBudgets() {
    var month = getCurrentMonth();
    fetch(API_BASE + '/budgets/summary?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var budgets = res.data || [];
            var container = document.getElementById('dashboardBudgets');
            if (budgets.length === 0) {
                container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Belum ada budget.</p>';
                return;
            }
            container.innerHTML = budgets.slice(0, 5).map(function (b) {
                var pct = b.percentage;
                var barColor = b.color === 'green' ? 'bg-green-500' : b.color === 'yellow' ? 'bg-yellow-500' : 'bg-red-500';
                var textColor = b.color === 'green' ? 'text-green-600' : b.color === 'yellow' ? 'text-yellow-600' : 'text-red-600';
                return '<div class="p-3 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">' +
                    '<div class="flex items-center justify-between mb-1">' +
                        '<div class="flex items-center gap-2">' +
                            '<span>' + (b.category.icon || '') + '</span>' +
                            '<p class="text-sm font-medium">' + (b.category.name || '-') + '</p>' +
                        '</div>' +
                        '<span class="text-sm font-medium ' + textColor + '">' + pct + '%</span>' +
                    '</div>' +
                    '<div class="w-full h-1.5 bg-[#e3e3e0] dark:bg-[#3E3E3A] rounded-full overflow-hidden">' +
                        '<div class="h-full ' + barColor + ' rounded-full" style="width:' + pct + '%"></div>' +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function () {
            document.getElementById('dashboardBudgets').innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

function loadWeeklySummary() {
    var month = getCurrentMonth();
    fetch(API_BASE + '/dashboard/weekly-summary?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var weeks = res.data || [];
            var container = document.getElementById('weeklySummary');
            if (weeks.length === 0) {
                container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Belum ada transaksi minggu ini.</p>';
                return;
            }
            container.innerHTML = weeks.map(function (w) {
                var balance = w.income - w.expense;
                var balanceColor = balance >= 0 ? 'text-green-600' : 'text-red-600';
                var startParts = w.week_start.split('-');
                var endParts = w.week_end.split('-');
                var startDay = parseInt(startParts[2], 10);
                var endDay = parseInt(endParts[2], 10);
                var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                var label = startDay + '-' + endDay + ' ' + monthNames[parseInt(startParts[1], 10) - 1];

                return '<div class="p-3 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">' +
                    '<div class="flex items-center justify-between">' +
                        '<div>' +
                            '<p class="text-sm font-medium">' + label + '</p>' +
                            '<p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">' + w.transaction_count + ' transaksi</p>' +
                        '</div>' +
                        '<div class="text-right">' +
                            '<p class="text-xs text-green-600">+' + formatCurrency(w.income) + '</p>' +
                            '<p class="text-xs text-red-600">-' + formatCurrency(w.expense) + '</p>' +
                            '<p class="text-sm font-medium ' + balanceColor + '">' + formatCurrency(Math.abs(balance)) + '</p>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function () {
            document.getElementById('weeklySummary').innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

function loadRecentTransactions() {
    var month = getCurrentMonth();
    fetch(API_BASE + '/transactions?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var transactions = res.data || [];
            var recent = transactions.slice(0, 5);
            var container = document.getElementById('recentTransactions');
            if (recent.length === 0) {
                container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Belum ada transaksi.</p>';
                return;
            }
            container.innerHTML = recent.map(function (t) {
                return '<div class="flex items-center justify-between p-3 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">' +
                    '<div>' +
                        '<p class="text-sm font-medium">' + (t.category ? t.category.name : '-') + ' ' + (t.category ? t.category.icon : '') + '</p>' +
                        '<p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">' + (t.note || '-') + ' &middot; ' + formatDate(t.transaction_date) + '</p>' +
                    '</div>' +
                    '<span class="text-sm font-medium ' + (t.type === 'income' ? 'text-green-600' : 'text-red-600') + '">' + (t.type === 'income' ? '+' : '-') + formatCurrency(t.amount) + '</span>' +
                '</div>';
            }).join('');
        })
        .catch(function () {
            document.getElementById('recentTransactions').innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

document.addEventListener('DOMContentLoaded', function () {
    loadSummary();
    loadExpensePieChart();
    loadMonthlyBarChart();
    loadCashFlow();
    loadDashboardBudgets();
    loadWeeklySummary();
    loadRecentTransactions();
});
</script>
@endpush
