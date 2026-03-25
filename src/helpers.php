<?php

use Azuriom\Plugin\Tebex\Resources\Currencies;
use Azuriom\Plugin\Tebex\Services\CartSessionService;

/*
|--------------------------------------------------------------------------
| Helper functions
|--------------------------------------------------------------------------
|
| Here is where you can register helpers for your plugin. These
| functions are loaded by Composer and are globally available on the app !
| Just make sure you verify that a function doesn't exist before registering it
| to prevent any side effect.
|
*/

if (! function_exists('tebex_store_info')) {
    function tebex_store_info($key = null, $default = null)
    {
        $info = cache('tebex.account_info');

        if (!$info) {
            return $default;
        }

        if ($key) {
            return data_get($info, $key) ?? data_get($info, 'data.'.$key) ?? $default;
        }

        return $info;
    }
}

if (! function_exists('tebex_currency')) {
    function tebex_currency()
    {
        return tebex_store_info('currency', 'USD');
    }
}

if (! function_exists('tebex_format_price')) {
    function tebex_format_price($amount)
    {
        return number_format((float) $amount, 2) . tebex_currency_symbol();
    }
}

if (! function_exists('tebex_currency_symbol')) {
    function tebex_currency_symbol(?string $currency = null)
    {
        return Currencies::symbol($currency ?? tebex_currency());
    }
}

if (! function_exists('tebex_format_price_data')) {
    /**
     * Build normalized price info (normal, discounted, display) from pricing data.
     *
     * @param array $priceData
     * @param bool|null $showVat
     * @return array{normal: float, discounted: ?float, display: float}
     */
    function tebex_format_price_data(array $priceData, ?bool $showVat = null): array
    {
        $showVat = $showVat ?? setting('tebex.shop.vat.status', false);

        $currentBase = (float) ($priceData['base_price'] ?? $priceData['price'] ?? 0);
        $currentTotal = (float) ($priceData['total_price'] ?? $currentBase);
        $discountBase = (float) ($priceData['discount'] ?? 0);

        $taxMultiplier = 1.0;
        if ($currentBase > 0) {
            $taxMultiplier = $currentTotal / $currentBase;
        }

        $currentPrice = $showVat ? $currentTotal : $currentBase;
        $originalPrice = $currentPrice + ($discountBase * ($showVat ? $taxMultiplier : 1));

        $normal = round($originalPrice, 2);
        $discounted = $discountBase > 0 ? round($currentPrice, 2) : null;

        return [
            'normal' => $normal,
            'discounted' => $discounted,
            'display' => $discounted ?? $normal,
        ];
    }
}


if (!function_exists('tebex_get_cart')) {
    /**
     * Get the current cart from session
     *
     * @return array The cart data from session
     */
    function tebex_get_cart(): array
    {
        return app(CartSessionService::class)->getCart();
    }
}

if (!function_exists('tebex_cart_count')) {
    /**
     * Get the current cart item count
     *
     * @return int The number of items in the cart
     */
    function tebex_cart_count(): int
    {
        return app(CartSessionService::class)->count();
    }
}

if (!function_exists('tebex_cart_items')) {
    /**
     * Get the current cart items
     *
     * @return array The items in the cart
     */
    function tebex_cart_items(): array
    {
        return app(CartSessionService::class)->items();
    }
}

if (!function_exists('tebex_cart_snapshot')) {
    /**
     * Get a snapshot of the current cart
     *
     * @return array A snapshot of the cart with items, count, and user info
     */
    function tebex_cart_snapshot(): array
    {
        return app(CartSessionService::class)->snapshot();
    }
}

if (!function_exists('tebex_add_to_cart')) {
    /**
     * Add an item to the cart
     *
     * @param int $packageId The package ID to add
     * @param int $quantity The quantity to add
     * @param int $maxPerItem The maximum quantity per item
     * @return array A snapshot of the updated cart
     */
    function tebex_add_to_cart(int $packageId, int $quantity = 1, int $maxPerItem = 99): array
    {
        return app(CartSessionService::class)->addItem($packageId, min($quantity, $maxPerItem));
    }
}

if (!function_exists('tebex_update_cart_item')) {
    /**
     * Update an item's quantity in the cart
     *
     * @param int $packageId The package ID to update
     * @param int $quantity The new quantity
     * @param int $maxPerItem The maximum quantity per item
     * @return array A snapshot of the updated cart
     */
    function tebex_update_cart_item(int $packageId, int $quantity, int $maxPerItem = 99): array
    {
        return app(CartSessionService::class)->setItemQuantity($packageId, min($quantity, $maxPerItem));
    }
}

if (!function_exists('tebex_remove_from_cart')) {
    /**
     * Remove an item from the cart
     *
     * @param int $packageId The package ID to remove
     * @return array A snapshot of the updated cart
     */
    function tebex_remove_from_cart(int $packageId): array
    {
        return app(CartSessionService::class)->removeItem($packageId);
    }
}

if (!function_exists('tebex_clear_cart')) {
    /**
     * Clear the cart completely
     *
     * @return void
     */
    function tebex_clear_cart(): void
    {
        app(CartSessionService::class)->clear();
    }
}

if (! function_exists('tebex_get_avatar')) {
    /**
     * Get the avatar URL for a given username or user.
     *
     * @param  mixed  $user
     * @param  int  $size
     * @return string
     */
    function tebex_get_avatar($user, int $size = 64): string
    {
        if ($user instanceof \Azuriom\Models\User) {
            return $user->getAvatar($size);
        }

        if (is_string($user)) {
            $azuriomUser = \Azuriom\Models\User::where('name', $user)->first();

            if ($azuriomUser !== null) {
                return $azuriomUser->getAvatar($size);
            }

            // Temp user for game avatar
            $tempUser = new \Azuriom\Models\User(['name' => $user]);

            return game()->getAvatarUrl($tempUser, $size);
        }

        return 'https://mc-heads.net/avatar/00000000000000000000000000000000/'.$size.'png';
    }
}
