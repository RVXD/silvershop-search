<?php

declare(strict_types=1);

namespace SilverShop\Search;

use Page;

/**
 * The storefront search results page. Add one in the CMS to switch search on and give it a URL; its form submits a
 * query (GET) back to itself and the controller renders faceted, sorted, paginated results.
 */
class SearchPage extends Page
{
    private static string $table_name = 'SilverShop_SearchPage';

    private static string $singular_name = 'Search Page';

    private static string $plural_name = 'Search Pages';

    private static string $description = 'Faceted product search results page.';

    private static string $icon_class = 'font-icon-search';

    /** Results per page. */
    private static int $products_per_page = 12;
}
