<?php

namespace SilverShop\Search\Tests;

use SilverShop\Search\Query\SearchQuery;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;

class SearchQueryTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testParsesTermsFacetsSortAndPaging(): void
    {
        $request = new HTTPRequest('GET', '/search', [
            'q' => 'jacket',
            'brand' => '5,8',
            'price' => '25-50',
            'sort' => 'price_asc',
            'start' => '12',
        ]);

        $query = SearchQuery::fromRequest($request, ['brand', 'price'], 12);

        $this->assertSame('jacket', $query->terms);
        $this->assertSame(['5', '8'], $query->selected('brand'), 'comma-separated values split');
        $this->assertSame(['25-50'], $query->selected('price'));
        $this->assertSame('price_asc', $query->sort);
        $this->assertSame(12, $query->start);
        $this->assertTrue($query->hasTerms());
    }

    public function testUnknownFacetAndBlankTerms(): void
    {
        $query = SearchQuery::fromRequest(new HTTPRequest('GET', '/search'), ['brand'], 12);

        $this->assertSame('', $query->terms);
        $this->assertFalse($query->hasTerms());
        $this->assertSame([], $query->selected('brand'), 'no value for an unselected facet');
        $this->assertSame([], $query->selected('colour'), 'no value for an unregistered facet');
    }
}
