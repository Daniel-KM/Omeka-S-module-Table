<?php declare(strict_types=1);

namespace Table;

use Common\Stdlib\PsrMessage;

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
$translate = $plugins->get('translate');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.66')) {
    $message = new \Omeka\Stdlib\Message(
        $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
        'Common', '3.4.66'
    );
    throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
}

if (version_compare($oldVersion, '3.4.1', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `tables`
CHANGE `title` `title` varchar(190) NOT NULL AFTER `owner_id`,
ADD`source` TEXT DEFAULT NULL AFTER `title`,
ADD `comment` TEXT DEFAULT NULL AFTER `source`,
CHANGE `slug` `slug` varchar(190) NOT NULL AFTER `comment`;
SQL;
    $connection->executeStatement($sql);
}

if (version_compare($oldVersion, '3.4.3', '<')) {
    $sql = <<<'SQL'
ALTER TABLE `tables`
DROP INDEX `idx_table_slug`;
SQL;
    try {
        $connection->executeStatement($sql);
    } catch (\Exception $e) {
        // No index.
    }

    $sql = <<<'SQL'
ALTER TABLE `tables`
CHANGE `slug` `slug` varchar(190) NOT NULL AFTER `owner_id`,
ADD `is_associative` tinyint(1) NOT NULL DEFAULT 0 AFTER `slug`,
CHANGE `title` `title` varchar(190) NOT NULL AFTER `is_associative`,
CHANGE `lang` `lang` varchar(190) DEFAULT NULL AFTER `title`,
CHANGE `source` `source` text DEFAULT NULL AFTER `lang`,
CHANGE `comment` `comment` text DEFAULT NULL AFTER `source`;
SQL;
    $connection->executeStatement($sql);

    $sql = <<<'SQL'
UPDATE `tables`
SET `is_associative` = 1;
SQL;
    $connection->executeStatement($sql);

    $sql = <<<'SQL'
ALTER TABLE `table_code`
ADD `lang` varchar(190) DEFAULT NULL AFTER `label`;
SQL;
    $connection->executeStatement($sql);

    $message = new PsrMessage(
        'It is now possible to create table with multiple labels for one code.' // @translate
    );
    $messenger->addSuccess($message);

    $message = new PsrMessage(
        'It is now possible to set a language to labels. When set, all labels should have a different language.' // @translate
    );
    $messenger->addSuccess($message);

    $message = new PsrMessage(
        'The api output did not change for associative tables, but is different for tables with multiple labels. Check your code if needed.' // @translate
    );
    $messenger->addWarning($message);
}
