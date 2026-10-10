<?php

declare(strict_types=1);

namespace SilverShop\Search\Query;

use SilverStripe\Model\List\SS_List;

/**
 * Immutable result of a backend query: the matching products (filtered + sorted, not yet paged — the controller
 * paginates), the total match count, and the facet data to render (each facet with its counted options).
 *
 * `facets` shape: list of ['name' => string, 'title' => string, 'options' => [['value','label','count','active'], …]].
 */
class SearchResults
{
    /**
     * @param array<int, array<string, mixed>> $facets
     */
    public function __construct(
        public readonly SS_List $hits,
        public readonly int $total = 0,
        public readonly array $facets = [],
    ) {
    }
}
