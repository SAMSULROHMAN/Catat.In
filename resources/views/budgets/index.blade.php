@extends('layouts.dashboard')

@section('title', 'Budget Bulanan')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-medium">Budget Bulanan</h1>
        <div class="flex gap-2">
            <button id="copyPreviousBtn"
                class="px-4 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded-sm hover:bg-[#f5f5f4] dark:hover:bg-[#252524] transition-colors text-sm">
                Salin dari Bulan Lalu
            </button>
            <button id="fabAlt"
                class="px-4 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors text-sm">
                + Set Budget
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-6">
        <input type="month" id="filterMonth"
            class="px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003] text-sm">
    </div>

    <div id="notificationBanner" class="hidden mb-4 p-4 rounded-sm text-sm font-medium"></div>

    <div id="budgetsList" class="space-y-3">
        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>
    </div>

    <div id="budgetModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-[#FDFDFC] dark:bg-[#161615] rounded-lg shadow-xl w-full max-w-md mx-4 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-medium" id="budgetModalTitle">Set Budget</h2>
                <button id="closeBudgetModal" class="text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] text-xl leading-none">&times;</button>
            </div>

            <form id="budgetForm" class="space-y-4">
                @csrf
                <input type="hidden" id="budgetId" name="id" value="">

                <div>
                    <label for="budgetCategory" class="block text-sm font-medium mb-1">Kategori</label>
                    <select id="budgetCategory" name="category_id" required
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                        <option value="">Pilih kategori</option>
                    </select>
                    <p class="text-red-500 text-sm mt-1 hidden" id="budgetCategoryError"></p>
                </div>

                <div>
                    <label for="budgetLimit" class="block text-sm font-medium mb-1">Batas Budget (Rp)</label>
                    <input id="budgetLimit" type="number" name="limit_amount" min="0" step="0.01" required placeholder="1000000"
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="budgetLimitError"></p>
                </div>

                <div>
                    <label for="budgetMonth" class="block text-sm font-medium mb-1">Periode</label>
                    <input id="budgetMonth" type="month" name="period_month" required
                        class="w-full px-3 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm bg-white dark:bg-[#161615] text-[#1b1b18] dark:text-[#EDEDEC] focus:outline-none focus:ring-1 focus:ring-[#f53003]">
                    <p class="text-red-500 text-sm mt-1 hidden" id="budgetMonthError"></p>
                </div>

                <div class="flex gap-2">
                    <button type="submit" id="budgetSubmitBtn"
                        class="flex-1 px-5 py-2 bg-[#1b1b18] dark:bg-[#eeeeec] text-white dark:text-[#1C1C1A] rounded-sm hover:bg-black dark:hover:bg-white transition-colors">
                        Simpan
                    </button>
                    <button type="button" id="cancelBudgetBtn"
                        class="px-5 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded-sm hover:bg-[#f5f5f4] dark:hover:bg-[#252524] transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirmBudgetModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50">
        <div class="bg-[#FDFDFC] dark:bg-[#161615] rounded-lg shadow-xl w-full max-w-sm mx-4 p-6 text-center">
            <h3 class="text-lg font-medium mb-2">Hapus Budget?</h3>
            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A] mb-4">Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-2 justify-center">
                <button id="confirmBudgetDelete"
                    class="px-5 py-2 bg-red-600 text-white rounded-sm hover:bg-red-700 transition-colors">Hapus</button>
                <button id="cancelBudgetDelete"
                    class="px-5 py-2 border border-[#e3e3e0] dark:border-[#3E3E3A] text-[#706f6c] dark:text-[#A1A09A] rounded-sm hover:bg-[#f5f5f4] dark:hover:bg-[#252524] transition-colors">Batal</button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
var deleteBudgetId = null;

function loadBudgets() {
    var month = document.getElementById('filterMonth').value;
    var params = new URLSearchParams();
    if (month) params.set('month', month);

    var url = API_BASE + '/budgets/summary' + (params.toString() ? '?' + params.toString() : '');
    var container = document.getElementById('budgetsList');
    container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Memuat...</p>';

    fetch(url, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var budgets = res.data || [];
            if (budgets.length === 0) {
                container.innerHTML = '<p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Belum ada budget. Klik "Set Budget" untuk membuat.</p>';
                return;
            }
            container.innerHTML = budgets.map(function (b) {
                var pct = b.percentage;
                var color = b.color;
                var barColor = color === 'green' ? 'bg-green-500' : color === 'yellow' ? 'bg-yellow-500' : 'bg-red-500';
                var textColor = color === 'green' ? 'text-green-600' : color === 'yellow' ? 'text-yellow-600' : 'text-red-600';

                return '<div class="p-4 border border-[#e3e3e0] dark:border-[#3E3E3A] rounded-sm">' +
                    '<div class="flex items-center justify-between mb-2">' +
                        '<div class="flex items-center gap-2">' +
                            '<span class="text-lg">' + (b.category.icon || '') + '</span>' +
                            '<p class="text-sm font-medium">' + (b.category.name || '-') + '</p>' +
                        '</div>' +
                        '<div class="flex items-center gap-3">' +
                            '<span class="text-sm font-medium ' + textColor + '">' + pct + '%</span>' +
                            '<button onclick="editBudget(' + b.id + ')" class="text-xs text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC]">Edit</button>' +
                            '<button onclick="confirmDeleteBudget(' + b.id + ')" class="text-xs text-red-500 hover:text-red-700">Hapus</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="w-full h-2 bg-[#e3e3e0] dark:bg-[#3E3E3A] rounded-full overflow-hidden mb-2">' +
                        '<div class="h-full ' + barColor + ' rounded-full transition-all" style="width:' + pct + '%"></div>' +
                    '</div>' +
                    '<div class="flex items-center justify-between text-xs text-[#706f6c] dark:text-[#A1A09A]">' +
                        '<span>' + formatCurrency(b.total_expense) + ' / ' + formatCurrency(b.limit_amount) + '</span>' +
                        (b.daily_budget ? '<span>Sisa: ' + formatCurrency(b.remaining) + ' (Rp ' + Number(b.daily_budget).toLocaleString('id-ID') + '/hari)</span>' : '<span class="text-red-500">Melebihi budget</span>') +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function () {
            container.innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

function loadBudgetCategories() {
    fetch(API_BASE + '/categories', { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var categories = res.data || [];
            var el = document.getElementById('budgetCategory');
            var currentVal = el.value;
            el.innerHTML = '<option value="">Pilih kategori</option>';
            categories.forEach(function (c) {
                el.innerHTML += '<option value="' + c.id + '">' + (c.icon ? c.icon + ' ' : '') + c.name + '</option>';
            });
            if (currentVal) el.value = currentVal;
        });
}

function openBudgetModal(budget) {
    var isEdit = !!budget;
    document.getElementById('budgetModalTitle').textContent = isEdit ? 'Edit Budget' : 'Set Budget';
    document.getElementById('budgetId').value = budget ? budget.id : '';
    document.getElementById('budgetCategory').value = budget ? budget.category_id : '';
    document.getElementById('budgetLimit').value = budget ? budget.limit_amount : '';
    document.getElementById('budgetMonth').value = budget ? budget.period_month : document.getElementById('filterMonth').value;
    document.getElementById('budgetSubmitBtn').textContent = isEdit ? 'Update' : 'Simpan';

    document.getElementById('budgetModal').classList.remove('hidden');
    document.getElementById('budgetModal').classList.add('flex');
}

function closeBudgetModal() {
    document.getElementById('budgetModal').classList.add('hidden');
    document.getElementById('budgetModal').classList.remove('flex');
    document.getElementById('budgetForm').reset();
    document.getElementById('budgetId').value = '';
    document.querySelectorAll('#budgetForm .text-red-500').forEach(function (e) { e.classList.add('hidden'); });
}

function editBudget(id) {
    fetch(API_BASE + '/budgets/' + id, { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            var b = res.data;
            b.category_id = b.category_id;
            b.limit_amount = b.limit_amount;
            b.period_month = b.period_month;
            openBudgetModal(b);
        })
        .catch(function () { alert('Gagal memuat data budget.'); });
}

function confirmDeleteBudget(id) {
    deleteBudgetId = id;
    document.getElementById('confirmBudgetModal').classList.remove('hidden');
    document.getElementById('confirmBudgetModal').classList.add('flex');
}

function deleteBudget(id) {
    var token = document.querySelector('input[name="_token"]')?.value || '';
    fetch(API_BASE + '/budgets/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': token },
        credentials: 'same-origin',
    })
        .then(function (r) { if (r.ok) { loadBudgets(); } })
        .catch(function () { alert('Gagal menghapus budget.'); });
}

function copyPrevious() {
    var btn = document.getElementById('copyPreviousBtn');
    btn.textContent = 'Menyalin...';
    btn.disabled = true;

    var token = document.querySelector('input[name="_token"]')?.value || '';
    fetch(API_BASE + '/budgets/copy-previous', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            showBanner('success', res.message);
            loadBudgets();
        })
        .catch(function () {
            showBanner('error', 'Gagal menyalin budget.');
        })
        .finally(function () {
            btn.textContent = 'Salin dari Bulan Lalu';
            btn.disabled = false;
        });
}

function showBanner(type, message) {
    var banner = document.getElementById('notificationBanner');
    banner.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800', 'bg-blue-100', 'text-blue-800');
    if (type === 'success') {
        banner.classList.add('bg-green-100', 'text-green-800');
    } else if (type === 'error') {
        banner.classList.add('bg-red-100', 'text-red-800');
    } else {
        banner.classList.add('bg-blue-100', 'text-blue-800');
    }
    banner.textContent = message;
    banner.classList.remove('hidden');

    setTimeout(function () {
        banner.classList.add('hidden');
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function () {
    loadBudgets();
    loadBudgetCategories();

    var now = new Date();
    var monthInput = document.getElementById('filterMonth');
    if (monthInput) {
        monthInput.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    }

    document.getElementById('fabAlt')?.addEventListener('click', function () {
        openBudgetModal(null);
    });

    document.getElementById('closeBudgetModal')?.addEventListener('click', closeBudgetModal);
    document.getElementById('cancelBudgetBtn')?.addEventListener('click', closeBudgetModal);

    document.getElementById('filterMonth')?.addEventListener('change', function () { loadBudgets(); });

    document.getElementById('copyPreviousBtn')?.addEventListener('click', copyPrevious);

    document.getElementById('confirmBudgetDelete')?.addEventListener('click', function () {
        if (deleteBudgetId) {
            deleteBudget(deleteBudgetId);
            deleteBudgetId = null;
        }
        document.getElementById('confirmBudgetModal').classList.add('hidden');
        document.getElementById('confirmBudgetModal').classList.remove('flex');
    });

    document.getElementById('cancelBudgetDelete')?.addEventListener('click', function () {
        deleteBudgetId = null;
        document.getElementById('confirmBudgetModal').classList.add('hidden');
        document.getElementById('confirmBudgetModal').classList.remove('flex');
    });

    document.getElementById('budgetForm')?.addEventListener('submit', function (e) {
        e.preventDefault();
        var id = document.getElementById('budgetId').value;
        var method = id ? 'PUT' : 'POST';
        var url = id ? API_BASE + '/budgets/' + id : API_BASE + '/budgets';

        var data = {
            category_id: document.getElementById('budgetCategory').value,
            limit_amount: document.getElementById('budgetLimit').value,
            period_month: document.getElementById('budgetMonth').value,
        };

        document.querySelectorAll('#budgetForm .text-red-500').forEach(function (el) { el.classList.add('hidden'); });

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
            .then(function () {
                closeBudgetModal();
                loadBudgets();
                showBanner('success', id ? 'Budget berhasil diperbarui.' : 'Budget berhasil disimpan.');
            })
            .catch(function (err) {
                if (err.errors) {
                    Object.keys(err.errors).forEach(function (key) {
                        var errorEl = document.getElementById('budget' + key.charAt(0).toUpperCase() + key.slice(1) + 'Error');
                        if (!errorEl) {
                            var altKey = key === 'category_id' ? 'budgetCategoryError' : key === 'limit_amount' ? 'budgetLimitError' : 'budgetMonthError';
                            errorEl = document.getElementById(altKey);
                            if (!errorEl) errorEl = document.getElementById('budgetCategoryError');
                        }
                        if (errorEl) {
                            errorEl.textContent = err.errors[key][0];
                            errorEl.classList.remove('hidden');
                        }
                    });
                } else if (err.message) {
                    showBanner('error', err.message);
                }
            });
    });
});
</script>
@endpush