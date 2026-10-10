<?php

namespace SilverShop\Search\Tests;

use SilverShop\Search\Facet\StockFacet;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class StockFacetTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        StockFacetTestBuyable::class,
    ];

    private function seed(): void
    {
        StockFacetTestBuyable::create(['Title' => 'A', 'Stocked' => true])->write();
        StockFacetTestBuyable::create(['Title' => 'B', 'Stocked' => true])->write();
        StockFacetTestBuyable::create(['Title' => 'C', 'Stocked' => false])->write();
    }

    public function testApplyKeepsOnlyInStock(): void
    {
        $this->seed();
        $facet = new StockFacet();

        $this->assertSame(2, $facet->apply(StockFacetTestBuyable::get(), ['in'])->count());
        $this->assertSame(3, $facet->apply(StockFacetTestBuyable::get(), [])->count(), 'no selection = unfiltered');
    }

    public function testOptionCountsInStock(): void
    {
        $this->seed();

        $options = (new StockFacet())->getOptions(
            StockFacetTestBuyable::get(),
            new SearchQuery(filters: ['stock' => ['in']])
        );

        $this->assertCount(1, $options);
        $this->assertSame('in', $options[0]['value']);
        $this->assertSame(2, $options[0]['count']);
        $this->assertTrue($options[0]['active']);
    }

    public function testUnavailableWithoutStockModule(): void
    {
        // silvershop/stock is not installed in this module's tests, so core Product lacks hasAvailableStock().
        $this->assertFalse((new StockFacet())->isAvailable(), 'dormant unless silvershop/stock is present');
    }
}

/**
 * A minimal buyable exposing the hasAvailableStock() method silvershop/stock would add, so the facet's own
 * filtering can be exercised without pulling in the stock module.
 */
class StockFacetTestBuyable extends DataObject implements TestOnly
{
    private static $table_name = 'Search_StockFacetTestBuyable';

    private static $db = [
        'Title' => 'Varchar',
        'Stocked' => 'Boolean',
    ];

    public function hasAvailableStock(int $require = 1): bool
    {
        return (bool) $this->Stocked;
    }
}
