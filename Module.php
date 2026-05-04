<?php declare(strict_types=1);

namespace Table;

if (!class_exists('Common\TraitModule', false)) {
    require_once file_exists(dirname(__DIR__) . '/Common/src/TraitModule.php')
        ? dirname(__DIR__) . '/Common/src/TraitModule.php'
        : dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;

class Module extends AbstractModule
{
    use TraitModule;

    public const NAMESPACE = __NAMESPACE__;

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        // TODO Check if it is useful to set permissions to Code.

        $allRoles = $acl->getRoles();

        $acl
            // Anybody can search and read tables (mainly via api endpoint).
            ->allow(
                null,
                [
                    \Table\Api\Adapter\TableAdapter::class,
                ],
                [
                    'search',
                    'read',
                ]
            )
            ->allow(
                null,
                [
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ],
                [
                    'read',
                ]
            )

            // All backend roles can search read tables.
            ->allow(
                $allRoles,
                [
                    \Table\Controller\Admin\TableController::class,
                ],
                [
                    'index',
                    'browse',
                    'search',
                    'show',
                    'show-details',
                ]
            )

            // Author can manage own tables.
            // Reviewer can manage all tables and delete own ones.
            ->allow(
                [
                    $acl::ROLE_AUTHOR,
                    $acl::ROLE_REVIEWER,
                ],
                [
                    \Table\Controller\Admin\TableController::class,
                ],
                [
                    'add',
                    'edit',
                    'delete',
                    'delete-confirm',
                    'batch-edit',
                    'batch-delete',
                ]
            )
            ->allow(
                [
                    $acl::ROLE_AUTHOR,
                    $acl::ROLE_REVIEWER,
                ],
                [
                    \Table\Api\Adapter\TableAdapter::class,
                ],
                [
                    'create',
                    'update',
                    'delete',
                    'batch_update',
                    'batch_delete',
                ]
            )
            ->allow(
                [
                    $acl::ROLE_AUTHOR,
                    $acl::ROLE_REVIEWER,
                ],
                [
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ],
                [
                    'read',
                    'create',
                ]
            )
            ->allow(
                [
                    $acl::ROLE_AUTHOR,
                ],
                [
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ],
                [
                    'update',
                    'delete',
                ],
                new OwnsEntityAssertion()
            )
            ->allow(
                [
                    $acl::ROLE_REVIEWER,
                ],
                [
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ],
                [
                    'update',
                ]
            )
            ->allow(
                [
                    $acl::ROLE_REVIEWER,
                ],
                [
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ],
                [
                    'delete',
                ],
                new OwnsEntityAssertion()
            )

            // Editor, Supervisor and SuperAdmin have all rights.
            ->allow(
                [
                    $acl::ROLE_EDITOR,
                    $acl::ROLE_SITE_ADMIN,
                    $acl::ROLE_GLOBAL_ADMIN,
                ],
                [
                    \Table\Controller\Admin\TableController::class,
                    \Table\Api\Adapter\TableAdapter::class,
                    \Table\Entity\Table::class,
                    \Table\Entity\Code::class,
                ]
            )

            // Reviewer, Editor and admins can see private tables (Reviewer can
            // already update all tables, so they must be able to browse them).
            ->allow(
                [
                    $acl::ROLE_REVIEWER,
                    $acl::ROLE_EDITOR,
                    $acl::ROLE_SITE_ADMIN,
                    $acl::ROLE_GLOBAL_ADMIN,
                ],
                [
                    \Table\Entity\Table::class,
                ],
                ['view-all']
            )
        ;
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        $sharedEventManager->attach(
            'Omeka\DataType\Manager',
            'service.registered_names',
            [$this, 'registerTableDataTypeNames']
        );
        $sharedEventManager->attach(
            \Table\Entity\Table::class,
            'entity.remove.pre',
            [$this, 'resetTableDataTypeOnRemove']
        );
        $sharedEventManager->attach(
            '*',
            'data_types.value_annotating',
            [$this, 'addTableDataTypesToValueAnnotating']
        );
    }

    public function addTableDataTypesToValueAnnotating(Event $event): void
    {
        $tables = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('tables')->getContent();
        $bases = [];
        foreach ($tables as $table) {
            $bases[$table->baseSlug()] = true;
        }
        $valueAnnotating = $event->getParam('data_types');
        foreach (array_keys($bases) as $base) {
            $valueAnnotating[] = 'table:' . $base;
        }
        $event->setParam('data_types', $valueAnnotating);
    }

    public function registerTableDataTypeNames(Event $event): void
    {
        $tables = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('tables')->getContent();
        $bases = [];
        foreach ($tables as $table) {
            $bases[$table->baseSlug()] = true;
        }
        $names = $event->getParam('registered_names');
        foreach (array_keys($bases) as $base) {
            $names[] = 'table:' . $base;
        }
        $event->setParam('registered_names', $names);
    }

    public function resetTableDataTypeOnRemove(Event $event): void
    {
        /** @var \Table\Entity\Table $table */
        $table = $event->getTarget();
        $services = $this->getServiceLocator();

        // Compute base slug from the entity being removed.
        $slug = $table->getSlug();
        $lang = $table->getLang();
        $base = ($lang && str_ends_with($slug, '-' . $lang))
            ? substr($slug, 0, -strlen($lang) - 1)
            : $slug;

        // Only reset when no other table in the group remains.
        $api = $services->get('Omeka\ApiManager');
        $remaining = 0;
        foreach ($api->search('tables')->getContent() as $t) {
            if ($t->id() !== $table->getId() && $t->baseSlug() === $base) {
                $remaining++;
            }
        }
        if ($remaining > 0) {
            return;
        }

        $name = 'table:' . $base;
        $conn = $services->get('Omeka\Connection');

        $stmt = $conn->prepare('UPDATE value SET type = "literal" WHERE type = ?');
        $stmt->bindValue(1, $name);
        $stmt->executeStatement();

        // resource_template_property.data_type is a JSON array of names.
        $sql = <<<'SQL'
            UPDATE resource_template_property
            SET data_type = JSON_REMOVE(data_type, JSON_UNQUOTE(JSON_SEARCH(data_type, 'one', ?)))
            WHERE JSON_SEARCH(data_type, 'one', ?) IS NOT NULL
            SQL;
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $name);
        $stmt->executeStatement();

        $conn->executeStatement(
            'UPDATE resource_template_property SET data_type = NULL WHERE data_type = ?',
            ['[]']
        );
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.77')) {
            $message = new \Omeka\Stdlib\Message(
                $translator->translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.77'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }
}
