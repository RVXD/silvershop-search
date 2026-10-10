<?php

declare(strict_types=1);

namespace SilverShop\Search\Facet;

use SilverShop\Model\Variation\AttributeType;
use SilverShop\Search\Query\SearchQuery;
use SilverStripe\ORM\DataList;

/**
 * One attribute type as a facet (e.g. Colour, with options Red/Blue). Matches products that have a variation
 * carrying the selected attribute value(s): Product → Variations → AttributeValues. Values within the facet are
 * OR-ed; different attribute types (separate facets) AND together. The title is the type's front-end Label.
 */
class AttributeTypeFacet implements SearchFacet
{
    public function __construct(private readonly AttributeType $type)
    {
    }

    public function getName(): string
    {
        return 'attr-' . (int) $this->type->ID;
    }

    public function getTitle(): string
    {
        return (string) ($this->type->Label ?: $this->type->Name);
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function apply(DataList $products, array $selected): DataList
    {
        $ids = array_values(array_filter(array_map('intval', $selected)));
        if (!$ids) {
            return $products;
        }

        return $products->filter('Variations.AttributeValues.ID', $ids);
    }

    public function getOptions(DataList $context, SearchQuery $query): array
    {
        $selected = array_map('strval', $query->selected($this->getName()));
        $options = [];

        foreach ($this->type->Values() as $value) {
            $count = (int) $context->filter('Variations.AttributeValues.ID', (int) $value->ID)->count();
            $optionValue = (string) $value->ID;
            $active = in_array($optionValue, $selected, true);
            if ($count === 0 && !$active) {
                continue;
            }
            $options[] = [
                'value' => $optionValue,
                'label' => (string) $value->Value,
                'count' => $count,
                'active' => $active,
            ];
        }

        return $options;
    }
}
