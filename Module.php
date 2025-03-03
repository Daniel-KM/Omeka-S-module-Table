<?php declare(strict_types=1);

namespace Table;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
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
                ],
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
                ],
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
        ;
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translate = $services->get('ControllerPluginManager')->get('translate');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.66')) {
            $message = new \Omeka\Stdlib\Message(
                $translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.66'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }
    }
}
