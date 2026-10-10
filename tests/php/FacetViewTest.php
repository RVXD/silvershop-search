<?php

namespace SilverShop\Search\Tests;

use SilverShop\Search\View\FacetView;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;

class FacetViewTest extends SapphireTest
{
    protected $usesDatabase = false;

    private function facetData(): array
    {
        return [[
            'name' => 'color',
            'title' => 'Color',
            'options' => [
                ['value' => '5', 'label' => 'Red', 'count' => 3, 'active' => false, 'swatch' => '#ff0000'],
                ['value' => '8', 'label' => 'Blue', 'count' => 2, 'active' => true],
            ],
        ]];
    }

    public function testCarriesSwatchWhenProvided(): void
    {
        $view = FacetView::build($this->facetData(), new HTTPRequest('GET', '/search'), '/search');

        $options = $view->first()->Options;
        $this->assertSame('#ff0000', $options->first()->Swatch, 'swatch passed through for the colour option');
        $this->assertNull($options->last()->Swatch, 'no swatch on an option that omits it');
    }

    public function testToggleLinkAddsAndRemovesValues(): void
    {
        $request = new HTTPRequest('GET', '/search', ['color' => '8']);

        // '5' not selected → adding it joins the CSV; '8' already selected → removing it drops the param.
        $this->assertStringContainsString('color=8%2C5', FacetView::toggleLink($request, '/search', 'color', '5'));
        $this->assertStringNotContainsString('color=', FacetView::toggleLink($request, '/search', 'color', '8'));
    }
}
