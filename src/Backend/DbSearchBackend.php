<?php

declare(strict_types=1);

namespace SilverShop\Search\Backend;

use SilverShop\Page\Product;
use SilverShop\Search\Facet\FacetRegistry;
use SilverShop\Search\Query\SearchQuery;
use SilverShop\Search\Query\SearchResults;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Model\List\SS_List;
use SilverStripe\ORM\DataList;

/**
 * The default, zero-infrastructure backend: queries the product tables directly and drives the registered facets.
 * Correct and dependency-free, but it does not do typo-tolerance, synonyms, instant-search or relevance ranking —
 * those are what the engine adapters (Algolia/Meilisearch/Typesense/Discoverer) are for. Reads the current Versioned
 * stage, so the storefront (Live) and tests (draft) both work.
 */
class DbSearchBackend implements SearchBackend
{
    use Configurable;

    /**
     * Product fields matched by the free-text query (PartialMatch, OR'd).
     *
     * @var array<int, string>
     */
    private static array $search_fields = ['Title', 'Content', 'InternalItemID', 'Model'];

    /**
     * When true, only products flagged AllowPurchase are searchable.
     */
    private static bool $only_purchasable = false;

    public function getName(): string
    {
        return 'db';
    }

    public function getTitle(): string
    {
        return _t(self::class . '.TITLE', 'Database (built-in)');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function query(SearchQuery $query): SearchResults
    {
        $facets = (new FacetRegistry())->all();
        $base = $this->baseList($query);

        // Final result set: the base list with every facet's selection applied (AND across facets).
        $filtered = $base;
        foreach ($facets as $facet) {
            $filtered = $facet->apply($filtered, $query->selected($facet->getName()));
        }
        $filtered = $this->applySort($filtered, $query->sort);

        // Facet options: each facet is counted against the base list with every OTHER facet's selection applied, so a
        // facet still shows its alternatives after you pick one of its own values (standard faceted-search behaviour).
        $facetData = [];
        foreach ($facets as $facet) {
            $context = $base;
            foreach ($facets as $other) {
                if ($other === $facet) {
                    continue;
                }
                $context = $other->apply($context, $query->selected($other->getName()));
            }
            $facetData[] = [
                'name' => $facet->getName(),
                'title' => $facet->getTitle(),
                'options' => $facet->getOptions($context, $query),
            ];
        }

        return new SearchResults($filtered, (int) $filtered->count(), $facetData);
    }

    public function index(SS_List $products): void
    {
        // No-op: the DB backend queries live tables, nothing to index.
    }

    public function remove(int $productID): void
    {
        // No-op.
    }

    public function supportsTypoTolerance(): bool
    {
        return false;
    }

    public function supportsSynonyms(): bool
    {
        return false;
    }

    public function supportsInstant(): bool
    {
        return false;
    }

    private function baseList(SearchQuery $query): DataList
    {
        $list = Product::get();

        if ((bool) self::config()->get('only_purchasable')) {
            $list = $list->filter('AllowPurchase', true);
        }

        if ($query->hasTerms()) {
            $terms = trim($query->terms);
            $any = [];
            foreach ((array) self::config()->get('search_fields') as $field) {
                $any[$field . ':PartialMatch'] = $terms;
            }
            if ($any) {
                $list = $list->filterAny($any);
            }
        }

        return $list;
    }

    private function applySort(DataList $list, string $sort): DataList
    {
        return match ($sort) {
            'price_asc' => $list->sort('BasePrice', 'ASC'),
            'price_desc' => $list->sort('BasePrice', 'DESC'),
            'newest' => $list->sort('Created', 'DESC'),
            'title' => $list->sort('Title', 'ASC'),
            default => $list,
        };
    }
}
