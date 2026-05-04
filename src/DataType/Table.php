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
            foreach ($this->resolveLangCandidates($view) as $lang) {
                $sibling = $this->api->search('tables', ['slug' => $this->base . '-' . $lang, 'limit' => 1])->getContent();
                if ($sibling) {
                    $table = reset($sibling);
                    break;
                }
            }
        }
        $table ??= $this->canonical();
        return $table ? ($table->labelFromCode($code) ?? $code) : $code;
    }

    /**
     * Ordered list of language code candidates derived from the view locale.
     * Tries the two-letter primary code first, then the three-letter
     * terminologic code when known.
     */
    protected function resolveLangCandidates(PhpRenderer $view): array
    {
        try {
            $locale = (string) $view->plugin('lang')->__invoke();
        } catch (\Throwable $e) {
            $locale = '';
        }
        if ($locale === '') {
            return [];
        }
        $primary = strtolower(strtok($locale, '_-')) ?: '';
        $candidates = [];
        if ($primary !== '') {
            $candidates[] = $primary;
            if (isset(self::ISO_639_1_TO_2T[$primary])) {
                $candidates[] = self::ISO_639_1_TO_2T[$primary];
            }
            if (isset(self::ISO_639_1_TO_2B[$primary])) {
                $candidates[] = self::ISO_639_1_TO_2B[$primary];
            }
        }
        return $candidates;
    }

    /**
     * ISO 639-1 (alpha-2) to ISO 639-2 terminologic (alpha-3) mapping for the
     * most common UI locales. Used to find a sibling table whose lang is
     * declared with three letters.
     */
    private const ISO_639_1_TO_2T = [
        'ar' => 'ara', 'bg' => 'bul', 'bn' => 'ben', 'br' => 'bre',
        'ca' => 'cat', 'cs' => 'ces', 'cy' => 'cym', 'da' => 'dan',
        'de' => 'deu', 'el' => 'ell', 'en' => 'eng', 'es' => 'spa',
        'et' => 'est', 'eu' => 'eus', 'fa' => 'fas', 'fi' => 'fin',
        'fr' => 'fra', 'ga' => 'gle', 'gl' => 'glg', 'he' => 'heb',
        'hi' => 'hin', 'hr' => 'hrv', 'hu' => 'hun', 'id' => 'ind',
        'is' => 'isl', 'it' => 'ita', 'ja' => 'jpn', 'ka' => 'kat',
        'ko' => 'kor', 'la' => 'lat', 'lt' => 'lit', 'lv' => 'lav',
        'mk' => 'mkd', 'mn' => 'mon', 'nl' => 'nld', 'no' => 'nor',
        'oc' => 'oci', 'pl' => 'pol', 'pt' => 'por', 'ro' => 'ron',
        'ru' => 'rus', 'sk' => 'slk', 'sl' => 'slv', 'sq' => 'sqi',
        'sr' => 'srp', 'sv' => 'swe', 'th' => 'tha', 'tr' => 'tur',
        'uk' => 'ukr', 'vi' => 'vie', 'zh' => 'zho',
    ];

    /**
     * ISO 639-1 (alpha-2) to ISO 639-2 bibliographic (alpha-3) mapping for the
     * 22 languages that have both T and B forms. Used as a secondary lookup
     * when a sibling slug uses the historical bibliographic code.
     */
    private const ISO_639_1_TO_2B = [
        'bo' => 'tib', 'cs' => 'cze', 'cy' => 'wel', 'de' => 'ger',
        'el' => 'gre', 'eu' => 'baq', 'fa' => 'per', 'fr' => 'fre',
        'hy' => 'arm', 'is' => 'ice', 'ka' => 'geo', 'mi' => 'mao',
        'mk' => 'mac', 'ms' => 'may', 'my' => 'bur', 'nl' => 'dut',
        'ro' => 'rum', 'sk' => 'slo', 'sq' => 'alb', 'zh' => 'chi',
    ];

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
