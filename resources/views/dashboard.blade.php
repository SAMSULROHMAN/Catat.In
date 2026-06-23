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

    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-medium">Transaksi Terbaru</h2>
        <a href="{{ route('transactions.index') }}" class="text-sm text-[#f53003] dark:text-[#FF4433] hover:underline">Lihat Semua</a>
    </div>

    <div id="recentTransactions" class="space-y-2">
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
    </div>
@endsection

@push('scripts')
<script>
function loadDashboard() {
    var now = new Date();
    var month = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');

    fetch(API_BASE + '/transactions?month=' + month, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var transactions = res.data || [];
            var income = 0, expense = 0;
            transactions.forEach(function (t) {
                if (t.type === 'income') income += Number(t.amount);
                else expense += Number(t.amount);
            });
            document.getElementById('monthlyIncome').textContent = formatCurrency(income);
            document.getElementById('monthlyExpense').textContent = formatCurrency(expense);
            document.getElementById('monthlyBalance').textContent = formatCurrency(income - expense);

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

document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
@endpush
