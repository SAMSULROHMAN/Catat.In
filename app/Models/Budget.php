<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category_id', 'limit_amount', 'period_month'];

    protected function casts(): array
    {
        return [
            'limit_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForMonth(Builder $query, string $periodMonth): Builder
    {
        return $query->where('period_month', $periodMonth);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public static function currentMonth(): string
    {
        return now()->format('Y-m');
    }

    public static function previousMonth(): string
    {
        return now()->subMonth()->format('Y-m');
    }
}