<?php

namespace SilverShop\Search\Tests;

use SilverShop\Page\Product;
use SilverShop\Page\ProductCategory;
use SilverShop\Page\ProductCategoryController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Dev\SapphireTest;

/**
 * The category page's product listing narrows by the request's facet selections (core `updateProductsShowable` hook),
 * and the controller exposes facet data for a sidebar.
 */
class CategoryNavTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static bool $use_draft_site = true;

    private function category(): ProductCategory
    {
        $category = ProductCategory::create(['Title' => 'Shop', 'URLSegment' => 'shop']);
        $category->write();
        foreach ([10, 30, 90] as $price) {
            Product::create([
                'Title' => 'P' . $price,
                'BasePrice' => $price,
                'ParentID' => $category->ID,
                'AllowPurchase' => true,
            ])->write();
        }

        return $category;
    }

    /**
     * Run $fn with a category controller current and carrying the given GET vars.
     */
    private function withController(ProductCategory $category, array $vars, callable $fn): mixed
    {
        $request = new HTTPRequest('GET', $category->RelativeLink(), $vars);
        $request->setSession(new Session([]));
        $controller = ProductCategoryController::create($category);
        $controller->setRequest($request);
        $controller->pushCurrent();
        try {
            return $fn($controller);
        } finally {
            $controller->popCurrent();
        }
    }

    public function testListingNarrowsByFacet(): void
    {
        $category = $this->category();

        $count = $this->withController($category, ['price' => '0-25'], fn () => $category->ProductsShowable()->count());
        $this->assertSame(1, $count, 'only the 10.00 product is in 0–25');
    }

    public function testListingUnfilteredWithoutFacetParams(): void
    {
        $category = $this->category();

        $count = $this->withController($category, [], fn () => $category->ProductsShowable()->count());
        $this->assertSame(3, $count);
    }

    public function testSearchFacetsReturnsSidebarData(): void
    {
        $category = $this->category();

        $names = $this->withController($category, [], function ($controller) {
            $facets = $controller->SearchFacets();
            $this->assertNotNull($facets);
            return $facets->column('Name');
        });
        $this->assertContains('price', $names, 'the price facet is offered on the category sidebar');
    }
}
