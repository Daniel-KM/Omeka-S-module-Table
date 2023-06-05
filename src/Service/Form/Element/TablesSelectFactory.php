<?php declare(strict_types=1);

namespace Table\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Table\Form\Element\TablesSelect;

class TablesSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new TablesSelect(null, $options ?? []);
        $element
            ->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}
