<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Runtime shop settings stored in the shop_settings table, falling back to
 * config/shop.php defaults. Cached forever and busted on write.
 */
class ShopSettings
{
    protected const CACHE_KEY = 'shop_settings.all';

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default ?? config("shop.{$key}");
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('shop_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function name(): string
    {
        return (string) $this->get('name');
    }

    public function tagline(): string
    {
        return (string) $this->get('tagline');
    }

    public function description(): string
    {
        return (string) $this->get('description');
    }

    public function contactEmail(): string
    {
        return (string) $this->get('contact_email');
    }

    public function currency(): string
    {
        return (string) $this->get('currency');
    }

    public function country(): string
    {
        return (string) $this->get('country');
    }

    public function orderPrefix(): string
    {
        return (string) $this->get('order_prefix');
    }

    /**
     * Free-text trading details shown in the storefront footer (legal name,
     * company number, registered address). Distance-selling rules require
     * customers to be able to see who they're buying from.
     */
    public function tradingDetails(): ?string
    {
        $value = $this->get('trading_details');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function vatRegistered(): bool
    {
        return (bool) $this->get('vat_registered');
    }

    public function vatNumber(): ?string
    {
        $value = $this->get('vat_number');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * The standard VAT rate as a percentage, e.g. 20.0.
     */
    public function vatRate(): float
    {
        return (float) $this->get('vat_rate');
    }

    /**
     * @return array<string, mixed>
     */
    protected function all(): array
    {
        /** @var array<string, mixed> */
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return DB::table('shop_settings')
                ->pluck('value', 'key')
                ->map(fn (string $value): mixed => json_decode($value, true))
                ->all();
        });
    }
}
