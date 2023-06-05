<?php declare(strict_types=1);

namespace Table;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (version_compare($oldVersion, '3.4.1', '<')) {
    $sql = <<<SQL
ALTER TABLE `tables`
CHANGE `title` `title` varchar(190) NOT NULL AFTER `owner_id`,
ADD`source` TEXT DEFAULT NULL AFTER `title`,
ADD `comment` TEXT DEFAULT NULL AFTER `source`,
CHANGE `slug` `slug` varchar(190) NOT NULL AFTER `comment`;
SQL;
    $connection->executeStatement($sql);
}
