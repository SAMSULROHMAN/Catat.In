<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'is_default', 'is_favorite'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_favorite' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDefaults(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_default', false);
    }

    public function scopeFavorites(Builder $query): Builder
    {
        return $query->where('is_favorite', true)->latest();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('is_favorite')->orderBy('name');
    }

    public static function defaultCategories(): array
    {
        return [
            ['name' => 'Makanan', 'icon' => '🍔'],
            ['name' => 'Transport', 'icon' => '🚗'],
            ['name' => 'Belanja', 'icon' => '🛒'],
            ['name' => 'Hiburan', 'icon' => '🎮'],
            ['name' => 'Tagihan', 'icon' => '📄'],
            ['name' => 'Gaji', 'icon' => '💰'],
            ['name' => 'Freelance', 'icon' => '💻'],
            ['name' => 'Investasi', 'icon' => '📈'],
            ['name' => 'Lainnya', 'icon' => '📌'],
        ];
    }

    public static function seedDefaultsFor(User $user): void
    {
        foreach (self::defaultCategories() as $default) {
            $user->categories()->create(array_merge($default, [
                'is_default' => true,
            ]));
        }
    }
}
