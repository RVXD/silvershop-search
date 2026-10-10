<?php

declare(strict_types=1);

namespace SilverShop\Search\Form;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;

/**
 * The storefront search box. GETs its query back to the SearchPage, so results are a bookmarkable URL. The current
 * query is pre-filled from the request, and the CSRF token is disabled (a GET search needs none).
 */
class ProductSearchForm extends Form
{
    public function __construct(Controller $controller, string $name)
    {
        $fields = FieldList::create(
            TextField::create('q', _t(self::class . '.LABEL', 'Search'))
                ->setAttribute('placeholder', _t(self::class . '.PLACEHOLDER', 'Search products…'))
                ->setAttribute('type', 'search')
        );

        $actions = FieldList::create(
            FormAction::create('results', _t(self::class . '.SUBMIT', 'Search'))
        );

        parent::__construct($controller, $name, $fields, $actions);

        $this->setFormMethod('GET');
        $this->setFormAction($controller->Link());
        $this->disableSecurityToken();
        $this->loadDataFrom($controller->getRequest()->getVars());
        $this->addExtraClass('silvershop-search-form');
    }
}
