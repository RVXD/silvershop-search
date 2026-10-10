<?php

declare(strict_types=1);

namespace SilverShop\Search\Facet;

use SilverShop\Page\Product;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\ORM\DataList;

/**
 * An "In stock" availability toggle, active only when silvershop/stock is installed (detected by the
 * `hasAvailableStock()` method that module adds to the buyable). Availability is read from stock's own
 * authoritative `hasAvailableStock()` rather than reimplemented in SQL — stock's rules (per-warehouse levels,
 * unlimited flags, variations, cart-pending, digital-product overrides) are intentionally not duplicated here.
 *
 * Because that check is per-product (not a single query), this suits typical catalogues; very large catalogues that
 * need availability filtering at scale should use an engine backend. No hard dependency on silvershop/stock — the
 * facet simply reports itself unavailable (and is skipped) when the module is absent.
 */
class StockFacet implements SearchFacet
{
    private const IN_STOCK = 'in';

    public function getName(): string
    {
        return 'stock';
    }

    public function getTitle(): string
    {
        return _t(self::class . '.TITLE', 'Availability');
    }

    public function isAvailable(): bool
    {
        return Product::singleton()->hasMethod('hasAvailableStock');
    }

    public function apply(DataList $products, array $selected): DataList
    {
        if (!in_array(self::IN_STOCK, array_map('strval', $selected), true)) {
            return $products;
        }

        return $products->filter('ID', $this->inStockIds($products) ?: [-1]);
    }

    public function getOptions(DataList $context, SearchQuery $query): array
    {
        $active = in_array(self::IN_STOCK, array_map('strval', $query->selected($this->getName())), true);
        $count = count($this->inStockIds($context));

        if ($count === 0 && !$active) {
            return [];
        }

        return [[
            'value' => self::IN_STOCK,
            'label' => _t(self::class . '.IN_STOCK', 'In stock'),
            'count' => $count,
            'active' => $active,
        ]];
    }

    /**
     * IDs of the products in $list that stock reports as available.
     *
     * @return array<int, int>
     */
    private function inStockIds(DataList $list): array
    {
        $ids = [];
        foreach ($list as $product) {
            if ($product->hasMethod('hasAvailableStock') && $product->hasAvailableStock()) {
                $ids[] = (int) $product->ID;
            }
        }

        return $ids;
    }
}
