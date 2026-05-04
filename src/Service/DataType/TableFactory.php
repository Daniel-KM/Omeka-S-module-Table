<?php declare(strict_types=1);

namespace Table\Service\DataType;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Psr\Container\ContainerInterface;
use Table\DataType\Table;

class TableFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $services, $requestedName)
    {
        if (!preg_match('/^table:([a-z0-9_-]+)$/', $requestedName, $matches)) {
            return false;
        }
        $base = $matches[1];
        $api = $services->get('Omeka\ApiManager');
        // Match by exact slug.
        if ($api->search('tables', ['slug' => $base, 'limit' => 1])->getTotalResults()) {
            return true;
        }
        // Or by base slug among siblings.
        foreach ($api->search('tables')->getContent() as $t) {
            if ($t->baseSlug() === $base) {
                return true;
            }
        }
        return false;
    }

    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $base = substr($requestedName, strrpos($requestedName, ':') + 1);
        return new Table($base, $services->get('Omeka\ApiManager'));
    }
}
