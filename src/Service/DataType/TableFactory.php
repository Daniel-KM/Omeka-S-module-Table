<?php declare(strict_types=1);

namespace Table\Service\DataType;

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Omeka\Api\Exception\NotFoundException;
use Psr\Container\ContainerInterface;
use Table\DataType\Table;

class TableFactory implements AbstractFactoryInterface
{
    public function canCreate(ContainerInterface $services, $requestedName)
    {
        if (!preg_match('/^table:(\d+)$/', $requestedName, $matches)) {
            return false;
        }
        try {
            $services->get('Omeka\ApiManager')->read('tables', $matches[1]);
        } catch (NotFoundException $e) {
            return false;
        }
        return true;
    }

    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $id = (int) substr($requestedName, strrpos($requestedName, ':') + 1);
        $table = $services->get('Omeka\ApiManager')->read('tables', $id)->getContent();
        return new Table($table);
    }
}
