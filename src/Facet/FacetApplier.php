<?php

declare(strict_types=1);

namespace SilverShop\Search\Facet;

use SilverShop\Search\Query\SearchQuery;
use SilverStripe\ORM\DataList;

/**
 * Applies the registered facets to any product list and computes each facet's counted options. Shared by the search
 * backend and by the on-listing-page faceted navigation (category / brand pages), so all surfaces narrow and count
 * the same way. Each facet's options are counted against the base with every OTHER facet's selection applied, so a
 * facet keeps showing its alternatives after you pick one of its values.
 */
class FacetApplier
{
    /**
     * @return array{products: DataList, facets: array<int, array<string, mixed>>}
     */
    public static function apply(DataList $base, SearchQuery $query): array
    {
        $facets = (new FacetRegistry())->all();

        $filtered = $base;
        foreach ($facets as $facet) {
            $filtered = $facet->apply($filtered, $query->selected($facet->getName()));
        }

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

        return ['products' => $filtered, 'facets' => $facetData];
    }
}
