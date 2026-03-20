<?php

namespace Azuriom\Plugin\Tebex\Controllers;

use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\Tebex\Services\CartSessionService;
use Azuriom\Plugin\Tebex\Services\TebexApiService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request, CartSessionService $cart, TebexApiService $api)
    {
        $token = $api->getConfiguredPublicKeyOrAbort();

        $api->getAccountInfo($token);

        $snapshot = $cart->snapshot();

        $packagesResponse = $api->getPackagesWithPricing($token);
        if (empty($packagesResponse['data'])) {
            $packagesResponse = $api->getCategories($token, true);
        }

        $packagesMap = collect($packagesResponse['data'] ?? [])->keyBy('id');

        foreach ($snapshot['items'] as &$item) {
            if ($pkg = $packagesMap->get($item['package_id'])) {
                $item['price'] = (float) ($pkg['total_price'] ?? $pkg['base_price'] ?? $pkg['price'] ?? 0);
                $item['name'] = $pkg['name'] ?? $item['name'];
                $item['image'] = $pkg['image'] ?? null;
            } else {
                $item['unavailable'] = true;
            }
        }

        return view('tebex::cart.index', [
            'cart' => $snapshot,
            'username' => auth()->user()?->name ?? session('tebex.username'),
            'creatorCode' => session('tebex.creator_code'),
        ]);
    }

    public function checkout(Request $request, CartSessionService $cart, TebexApiService $api)
    {
        $data = $request->validate([
            'creator_code' => 'nullable|string|max:64',
        ]);

        $creatorCode = trim((string) ($data['creator_code'] ?? ''));
        if ($creatorCode !== '') {
            session()->put('tebex.creator_code', $creatorCode);
        } else {
            session()->forget('tebex.creator_code');
        }

        $items = $cart->items();
        if (empty($items)) return redirect()->route('tebex.cart.show')->with('info', trans('tebex::messages.cart.empty'));

        $token = $api->getConfiguredPublicKeyOrAbort();
        $username = auth()->user()?->name ?? session('tebex.username');

        try {
            $redirectUrl = $api->processCheckout($token, $items, $username ?? '', [
                'show' => route('tebex.cart.show'),
                'complete' => route('tebex.cart.payment.complete'),
                'cancel' => route('tebex.cart.payment.failed'),
            ], $creatorCode);
            return redirect()->away($redirectUrl);
        } catch (\Exception $e) {
            return redirect()->route('tebex.cart.show')->with('error', $e->getMessage());
        }
    }

    public function auth(Request $request, TebexApiService $api)
    {
        $username = $request->input('username');
        if ($username) session()->put('tebex.username', $username);

        $token = $api->getConfiguredPublicKeyOrAbort();

        $basket = $api->createBasketWithFallback($token, [
            'complete' => route('tebex.cart.show'),
            'cancel' => route('tebex.cart.show')
        ], $username);

        $ident = $basket['ident'] ?? $basket['data']['ident'] ?? null;
        if (!$ident) return redirect()->back()->with('error', trans('tebex::messages.cart.creation_failed'));

        session()->put('tebex.basket.ident', $ident);

        $auth = $api->getBasketAuthLinks($token, $ident, route('tebex.cart.show'));
        $link = $auth['links'][0]['href'] ?? $auth[0]['url'] ?? null;

        return $link ? redirect()->away($link) : redirect()->route('tebex.cart.show');
    }

    public function add(Request $request, CartSessionService $cart)
    {
        $data = $request->validate([
            'package_id' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1',
            'username' => 'nullable|string',
        ]);

        if (!auth()->check() && !empty($data['username'])) {
            session()->put('tebex.username', $data['username']);
        }

        $snapshot = $cart->addItem((int) $data['package_id'], (int) ($data['quantity'] ?? 1));
        return response()->json(['success' => true, 'cart' => $snapshot]);
    }

    public function update(Request $request, CartSessionService $cart, TebexApiService $api)
    {
        $data = $request->validate(['package_id' => 'required|integer', 'quantity' => 'required|integer']);
        $snapshot = $cart->setItemQuantity((int)$data['package_id'], (int)$data['quantity']);

        $basketIdent = session('tebex.basket.ident');
        if ($basketIdent) {
            try {
                $api->updateBasketPackageQuantity($basketIdent, (int) $data['package_id'], (int) $data['quantity']);
            } catch (\Throwable $e) {
                // Keep local cart update even if remote sync fails.
            }
        }

        return response()->json(['success' => true, 'cart' => $snapshot]);
    }

    public function remove(Request $request, CartSessionService $cart, TebexApiService $api)
    {
        $data = $request->validate(['package_id' => 'required|integer']);
        $snapshot = $cart->removeItem((int) $data['package_id']);

        $basketIdent = session('tebex.basket.ident');
        if ($basketIdent) {
            try {
                $api->removeBasketPackage($basketIdent, (int) $data['package_id']);
            } catch (\Throwable $e) {
                // Keep local cart update even if remote sync fails.
            }
        }

        return response()->json(['success' => true, 'cart' => $snapshot]);
    }

    public function clear(Request $request, CartSessionService $cart)
    {
        $cart->clear();

        session()->forget(['tebex.basket.ident', 'tebex.creator_code']);

        return redirect()->route('tebex.cart.show')->with('success', trans('tebex::messages.cart.cleared'));
    }

    public function paymentComplete(CartSessionService $cart)
    {
        $cart->clear();
        session()->forget(['tebex.basket.ident', 'tebex.creator_code']);
        return redirect()->route('tebex.cart.show')->with('success', trans('tebex::messages.payment.success'));
    }

    public function paymentFailed()
    {
        return redirect()->route('tebex.cart.show')->with('error', trans('tebex::messages.payment.failed'));
    }

    public function setUsername(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|min:3|max:25',
        ]);

        session()->put('tebex.username', $data['username']);

        return redirect()->back()->with('success', trans('tebex::messages.cart.username_updated'));
    }
}
