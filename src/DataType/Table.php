<?php declare(strict_types=1);

namespace Table\DataType;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType;
use Omeka\DataType\ValueAnnotatingInterface;
use Omeka\Entity\Value;
use Table\Api\Representation\TableRepresentation;

/**
 * Data type backed by a group of tables sharing a base slug.
 *
 * Stores the code as @value with no @language. Renders the label from the
 * sibling table matching the current locale (slug "<base>-<lang>"), with
 * fallback on the canonical table (slug equal to base).
 */
class Table extends AbstractDataType implements ValueAnnotatingInterface
{
    protected string $base;

    protected ApiManager $api;

    protected ?TableRepresentation $canonical = null;

    public function __construct(string $base, ApiManager $api)
    {
        $this->base = $base;
        $this->api = $api;
    }

    public function getName()
    {
        return 'table:' . $this->base;
    }

    public function getLabel()
    {
        $canonical = $this->canonical();
        return $canonical ? $canonical->displayTitle() : $this->base;
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
        $canonical = $this->canonical();
        $codes = $canonical ? $canonical->codesAssociative() : [];
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
        $canonical = $this->canonical();
        return $canonical && $canonical->labelFromCode($value, true) !== null;
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
        $table = null;
        if ($view !== null) {
            $locale = $this->resolveLocale($view);
            if ($locale) {
                $sibling = $this->api->search('tables', ['slug' => $this->base . '-' . $locale, 'limit' => 1])->getContent();
                $table = $sibling ? reset($sibling) : null;
            }
        }
        $table ??= $this->canonical();
        return $table ? ($table->labelFromCode($code) ?? $code) : $code;
    }

    protected function resolveLocale(PhpRenderer $view): ?string
    {
        try {
            return $view->plugin('lang')->__invoke();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function canonical(): ?TableRepresentation
    {
        if ($this->canonical !== null) {
            return $this->canonical;
        }
        // Prefer the table whose slug equals the base.
        $exact = $this->api->search('tables', ['slug' => $this->base, 'limit' => 1])->getContent();
        if ($exact) {
            return $this->canonical = reset($exact);
        }
        // Fallback: first table whose baseSlug matches.
        foreach ($this->api->search('tables')->getContent() as $t) {
            if ($t->baseSlug() === $this->base) {
                return $this->canonical = $t;
            }
        }
        return null;
    }
}
