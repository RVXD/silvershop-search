<?php

namespace SilverShop\Search\Tests;

use SilverShop\Search\Backend\DbSearchBackend;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\Dev\SapphireTest;

class DbSearchBackendTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/products.yml';

    protected static bool $use_draft_site = true;

    public function testFullTextMatchesTitleAndContent(): void
    {
        $results = (new DbSearchBackend())->query(new SearchQuery(terms: 'wool'));

        // "Wool Socks", "Wool Hat" (titles) + "Matching wool hat" (content) → socks + hat; not the jacket.
        $this->assertSame(2, $results->total);

        $results = (new DbSearchBackend())->query(new SearchQuery(terms: 'jacket'));
        $this->assertSame(1, $results->total);
    }

    public function testSortByPriceAscending(): void
    {
        $results = (new DbSearchBackend())->query(new SearchQuery(sort: 'price_asc'));

        $this->assertSame(3, $results->total);
        $this->assertSame('Wool Socks', $results->hits->first()->Title, 'cheapest (12.50) first');
    }

    public function testPriceFacetSelectionNarrowsResults(): void
    {
        $results = (new DbSearchBackend())->query(new SearchQuery(filters: ['price' => ['50-100']]));

        $this->assertSame(1, $results->total, 'only the 89.00 jacket is in 50–100');
        $this->assertSame('Rain Jacket', $results->hits->first()->Title);
    }

    public function testResultsCarryFacetData(): void
    {
        $results = (new DbSearchBackend())->query(new SearchQuery());

        $this->assertNotEmpty($results->facets, 'facet data is returned');
        $names = array_column($results->facets, 'name');
        $this->assertContains('price', $names, 'the price facet is present');
        $this->assertContains('category', $names, 'the category facet is present');
    }
}
