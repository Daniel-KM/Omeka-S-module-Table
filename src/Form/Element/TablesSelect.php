<?php declare(strict_types=1);

namespace Table\Form\Element;

use Common\Form\Element\TraitGroupByOwner;
use Common\Form\Element\TraitOptionalElement;
use Omeka\Api\Manager as ApiManager;
use Omeka\Form\Element\AbstractGroupByOwnerSelect;

class TablesSelect extends AbstractGroupByOwnerSelect
{
    use TraitGroupByOwner;
    use TraitOptionalElement;

    public function setApiManager(ApiManager $apiManager): self
    {
        $this->api = $apiManager;
        return $this;
    }

    public function getResourceName()
    {
        return 'tables';
    }

    public function getValueLabel($resource)
    {
        return $resource->displayTitle();
    }

    public function getValueOptions(): array
    {
        return $this->getValueOptionsFix();
    }
}
