<?php

declare(strict_types=1);

namespace SilverShop\Search\Facet;

/**
 * A registered entry that expands into zero or more concrete {@link SearchFacet}s at runtime — for dimensions that
 * are data-driven rather than fixed. The attribute facet uses this to produce one facet per product attribute type
 * (Colour, Size, …). The {@link FacetRegistry} expands these in place; unavailable providers are skipped.
 */
interface ExpandableFacet
{
    /**
     * Whether this provider can contribute any facets (e.g. the underlying data exists).
     */
    public function isAvailable(): bool;

    /**
     * The concrete facets to register, in display order.
     *
     * @return array<int, SearchFacet>
     */
    public function expand(): array;
}
