<?php

declare(strict_types=1);

namespace SilverShop\Search\Query;

use SilverStripe\Control\HTTPRequest;

/**
 * Immutable description of one search request: free-text terms, selected facet values, sort and paging. Backends
 * consume it; the facet framework reads selected values from it. Facet values use OR-within / AND-across semantics
 * (several values for one facet widen that facet; different facets narrow together).
 */
class SearchQuery
{
    /**
     * @param array<string, array<int|string>> $filters facet name => selected values
     */
    public function __construct(
        public readonly string $terms = '',
        public readonly array $filters = [],
        public readonly string $sort = '',
        public readonly int $start = 0,
        public readonly int $length = 12,
    ) {
    }

    /**
     * Selected values for one facet (empty when none chosen).
     *
     * @return array<int|string>
     */
    public function selected(string $facet): array
    {
        return array_values((array) ($this->filters[$facet] ?? []));
    }

    public function hasTerms(): bool
    {
        return trim($this->terms) !== '';
    }

    /**
     * Build a query from request vars: `q` (terms), `sort`, `start` (paging), and one comma-separated var per facet
     * name (e.g. `?brand=5,8&price=25-50`).
     *
     * @param array<int, string> $facetNames the registered facet names to read values for
     */
    public static function fromRequest(HTTPRequest $request, array $facetNames, int $length = 12): self
    {
        $filters = [];
        foreach ($facetNames as $name) {
            $raw = $request->getVar($name);
            if ($raw === null || $raw === '') {
                continue;
            }
            $values = array_values(array_filter(array_map('trim', explode(',', (string) $raw)), 'strlen'));
            if ($values) {
                $filters[$name] = $values;
            }
        }

        return new self(
            (string) ($request->getVar('q') ?? ''),
            $filters,
            (string) ($request->getVar('sort') ?? ''),
            max(0, (int) $request->getVar('start')),
            $length
        );
    }
}
