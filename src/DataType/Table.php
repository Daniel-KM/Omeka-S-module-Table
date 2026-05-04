<?php declare(strict_types=1);

namespace Table\DataType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType;
use Omeka\DataType\ValueAnnotatingInterface;
use Omeka\Entity\Value;
use Table\Api\Representation\TableRepresentation;

/**
 * Data type backed by a Table. Stores the code as @value with no @language.
 *
 * Display follows the current locale via slug convention "<base>-<lang>".
 */
class Table extends AbstractDataType implements ValueAnnotatingInterface
{
    protected TableRepresentation $table;

    public function __construct(TableRepresentation $table)
    {
        $this->table = $table;
    }

    public function getName()
    {
        return 'table:' . $this->table->id();
    }

    public function getLabel()
    {
        return $this->table->displayTitle();
    }

    public function getOptgroupLabel()
    {
        return 'Table'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headScript()->appendFile($view->assetUrl('js/resource-form.js', 'Table'));
    }

    public function form(PhpRenderer $view)
    {
        $codes = $this->table->codesAssociative();
        $select = (new \Laminas\Form\Element\Select('table'))
            ->setValueOptions($codes)
            ->setEmptyOption('');
        $select
            ->setAttribute('data-value-key', '@value')
            ->setAttribute('class', 'table-data-type to-require')
            ->setAttribute('data-placeholder', $view->translate('Select a value…'));
        return $view->formSelect($select);
    }

    public function isValid(array $valueObject)
    {
        $value = $valueObject['@value'] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return false;
        }
        return $this->table->labelFromCode($value, true) !== null;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue((string) $valueObject['@value']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return $view->escapeHtml($this->labelFor((string) $value->value(), $view));
    }

    public function toString(ValueRepresentation $value)
    {
        return $this->labelFor((string) $value->value(), null);
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => (string) $value->value()];
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return $this->labelFor((string) $value->value(), $view);
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $this->form($view);
    }

    protected function labelFor(string $code, ?PhpRenderer $view): string
    {
        $table = $this->table;
        if ($view !== null) {
            $locale = $this->resolveLocale($view);
            $table = $this->table->siblingForLang($locale);
        }
        return $table->labelFromCode($code) ?? $code;
    }

    protected function resolveLocale(PhpRenderer $view): ?string
    {
        try {
            return $view->plugin('lang')->__invoke();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
