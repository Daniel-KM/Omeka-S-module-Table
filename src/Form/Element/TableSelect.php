<?php declare(strict_types=1);

namespace Table\Form\Element;

use Laminas\Form\Element\Select;
use Omeka\Api\Manager as ApiManager;

/**
 * A select that uses table codes.
 *
 * The table is set via option "table", that can be the id or the slug.
 */
class TableSelect extends Select
{
    use TraitOptionalElement;

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->api = $apiManager;
    }

    public function getValueOptions(): array
    {
        $valueOptions = [];

        $tableId = $this->getOption('table');
        if ($tableId) {
            $tables = $this->api->search('tables', is_numeric($tableId) ? ['id' => $tableId] : ['slug' => $tableId])->getContent();
            if (count($tables)) {
                /** @var \Table\Api\Representation\TableRepresentation $table */
                $table = reset($tables);
                $valueOptions = $table->codes();
            }
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }

        return $valueOptions;
    }
}
