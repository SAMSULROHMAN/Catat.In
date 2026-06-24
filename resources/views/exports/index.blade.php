@extends('layouts.dashboard')

@section('title', 'Ekspor Transaksi')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-medium">Ekspor Transaksi</h1>
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mt-1">Filter transaksi lalu pilih format untuk mengekspor.</p>
    </div>

    <div class="bg-white dark:bg-[#161615] border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm p-6 mb-6">
        <form id="exportForm" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium mb-1">Tanggal Mulai</label>
                    <input type="date" id="start_date" name="start_date"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium mb-1">Tanggal Akhir</label>
                    <input type="date" id="end_date" name="end_date"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium mb-1">Jenis</label>
                    <select id="type" name="type"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
                        <option value="">Semua Jenis</option>
                        <option value="income">Pemasukan</option>
                        <option value="expense">Pengeluaran</option>
                    </select>
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium mb-1">Kategori</label>
                    <select id="category_id" name="category_id"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
                        <option value="">Semua Kategori</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="button" id="exportExcel"
                    class="px-5 py-2 bg-green-700 text-white rounded-sm hover:bg-green-800 transition-colors text-sm">
                    Download Excel (.xlsx)
                </button>
                <button type="button" id="exportCsv"
                    class="px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors text-sm">
                    Download CSV (.csv)
                </button>
            </div>
        </form>
    </div>

    <div id="exportNotification" class="hidden mb-4 p-4 rounded-sm text-sm font-medium"></div>
@endsection

@push('scripts')
<script>
    function buildExportUrl(format) {
        var params = new URLSearchParams();
        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;
        var type = document.getElementById('type').value;
        var category = document.getElementById('category_id').value;

        if (startDate) params.set('start_date', startDate);
        if (endDate) params.set('end_date', endDate);
        if (type) params.set('type', type);
        if (category) params.set('category_id', category);

        return API_BASE + '/exports/' + format + '?' + params.toString();
    }

    function triggerExport(format) {
        var notification = document.getElementById('exportNotification');
        notification.classList.add('hidden');

        var url = buildExportUrl(format);
        var token = document.querySelector('input[name="_token"]')?.value || '';

        fetch(url, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
        .then(function (r) {
            if (!r.ok) {
                return r.json().then(function (err) { throw err; });
            }
            return r.blob();
        })
        .then(function (blob) {
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = format === 'excel' ? 'transaksi.xlsx' : 'transaksi.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);

            notification.className = 'mb-4 p-4 rounded-sm text-sm font-medium bg-green-100 text-green-800';
            notification.textContent = 'File berhasil diunduh.';
            notification.classList.remove('hidden');
            setTimeout(function () { notification.classList.add('hidden'); }, 5000);
        })
        .catch(function () {
            notification.className = 'mb-4 p-4 rounded-sm text-sm font-medium bg-red-100 text-red-800';
            notification.textContent = 'Gagal mengekspor data. Silakan coba lagi.';
            notification.classList.remove('hidden');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var now = new Date();
        var firstDay = new Date(now.getFullYear(), now.getMonth(), 1);

        document.getElementById('start_date').value = firstDay.toISOString().slice(0, 10);
        document.getElementById('end_date').value = now.toISOString().slice(0, 10);

        document.getElementById('exportExcel')?.addEventListener('click', function () { triggerExport('excel'); });
        document.getElementById('exportCsv')?.addEventListener('click', function () { triggerExport('csv'); });
    });
</script>
@endpush
