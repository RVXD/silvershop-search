<?php

declare(strict_types=1);

namespace SilverShop\Search\Facet;

use SilverShop\Model\Variation\AttributeType;

/**
 * Expands into one {@link AttributeTypeFacet} per product attribute type (Colour, Size, …) defined in the shop.
 * Self-guarding: contributes nothing when no attribute types exist, so it only appears on shops that use product
 * variations. Attribute types come from silvershop/core's variation support, which is always present.
 */
class AttributeFacet implements ExpandableFacet
{
    public function isAvailable(): bool
    {
        return AttributeType::get()->exists();
    }

    public function expand(): array
    {
        $facets = [];
        foreach (AttributeType::get() as $type) {
            $facets[] = new AttributeTypeFacet($type);
        }

        return $facets;
    }
}
