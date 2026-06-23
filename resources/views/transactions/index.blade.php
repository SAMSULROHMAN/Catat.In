@extends('layouts.dashboard')

@section('title', 'Riwayat Transaksi')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-medium">Riwayat Transaksi</h1>
        <button id="fabAlt"
            class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors text-sm">
            + Transaksi Baru
        </button>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <select id="filterType"
            class="px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
            <option value="">Semua Jenis</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
        </select>

        <select id="filterCategory"
            class="px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
            <option value="">Semua Kategori</option>
        </select>

        <input type="month" id="filterMonth"
            class="px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
    </div>

    <div id="transactionsList" class="space-y-2">
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
    </div>

    <div id="pagination" class="mt-4 flex items-center justify-center gap-2 text-sm"></div>
@endsection

@push('scripts')
<script>
function loadTransactions() {
    var params = new URLSearchParams();
    var type = document.getElementById('filterType').value;
    var category = document.getElementById('filterCategory').value;
    var month = document.getElementById('filterMonth').value;
    if (type) params.set('type', type);
    if (category) params.set('category_id', category);
    if (month) params.set('month', month);

    var container = document.getElementById('transactionsList');
    container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>';

    fetch(API_BASE + '/transactions?' + params.toString(), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var transactions = res.data || [];
            if (transactions.length === 0) {
                container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Tidak ada transaksi.</p>';
                document.getElementById('pagination').innerHTML = '';
                return;
            }
            container.innerHTML = transactions.map(function (t) {
                return '<div class="flex items-center justify-between p-3 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">' +
                    '<div class="flex-1">' +
                        '<p class="text-sm font-medium">' + (t.category ? t.category.name : '-') + ' ' + (t.category ? t.category.icon : '') + '</p>' +
                        '<p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">' + (t.note || '-') + ' &middot; ' + formatDate(t.transaction_date) + '</p>' +
                    '</div>' +
                    '<div class="flex items-center gap-3">' +
                        '<span class="text-sm font-medium ' + (t.type === 'income' ? 'text-green-600' : 'text-red-600') + '">' + (t.type === 'income' ? '+' : '-') + formatCurrency(t.amount) + '</span>' +
                        '<button onclick="editTransaction(' + t.id + ')" class="text-xs text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Edit</button>' +
                        '<button onclick="duplicateTransaction(' + t.id + ')" class="text-xs text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Duplikat</button>' +
                        '<button onclick="confirmDeleteTransaction(' + t.id + ')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>' +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function () {
            container.innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

function editTransaction(id) {
    fetch(API_BASE + '/transactions/' + id, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (t) { openModal(t); })
        .catch(function () { alert('Gagal memuat data transaksi.'); });
}

function duplicateTransaction(id) {
    var token = document.querySelector('input[name="_token"]')?.value || '';
    fetch(API_BASE + '/transactions/' + id + '/duplicate', { method: 'POST', headers: { 'X-CSRF-TOKEN': token }, credentials: 'same-origin' })
        .then(function (r) { if (r.ok) { loadTransactions(); if (typeof loadDashboard === 'function') loadDashboard(); } })
        .catch(function () { alert('Gagal menduplikasi transaksi.'); });
}

function confirmDeleteTransaction(id) {
    deleteTargetId = id;
    document.getElementById('confirmModal').classList.remove('hidden');
    document.getElementById('confirmModal').classList.add('flex');
}

function deleteTransaction(id) {
    var token = document.querySelector('input[name="_token"]')?.value || '';
    fetch(API_BASE + '/transactions/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': token }, credentials: 'same-origin' })
        .then(function (r) { if (r.ok) { loadTransactions(); if (typeof loadDashboard === 'function') loadDashboard(); } })
        .catch(function () { alert('Gagal menghapus transaksi.'); });
}

document.addEventListener('DOMContentLoaded', function () {
    loadTransactions();

    var now = new Date();
    var monthInput = document.getElementById('filterMonth');
    if (monthInput) {
        monthInput.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    }

    document.getElementById('fabAlt')?.addEventListener('click', function () { openModal(null); });

    document.getElementById('filterType')?.addEventListener('change', function () { loadTransactions(); });
    document.getElementById('filterCategory')?.addEventListener('change', function () { loadTransactions(); });
    document.getElementById('filterMonth')?.addEventListener('change', function () { loadTransactions(); });
});
</script>
@endpush
