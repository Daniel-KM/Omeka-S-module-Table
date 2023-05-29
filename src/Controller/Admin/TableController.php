<?php declare(strict_types=1);

namespace Table\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Form\ConfirmForm;
use Omeka\Stdlib\Message;
use Table\Api\Representation\TableRepresentation;
use Table\Form\TableForm;

/**
 * Adapted from Omeka controllers.
 */
class TableController extends AbstractActionController
{
    public function indexAction()
    {
        $params = $this->params()->fromRoute();
        $params['action'] = 'browse';
        return $this->forward()->dispatch(__CLASS__, $params);
    }

    public function browseAction()
    {
        $this->browse()->setDefaults('tables');
        $response = $this->api()->search('tables', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        // Set the return query for batch actions. Note that we remove the page
        // from the query because there's no assurance that the page will return
        // results once changes are made.
        $returnQuery = $this->params()->fromQuery();
        unset($returnQuery['page']);

        $formDeleteSelected = $this->getForm(ConfirmForm::class);
        $formDeleteSelected->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'batch-delete'], ['query' => $returnQuery], true));
        $formDeleteSelected->setButtonLabel('Confirm Delete'); // @translate
        $formDeleteSelected->setAttribute('id', 'confirm-delete-selected');

        $formDeleteAll = $this->getForm(ConfirmForm::class);
        $formDeleteAll->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'batch-delete-all'], ['query' => $returnQuery], true));
        $formDeleteAll->setButtonLabel('Confirm Delete'); // @translate
        $formDeleteAll->setAttribute('id', 'confirm-delete-all');
        $formDeleteAll->get('submit')->setAttribute('disabled', true);

        $tables = $response->getContent();

        return new ViewModel([
            'tables' => $tables,
            'resources' => $tables,
            'formDeleteSelected' => $formDeleteSelected,
            'formDeleteAll' => $formDeleteAll,
            'returnQuery' => $returnQuery,
        ]);
    }

    public function showAction()
    {
        $table = $this->getTableFromRoute();
        return new ViewModel([
            'table' => $table,
            'resource' => $table,
        ]);
    }

    public function showDetailsAction()
    {
        $table = $this->getTableFromRoute();

        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);

        $view = new ViewModel([
            'table' => $table,
            'resource' => $table,
            'linkTitle' => $linkTitle,
        ]);
        $view->setTerminal(true);
        return $view;
    }

    public function addAction()
    {
        /** @var \Table\Form\TableForm $form */
        $form = $this->getForm(TableForm::class);
        $form
            ->setAttribute('action', $this->url()->fromRoute(null, [], true))
            ->setAttribute('enctype', 'multipart/form-data')
            ->setAttribute('id', 'add-table');

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->create('tables', $data);
                if ($response) {
                    /** @var \Table\Api\Representation\TableRepresentation $table */
                    $table = $response->getContent();
                    $message = new Message(
                        'Table successfully created. %s', // @translate
                        sprintf(
                            '<a href="%s">%s</a>',
                            htmlspecialchars($this->url()->fromRoute(null, [], true)),
                            $this->translate('Add another table?') // @translate
                        ));
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toUrl($table->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        return new ViewModel([
            'form' => $form,
        ]);
    }

    public function editAction()
    {
        $table = $this->getTableFromRoute();

        $data = $table->jsonSerialize();

        /** @var \Table\Form\TableForm $form */
        $form = $this->getForm(TableForm::class);
        $form
            ->setAttribute('action', $this->url()->fromRoute(null, [], true))
            ->setAttribute('enctype', 'multipart/form-data')
            ->setAttribute('id', 'edit-table')
            ->setData($data)
        ;

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->update('tables', ['id' => $table->id()], $data);
                if ($response) {
                    $table = $response->getContent();
                    $this->messenger()->addSuccess('Table successfully updated.'); // @translate
                    return $this->redirect()->toUrl($table->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        return new ViewModel([
            'form' => $form,
            'table' => $table,
            'resource' => $table,
        ]);
    }

    public function deleteConfirmAction()
    {
        $table = $this->getTableFromRoute();

        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);

        $view = new ViewModel([
            'table' => $table,
            'resource' => $table,
            'linkTitle' => $linkTitle,
            'resourceLabel' => 'table', // @translate
            'partialPath' => 'table/admin/table/show-details',
        ]);
        $view
            ->setTemplate('common/delete-confirm-details')
            ->setTerminal(true);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $slug = $this->params('slug');
                $response = $this->api($form)->delete('tables', is_numeric($slug) ? ['id' => $slug] : ['slug' => $slug]);
                if ($response) {
                    $this->messenger()->addSuccess('Table successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(
            'admin/table',
            ['action' => 'browse'],
            true
        );
    }

    public function batchDeleteAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/table', ['action' => 'browse'], true);
        }

        $returnQuery = $this->params()->fromQuery();
        $resourceIds = $this->params()->fromPost('resource_ids', []);
        if (!$resourceIds) {
            $this->messenger()->addError('You must select at least one table to batch delete.'); // @translate
            return $this->redirect()->toRoute('admin/table', ['action' => 'browse'], ['query' => $returnQuery], true);
        }

        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if ($form->isValid()) {
            $response = $this->api($form)->batchDelete('tables', $resourceIds, [], ['continueOnError' => true]);
            if ($response) {
                $this->messenger()->addSuccess('Tables  successfully deleted'); // @translate
            }
        } else {
            $this->messenger()->addFormErrors($form);
        }
        return $this->redirect()->toRoute('admin/table', ['action' => 'browse'], ['query' => $returnQuery], true);
    }

    public function batchDeleteAllAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('admin/table', ['action' => 'browse'], true);
        }

        // Derive the query, removing limiting and sorting params.
        $query = json_decode($this->params()->fromPost('query', []), true);
        unset($query['submit'], $query['page'], $query['per_page'], $query['limit'],
            $query['offset'], $query['sort_by'], $query['sort_order']);

        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if ($form->isValid()) {
            $job = $this->jobDispatcher()->dispatch(\Omeka\Job\BatchDelete::class, [
                'resource' => 'tables',
                'query' => $query,
            ]);
            $message = new Message(
                'Deleting tables started in job %1$s#%2$d%3$s (%4$slogs%3$s)', // @translate
                sprintf('<a href="%1$s">', $this->url()->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()])),
                $job->getId(),
                '</a>',
                sprintf('<a href="%1$s">', class_exists('Log\Stdlib\PsrMessage') ? $this->url()->fromRoute('admin/default', ['controller' => 'log'], ['query' => ['job_id' => $job->getId()]]) :  $this->url()->fromRoute('admin/id', ['controller' => 'job', 'action' => 'log', 'id' => $job->getId()]))
            );
            $message->setEscapeHtml(false);
            $this->messenger()->addSuccess($message);
        } else {
            $this->messenger()->addFormErrors($form);
        }
        return $this->redirect()->toRoute('admin/table', ['action' => 'browse'], ['query' => $this->params()->fromQuery()], true);
    }

    /**
     * @throws \Omeka\Api\Exception\NotFoundException
     */
    protected function getTableFromRoute(): TableRepresentation
    {
        $slug = $this->params('slug');
        $response = $this->api()->read('tables', is_numeric($slug) ? ['id' => $slug] : ['slug' => $slug]);
        return $response->getContent();
    }
}
