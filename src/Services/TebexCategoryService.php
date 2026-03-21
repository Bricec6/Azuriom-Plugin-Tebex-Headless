<?php

namespace Azuriom\Plugin\Tebex\Services;

class TebexCategoryService
{
    private TebexApiService $tebexApi;

    public function __construct(TebexApiService $tebexApi)
    {
        $this->tebexApi = $tebexApi;
    }

    public function getCategoriesData($token, $includePackages = true)
    {
        $categoriesData = $this->tebexApi->getCategories($token, $includePackages);
        $pricingData = $this->tebexApi->getPackagesWithPricing($token);

        $pricingMap = [];
        if ($pricingData && isset($pricingData['data'])) {
            foreach ($pricingData['data'] as $p) {
                $pricingMap[$p['id']] = $p;
            }
        }

        $allPackages = [];
        $transformedCategories = [];

        if ($categoriesData && isset($categoriesData['data'])) {
            foreach ($categoriesData['data'] as $category) {
                $catObj = (object) [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'packages' => [],
                    'subcategories' => []
                ];

                if (isset($category['packages']) && is_array($category['packages'])) {
                    foreach ($category['packages'] as $pkg) {
                        $pObj = $this->mapPackageToObject($pkg, $category['id'], $pricingMap);
                        $allPackages[] = $pObj;
                        $catObj->packages[] = (object)['id' => $pkg['id']];
                    }
                }

                if (isset($category['subcategories']) && is_array($category['subcategories'])) {
                    foreach ($category['subcategories'] as $subcat) {
                        $subObj = (object) [
                            'id' => $subcat['id'],
                            'name' => $subcat['name'],
                            'packages' => []
                        ];

                        if (isset($subcat['packages']) && is_array($subcat['packages'])) {
                            foreach ($subcat['packages'] as $pkg) {
                                $pObj = $this->mapPackageToObject($pkg, $subcat['id'], $pricingMap);
                                $allPackages[] = $pObj;
                                $subObj->packages[] = (object)['id' => $pkg['id']];
                            }
                        }
                        $catObj->subcategories[] = $subObj;
                    }
                }
                $transformedCategories[] = $catObj;
            }
        }

        return [$allPackages, (object) ['categories' => $transformedCategories], (object) ['data' => []]];
    }

    private function mapPackageToObject($pkg, $catId, $pricingMap = [])
    {
        $priceData = $pricingMap[$pkg['id']] ?? ['_debug_source' => 'categories_endpoint', '_debug_data' => $pkg];

        return (object) [
            'id' => $pkg['id'],
            'name' => $pkg['name'],
            'description' => $pkg['description'] ?? '',
            'price' => $priceData['price'] ?? ($pkg['price'] ?? 0),
            'base_price' => $priceData['base_price'] ?? null,
            'total_price' => $priceData['total_price'] ?? null,
            'discount' => $priceData['discount'] ?? 0,
            'sales_tax' => $priceData['sales_tax'] ?? 0,
            'image' => $pkg['image'] ?? '',
            'disabled' => false,
            'category' => (object) ['id' => $catId]
        ];
    }

    public function processCategories($rProducts, $rCategories, $rSales)
    {
        if (!isset($rCategories->categories) || !is_array($rCategories->categories)) {
            return [];
        }

        $productsMap = [];
        foreach ($rProducts as $prod) {
            $productsMap[$prod->id] = $prod;
        }

        $categories = [];

        foreach ($rCategories->categories as $cate) {
            $packages = [];

            if (isset($cate->packages) && is_array($cate->packages)) {
                foreach ($cate->packages as $catPkg) {
                    if (isset($productsMap[$catPkg->id]) && !$productsMap[$catPkg->id]->disabled) {
                        $packages[] = $this->formatProduct($productsMap[$catPkg->id], $rSales);
                    }
                }
            }

            $subCategories = [];
            if (isset($cate->subcategories) && is_array($cate->subcategories)) {
                foreach ($cate->subcategories as $sub) {
                    $subPkgs = [];
                    if (isset($sub->packages) && is_array($sub->packages)) {
                        foreach ($sub->packages as $subPkg) {
                            if (isset($productsMap[$subPkg->id]) && !$productsMap[$subPkg->id]->disabled) {
                                $subPkgs[] = $this->formatProduct($productsMap[$subPkg->id], $rSales);
                            }
                        }
                    }
                    $subCategories[] = (object) [
                        'id' => $sub->id,
                        'name' => $sub->name,
                        'packages' => $subPkgs
                    ];
                }
            }

            $categories[] = (object) [
                'id' => $cate->id,
                'name' => $cate->name,
                'packages' => $packages,
                'subcategories' => $subCategories
            ];
        }

        return $categories;
    }

    public function formatProduct($Product, $rSales)
    {
        $showVat = setting('tebex.shop.vat.status', false);

        $currentBase = $Product->base_price ?? $Product->price;
        $currentTotal = $Product->total_price ?? $currentBase;

        $discountBase = $Product->discount ?? 0;

        $taxMultiplier = 1;
        if ($currentBase > 0) {
            $taxMultiplier = $currentTotal / $currentBase;
        }

        $currentPrice = $showVat ? $currentTotal : $currentBase;
        $originalPrice = $currentPrice + ($discountBase * ($showVat ? $taxMultiplier : 1));

        $product = (object) [
            'id' => $Product->id,
            'name' => $Product->name,
            'image' => $Product->image,
            'description' => $Product->description,
            "price" => (object) [
                "normal" => round($originalPrice, 2),
                "discounted" => ($discountBase > 0) ? round($currentPrice, 2) : null,
                "expire" => null,
            ],
            "sales" => []
        ];

        if (isset($rSales->data) && is_array($rSales->data)) {
            foreach ($rSales->data as $sale) {
                $isEffective = false;

                if ($sale->effective->type === 'all') $isEffective = true;
                elseif ($sale->effective->type === 'package' && in_array($Product->id, $sale->effective->packages)) $isEffective = true;
                elseif ($sale->effective->type === 'category' && in_array($Product->category->id, $sale->effective->categories)) $isEffective = true;

                if ($isEffective) {
                    $discountVal = $sale->discount->type === "percentage"
                        ? $Product->price * ($sale->discount->percentage / 100)
                        : $sale->discount->value;

                    $newPrice = max(0, $Product->price - $discountVal);

                    $product->price->discounted = round($newPrice, 2);
                    $product->price->expire = date('d/m/y H:i:s', $sale->expire);
                    $product->sales[] = $sale->discount;
                }
            }
        }

        return $product;
    }
}
