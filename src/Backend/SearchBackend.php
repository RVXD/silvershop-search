<?php

declare(strict_types=1);

namespace SilverShop\Search\Backend;

use SilverShop\Search\Query\SearchQuery;
use SilverShop\Search\Query\SearchResults;
use SilverStripe\Model\List\SS_List;

/**
 * A search backend runs a {@link SearchQuery} and returns {@link SearchResults}. The default {@link DbSearchBackend}
 * queries the product tables directly (no infra); engine adapters (Algolia, Meilisearch, Typesense, Discoverer) ship
 * as separate modules implementing this same interface and are selected in config.
 *
 * Capability flags let the UI hide what the active backend can't do — the DB backend returns false for all of them.
 */
interface SearchBackend
{
    public function getName(): string;

    public function getTitle(): string;

    /**
     * Whether the backend has the configuration (API key, host, …) it needs. The DB backend is always configured.
     */
    public function isConfigured(): bool;

    public function query(SearchQuery $query): SearchResults;

    /**
     * (Re)index products — a no-op for the DB backend (it queries live tables). Engine adapters push to their index.
     */
    public function index(SS_List $products): void;

    /**
     * Drop one product from the index — a no-op for the DB backend.
     */
    public function remove(int $productID): void;

    public function supportsTypoTolerance(): bool;

    public function supportsSynonyms(): bool;

    public function supportsInstant(): bool;
}
