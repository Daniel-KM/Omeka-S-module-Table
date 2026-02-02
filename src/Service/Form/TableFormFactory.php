<?php declare(strict_types=1);

namespace Table\Service\Form;

use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use Table\Form\TableForm;

class TableFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $element = new TableForm(null, $options ?? []);
        return $element
            ->setApiAdapterTable($services->get('Omeka\ApiAdapterManager')->get('tables'))
            ->setTranslator($services->get(TranslatorInterface::class));
    }
}
