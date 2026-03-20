<?php

namespace Azuriom\Plugin\Tebex\Controllers;

use Illuminate\Support\Facades\Http;
use Azuriom\Http\Controllers\Controller;
use Azuriom\Plugin\Tebex\Services\TebexApiService;
use Azuriom\Plugin\Tebex\Services\TebexCategoryService;

class TebexHomeController extends Controller
{
    private TebexApiService $tebexApi;
    private TebexCategoryService $categoryService;

    public function __construct(TebexApiService $tebexApi, TebexCategoryService $categoryService)
    {
        $this->tebexApi = $tebexApi;
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        if(!setting('tebex.key')) {
            return redirect()->route('home')->with('error', trans('tebex::admin.errors.noApiKey'));
        }

        if (auth()->check()) {
            session()->put('tebex.username', auth()->user()->name);
        }

        $token = $this->tebexApi->getConfiguredPublicKeyOrAbort();
        list($rProducts, $rCategories, $rSales) = $this->categoryService->getCategoriesData($token, true);
        $categories = $this->categoryService->processCategories($rProducts, $rCategories, $rSales);

        if (! setting('tebex.shop.home_status', true) && ! empty($categories)) {
            request()->session()->reflash();

            return to_route('tebex.categories.show', $categories[0]->id);
        }

        $this->markPackagesInCart($categories);

        return view('tebex::index', ["categories" => $categories]);
    }

    public function show($id)
    {
        if(!setting('tebex.key')) {
            return redirect()->route('home')->with('error', trans('tebex::admin.errors.noApiKey'));
        }

        $token = $this->tebexApi->getConfiguredPublicKeyOrAbort();
        list($rProducts, $rCategories, $rSales) = $this->categoryService->getCategoriesData($token, true);
        $categories = $this->categoryService->processCategories($rProducts, $rCategories, $rSales);

        $this->markPackagesInCart($categories);

        $category = null;
        foreach ($categories as $cat) {
            if ($cat->id == $id) {
                $category = $cat;
                break;
            }
            foreach ($cat->subcategories as $subcat) {
                if ($subcat->id == $id) {
                    $category = $subcat;
                    break 2;
                }
            }
        }

        if (!$category) {
            return redirect()->route('tebex.index')->with('error', trans('tebex::admin.errors.category_not_found'));
        }


        if(auth()->check()) {
            session()->put('tebex.username', auth()->user()->name);
        }

        return view('tebex::categories.show', [
            'category' => $category,
            'categories' => $categories
        ]);
    }

    /**
     * Adds an 'isInCart' (bool) property to each package
     */
    private function markPackagesInCart(array $categories): void
    {
        $cartPackageIds = collect(tebex_cart_items())->pluck('package_id')->toArray();

        foreach ($categories as $category) {
            if (!empty($category->packages)) {
                foreach ($category->packages as $pkg) {
                    $pkg->isInCart = in_array($pkg->id, $cartPackageIds);
                }
            }

            if (!empty($category->subcategories)) {
                foreach ($category->subcategories as $sub) {
                    if (!empty($sub->packages)) {
                        foreach ($sub->packages as $subPkg) {
                            $subPkg->isInCart = in_array($subPkg->id, $cartPackageIds);
                        }
                    }
                }
            }
        }
    }
}
