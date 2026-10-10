<?php

namespace SilverShop\Search\Tests;

use SilverShop\Page\Product;
use SilverShop\Search\Facet\PriceFacet;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\Dev\SapphireTest;

class PriceFacetTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/products.yml';

    protected static bool $use_draft_site = true;

    public function testApplyFiltersToASingleBucket(): void
    {
        $facet = new PriceFacet();

        // 0–25 → socks (12.50) + hat (22.00); not the 89.00 jacket.
        $this->assertSame(2, $facet->apply(Product::get(), ['0-25'])->count());
        // 50–100 → jacket only.
        $this->assertSame(1, $facet->apply(Product::get(), ['50-100'])->count());
    }

    public function testApplyOrsMultipleBuckets(): void
    {
        $facet = new PriceFacet();

        $this->assertSame(3, $facet->apply(Product::get(), ['0-25', '50-100'])->count(), 'two buckets are OR-ed');
    }

    public function testApplyWithNoSelectionIsUnfiltered(): void
    {
        $facet = new PriceFacet();

        $this->assertSame(3, $facet->apply(Product::get(), [])->count());
    }

    public function testOptionsCarryCountsAndActiveFlag(): void
    {
        $facet = new PriceFacet();
        $query = new SearchQuery(filters: ['price' => ['0-25']]);

        $options = $facet->getOptions(Product::get(), $query);

        // Empty buckets (25–50, 100+) are hidden → only 0–25 and 50–100 remain.
        $byValue = [];
        foreach ($options as $option) {
            $byValue[$option['value']] = $option;
        }

        $this->assertArrayHasKey('0-25', $byValue);
        $this->assertArrayHasKey('50-100', $byValue);
        $this->assertArrayNotHasKey('25-50', $byValue, 'zero-count bucket hidden');
        $this->assertSame(2, $byValue['0-25']['count']);
        $this->assertTrue($byValue['0-25']['active'], 'selected bucket is marked active');
        $this->assertFalse($byValue['50-100']['active']);
        $this->assertNotSame('', (string) $byValue['0-25']['label'], 'label is rendered');
    }
}
