<?php

namespace Azuriom\Plugin\Tebex\Controllers\Admin;

use Azuriom\Models\Setting;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\Tebex\Requests\TebexSettingsRequest;
use Azuriom\Plugin\Tebex\Services\TebexApiService;
use Azuriom\Plugin\Tebex\Services\TebexCategoryService;
use Illuminate\Support\Facades\Crypt;

class SettingController extends Controller
{
    private TebexApiService $tebexApi;
    private TebexCategoryService $categoryService;

    public function __construct(TebexApiService $tebexApi, TebexCategoryService $categoryService)
    {
        $this->tebexApi = $tebexApi;
        $this->categoryService = $categoryService;
    }
    /**
     * Display the tebex settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $encryptedPrivateKey = setting('tebex.private_key', '');
        $decryptedPrivateKey = '';

        if ($encryptedPrivateKey) {
            try {
                $decryptedPrivateKey = Crypt::decryptString($encryptedPrivateKey);
            } catch (\Exception $e) {
                $decryptedPrivateKey = '';
            }
        }

        $packages = [];
        $token = setting('tebex.key');

        if ($token) {
            list($rProducts) = $this->categoryService->getCategoriesData($token, true);
            $packages = $rProducts;
        }

        return view('tebex::admin.index', [
            'public_key' => setting('tebex.key', ''),
            'tebex_project_id' => setting('tebex.project_id', ''),
            'tebex_private_key' => $decryptedPrivateKey,
            'tebex_shop_title' => setting('tebex.shop.title', ''),
            'tebex_shop_subtitle' => setting('tebex.shop.subtitle', ''),
            'banners' => json_decode(setting('tebex.package_banners', '[]'), true),
            'packages' => $packages,
        ]);
    }


    /**
     * Update the tebex settings.
     *
     * @param  \Azuriom\Plugin\Tebex\Requests\TebexSettingsRequest  $request
     * @return \Illuminate\Http\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function save(TebexSettingsRequest $request)
    {
        $privateKey = $request->input('private_key');
        $encryptedPrivateKey = $privateKey ? Crypt::encryptString($privateKey) : '';

        Setting::updateSettings([
            'tebex.key' => $request->input('public_key'),
            'tebex.project_id' => $request->input('project_id'),
            'tebex.private_key' => $encryptedPrivateKey,
            'tebex.shop.title' => $request->input('tebex_title'),
            'tebex.shop.subtitle' => $request->input('tebex_subtitle'),
            'tebex.shop.home_status' => $request->input('home_status'),
            'tebex.shop.home.message' => $request->input('home_message'),
            'tebex.package_banners' => json_encode($request->input('banners', [])),
        ]);

        return redirect()->route('tebex.admin.index')
            ->with('success', trans('admin.settings.updated'));
    }

    public function testPublicToken(Request $request)
    {
        $data = $request->validate([
            'public_key' => ['required', 'string', 'max:255'],
        ], [
            'public_key.required' => trans('tebex::admin.errors.noApiKey'),
            'public_key.max' => trans('tebex::admin.validation.public_key_max'),
        ]);

        $checkApiKey = Http::get("https://headless.tebex.io/api/accounts/{$data['public_key']}");

        if ($checkApiKey->successful()) {
            return redirect()->route('tebex.admin.index')
                ->withInput()
                ->with('success', trans('tebex::admin.fields.token_test_success'));
        }

        return redirect()->route('tebex.admin.index')
            ->withInput()
            ->with('error', trans('tebex::admin.errors.token_test_failed'));
    }
}
