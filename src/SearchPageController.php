<?php

declare(strict_types=1);

namespace SilverShop\Search;

use PageController;
use SilverShop\Search\Backend\BackendRegistry;
use SilverShop\Search\Facet\FacetRegistry;
use SilverShop\Search\Form\ProductSearchForm;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Model\ArrayData;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\Model\List\PaginatedList;

/**
 * Runs the query through the active backend and renders results + facets. The search form GETs back here, so the
 * whole state lives in the URL (shareable, cacheable). Facet links toggle a value on/off and reset paging.
 */
class SearchPageController extends PageController
{
    private static array $allowed_actions = ['SearchForm'];

    public function index(HTTPRequest $request)
    {
        $facetNames = (new FacetRegistry())->names();
        $pageLength = (int) $this->data()->config()->get('products_per_page');
        $query = SearchQuery::fromRequest($request, $facetNames, $pageLength);

        $results = (new BackendRegistry())->getActive()->query($query);
        $this->recordSearch($query, $results->total);

        $paginated = PaginatedList::create($results->hits, $request)->setPageLength($pageLength);

        return $this->customise([
            'Query' => $query->terms,
            'Total' => $results->total,
            'Results' => $paginated,
            'Facets' => $this->facetList($results->facets),
            'SearchForm' => $this->SearchForm(),
        ]);
    }

    public function SearchForm(): ProductSearchForm
    {
        return ProductSearchForm::create($this, 'SearchForm');
    }

    /**
     * Turn the backend's facet arrays into template data, each option carrying a toggle Link.
     *
     * @param array<int, array<string, mixed>> $facets
     */
    private function facetList(array $facets): ArrayList
    {
        $list = ArrayList::create();
        foreach ($facets as $facet) {
            $options = ArrayList::create();
            foreach ($facet['options'] as $option) {
                $options->push(ArrayData::create([
                    'Value' => $option['value'],
                    'Label' => $option['label'],
                    'Count' => $option['count'],
                    'Active' => $option['active'],
                    'Link' => $this->toggleLink($facet['name'], (string) $option['value']),
                ]));
            }
            $list->push(ArrayData::create([
                'Name' => $facet['name'],
                'Title' => $facet['title'],
                'Options' => $options,
            ]));
        }

        return $list;
    }

    /**
     * The current URL with one facet value toggled on/off (OR within a facet), paging reset.
     */
    public function toggleLink(string $facet, string $value): string
    {
        $vars = $this->cleanVars();

        $current = isset($vars[$facet])
            ? array_values(array_filter(array_map('trim', explode(',', (string) $vars[$facet])), 'strlen'))
            : [];

        if (in_array($value, $current, true)) {
            $current = array_values(array_diff($current, [$value]));
        } else {
            $current[] = $value;
        }

        if ($current) {
            $vars[$facet] = implode(',', $current);
        } else {
            unset($vars[$facet]);
        }

        $qs = http_build_query($vars);

        return $this->Link() . ($qs ? '?' . $qs : '');
    }

    /**
     * Request GET vars minus routing/form noise, so rebuilt links stay clean.
     *
     * @return array<string, mixed>
     */
    private function cleanVars(): array
    {
        $vars = $this->getRequest()->getVars();
        unset($vars['url'], $vars['flush'], $vars['start']);
        foreach (array_keys($vars) as $key) {
            if (str_starts_with((string) $key, 'action_')) {
                unset($vars[$key]);
            }
        }

        return $vars;
    }

    /**
     * Hand the query + result count to silvershop/analytics when it is installed (unblocks its search-term reports).
     * Dormant — and non-fatal — when analytics is absent.
     */
    private function recordSearch(SearchQuery $query, int $total): void
    {
        if (!$query->hasTerms()) {
            return;
        }
        $recorder = 'SilverShop\\Analytics\\SearchInsights';
        if (method_exists($recorder, 'record')) {
            $recorder::record($query->terms, $total);
        }
    }
}
