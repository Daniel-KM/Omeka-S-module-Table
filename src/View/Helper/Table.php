<?php declare(strict_types=1);

namespace Table\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Table\Api\Representation\TableRepresentation;

class Table extends AbstractHelper
{
    /**
     * Get the table representation.
     */
    public function __invoke($idOrSlug): ?TableRepresentation
    {
        return $this->getView()->api()
            ->searchOne('tables', is_numeric($idOrSlug) ? ['id' => $idOrSlug] : ['slug' => $idOrSlug])
            ->getContent();
    }
}
