<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CategorySuggestionService
{
    private static array $keywordMap = [
        'Makanan' => [
            'makan', 'nasi', 'kopi', 'mie', 'ayam', 'soto', 'bakso', 'sate',
            'gorengan', 'minum', 'jajan', 'katering', 'restoran', 'warung',
            'kantin', 'buah', 'sayur', 'lauk', 'roti', 'susu', 'telur',
            'ikan', 'tempe', 'tahu', 'sarapan', 'makan siang', 'makan malam',
            'catering', 'seblak', 'baso', 'pecel', 'gado',
        ],
        'Transport' => [
            'bensin', 'solar', 'pertalite', 'pertamax', 'tol', 'parkir',
            'kendaraan', 'motor', 'mobil', 'ojek', 'grab', 'gojek',
            'taksi', 'angkot', 'bus', 'kereta', 'transport', 'bbm',
            'bahan bakar', 'service kendaraan', 'servis', 'spion',
        ],
        'Belanja' => [
            'belanja', 'sembako', 'kebutuhan', 'bulanan', 'minimarket',
            'supermarket', 'indomaret', 'alfamaret', 'pasar', 'swalayan',
            'pakaian', 'baju', 'sepatu', 'tas', 'aksesoris', 'peralatan',
            'alat tulis', 'buku', 'perlengkapan',
        ],
        'Hiburan' => [
            'hiburan', 'nonton', 'film', 'bioskop', 'game', 'steam',
            'spotify', 'netflix', 'youtube', 'musik', 'liburan', 'wisata',
            'tiket', 'konser', 'streaming', 'subscribe', 'disney',
            'voucher game', 'top up game',
        ],
        'Tagihan' => [
            'tagihan', 'listrik', 'air', 'pdam', 'pln', 'pulsa', 'kuota',
            'internet', 'wifi', 'bpjs', 'telepon', 'cicilan', 'pajak',
            'asuransi', 'angsuran', 'bayar', 'subscription',
        ],
        'Gaji' => [
            'gaji', 'honor', 'upah', 'thr', 'bonus', 'insentif',
            'pendapatan', 'penjualan', 'pemasukan', 'bulanan',
        ],
        'Freelance' => [
            'freelance', 'proyek', 'project', 'desain', 'coding',
            'konten', 'nulis', 'foto', 'desain grafis', 'web',
            'aplikasi', 'design', 'developer',
        ],
        'Investasi' => [
            'investasi', 'saham', 'reksadana', 'crypto', 'emas',
            'deposito', 'properti', 'tanah', 'dividen', 'obligasi',
            'bitcoin', 'cryptocurrency',
        ],
    ];

    public function suggest(int $userId, string $note): ?int
    {
        if (empty(trim($note))) {
            return $this->getLainnyaId($userId);
        }

        $lowerNote = mb_strtolower($note);

        $scores = [];

        foreach (self::$keywordMap as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerNote, mb_strtolower($keyword))) {
                    if (!isset($scores[$categoryName])) {
                        $scores[$categoryName] = 0;
                    }
                    $scores[$categoryName]++;
                }
            }
        }

        if (empty($scores)) {
            return $this->getLainnyaId($userId);
        }

        arsort($scores);
        $bestCategoryName = array_key_first($scores);

        $category = Category::forUser($userId)
            ->where('name', $bestCategoryName)
            ->first();

        return $category?->id ?? $this->getLainnyaId($userId);
    }

    public function suggestWithCategory(int $userId, string $note): ?Category
    {
        $id = $this->suggest($userId, $note);

        if ($id === null) {
            return null;
        }

        return Category::find($id);
    }

    public function getLainnyaId(int $userId): ?int
    {
        $lainnya = Category::forUser($userId)
            ->where('name', 'Lainnya')
            ->first();

        return $lainnya?->id;
    }

    public function logSuggestion(int $userId, string $note, int $chosenCategoryId): void
    {
        $suggestedId = $this->suggest($userId, $note);

        if ($suggestedId === null) {
            return;
        }

        $accepted = $suggestedId === $chosenCategoryId;

        Log::info('CategorySuggestion', [
            'user_id' => $userId,
            'note' => $note,
            'suggested_category_id' => $suggestedId,
            'chosen_category_id' => $chosenCategoryId,
            'accepted' => $accepted,
        ]);
    }

    public static function getKeywordMap(): array
    {
        return self::$keywordMap;
    }
}
