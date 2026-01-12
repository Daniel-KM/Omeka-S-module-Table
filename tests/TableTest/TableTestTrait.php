<?php declare(strict_types=1);

namespace TableTest;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Manager as ApiManager;
use Table\Api\Representation\TableRepresentation;

/**
 * Shared test helpers for Table module tests.
 */
trait TableTestTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var array IDs of tables created during tests (for cleanup).
     */
    protected array $createdTables = [];

    /**
     * @var bool Whether admin is logged in.
     */
    protected bool $isLoggedIn = false;

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ServiceLocatorInterface
    {
        if (isset($this->application) && $this->application !== null) {
            return $this->application->getServiceManager();
        }
        return $this->getApplication()->getServiceManager();
    }

    /**
     * Reset the cached service locator.
     */
    protected function resetServiceLocator(): void
    {
        $this->services = null;
    }

    /**
     * Get the API manager.
     */
    protected function api(): ApiManager
    {
        if ($this->isLoggedIn) {
            $this->ensureLoggedIn();
        }
        return $this->getServiceLocator()->get('Omeka\ApiManager');
    }

    /**
     * Get the entity manager.
     */
    public function getEntityManager(): \Doctrine\ORM\EntityManager
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
    }

    /**
     * Login as admin user.
     */
    protected function loginAdmin(): void
    {
        $this->isLoggedIn = true;
        $this->ensureLoggedIn();
    }

    /**
     * Ensure admin is logged in on the current application instance.
     */
    protected function ensureLoggedIn(): void
    {
        $services = $this->getServiceLocator();
        $auth = $services->get('Omeka\AuthenticationService');

        if ($auth->hasIdentity()) {
            return;
        }

        $adapter = $auth->getAdapter();
        $adapter->setIdentity('admin@example.com');
        $adapter->setCredential('root');
        $auth->authenticate();
    }

    /**
     * Logout current user.
     */
    protected function logout(): void
    {
        $this->isLoggedIn = false;
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
    }

    /**
     * Create a test table.
     *
     * @param array $data Table data.
     * @return TableRepresentation
     */
    protected function createTable(array $data): TableRepresentation
    {
        $response = $this->api()->create('tables', $data);
        $table = $response->getContent();
        $this->createdTables[] = $table->id();
        return $table;
    }

    /**
     * Create a simple associative table for testing.
     *
     * @param string $title Table title.
     * @param array $codes Array of code => label pairs.
     * @return TableRepresentation
     */
    protected function createAssociativeTable(string $title, array $codes): TableRepresentation
    {
        $codesData = [];
        foreach ($codes as $code => $label) {
            $codesData[] = ['code' => (string) $code, 'label' => $label];
        }

        return $this->createTable([
            'o:title' => $title,
            'o:is_associative' => true,
            'o:codes' => $codesData,
        ]);
    }

    /**
     * Create a multilingual table for testing.
     *
     * @param string $title Table title.
     * @param array $codes Array of ['code' => x, 'label' => y, 'lang' => z].
     * @return TableRepresentation
     */
    protected function createMultilingualTable(string $title, array $codes): TableRepresentation
    {
        return $this->createTable([
            'o:title' => $title,
            'o:is_associative' => false,
            'o:codes' => $codes,
        ]);
    }

    /**
     * Clean up created tables after test.
     */
    protected function cleanupTables(): void
    {
        foreach ($this->createdTables as $tableId) {
            try {
                $this->api()->delete('tables', $tableId);
            } catch (\Exception $e) {
                // Ignore errors during cleanup.
            }
        }
        $this->createdTables = [];
    }
}
