<?php declare(strict_types=1);

namespace Table\Api\Representation;

use DateTime;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\UserRepresentation;

class TableRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var \Table\Entity\Table
     */
    protected $resource;

    /**
     * Associative array of codes and labels.
     *
     * @var array
     */
    protected $codes;

    /**
     * Associative array of codes in lower case without diacritics and labels.
     *
     * @var array
     */
    protected $cleanCodes;

    /**
     * Associative array of codes and labels in lower case without diacritics.
     *
     * @var array
     */
    protected $cleanLabels;

    public function getControllerName()
    {
        return 'table';
    }

    public function getJsonLdType()
    {
        return 'o:Table';
    }

    public function getJsonLd()
    {
        $owner = $this->owner();

        $created = [
            '@value' => $this->getDateTime($this->created()),
            '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        ];

        $modified = $this->modified();
        if ($modified) {
            $modified = [
                '@value' => $this->getDateTime($modified),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        return [
            'o:id' => $this->id(),
            'o:owner' => $owner ? $owner->getReference() : null,
            'o:slug' => $this->slug(),
            'o:title' => $this->title(),
            'o:lang' => $this->lang(),
            'o:source' => $this->source(),
            'o:comment' => $this->comment(),
            'o:created' => $created,
            'o:modified' => $modified,
            'o:codes' => $this->codes(),
        ];
    }

    public function owner(): ?UserRepresentation
    {
        $owner = $this->resource->getOwner();
        return $owner
            ? $this->getAdapter('users')->getRepresentation($owner)
            : null;
    }

    public function slug(): string
    {
        return $this->resource->getSlug();
    }

    public function title(): string
    {
        return $this->resource->getTitle();
    }

    /**
     * For simplicity with generic code.
     */
    public function displayTitle(): string
    {
        return $this->title();
    }

    public function lang(): ?string
    {
        return $this->resource->getLang();
    }

    public function source(): ?string
    {
        return $this->resource->getSource();
    }

    public function comment(): ?string
    {
        return $this->resource->getComment();
    }

    public function created(): DateTime
    {
        return $this->resource->getCreated();
    }

    public function modified(): ?DateTime
    {
        return $this->resource->getModified();
    }

    public function codes(): array
    {
        if (is_array($this->codes)) {
            return $this->codes;
        }

        $this->codes = [];
        $this->cleanCodes = [];
        $this->cleanLabels = [];

        /** @var \Table\Entity\Code $code */
        foreach ($this->resource->getCodes() as $code) {
            $codeCode = $code->getCode();
            $label = $code->getLabel();
            $this->codes[$codeCode] = $label;
            $this->cleanCodes[$codeCode] = $this->stringToLowercaseAscii($codeCode);
            $this->cleanLabels[$codeCode] = $this->stringToLowercaseAscii($label);
        }
        return $this->codes;
    }

    public function codeCount(): int
    {
        return $this->resource->getCodes()->count();
    }

    /**
     * @param string|int $code Int is managed in order to fix array issues.
     */
    public function labelFromCode($code, bool $strict = false): ?string
    {
        if ($this->codes === null) {
            $this->codes();
        }
        $code = (string) $code;
        if (isset($this->codes[$code])) {
            return $this->codes[$code];
        }
        if ($strict) {
            return null;
        }
        $cleanCode = $this->stringToLowercaseAscii($code);
        return $this->cleanCodes[$cleanCode] ?? null;
    }

    public function codeFromLabel($label, bool $strict = false): ?string
    {
        if ($this->codes === null) {
            $this->codes();
        }
        $label = (string) $label;
        $code = array_search($label, $this->codes);
        if ($code !== false) {
            return (string) $code;
        }
        if ($strict) {
            return null;
        }
        $cleanLabel = $this->stringToLowercaseAscii($label);
        $code = array_search($cleanLabel, $this->cleanLabels);
        return $code === false
            ? null
            : $this->cleanLabels[$code];
    }

    /**
     *@todo The route doesn't exclude add/edit etc., so an action is required.
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Representation\AbstractResourceRepresentation::url()
     */
    public function url($action = null, $canonical = false)
    {
        return parent::url($action ?? 'show', $canonical);
    }

    /**
     * Use slug instead of the id.
     *
     * {@inheritDoc}
     * @see \Omeka\Api\Representation\AbstractResourceRepresentation::adminUrl()
     */
    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/table-id',
            [
                'controller' => $this->getControllerName(),
                'action' => $action ?? 'show',
                'slug' => $this->slug(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    /**
     * Remove diacritics from a string and set it lowercase.
     *
     * Mysql is case insensitive and skips diacritics so php should do the same.
     *
     * Don't use iconv() neither mb_convert_encoding(), that are system
     * dependant and that provides bad conversion by default.
     */
    public function stringToLowercaseAscii($string): string
    {
        static $isLogged;

        // Don't use iconv, that transliterates badly to ascii, depending on
        // system config. The same for mb_convert_encoding(),
        $string = (string) $string;
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
            $string = $transliterator->transliterate($string);
        } elseif (!$isLogged) {
            $this->getServiceLocator()->get('Omeka\Logger')->warn(
                'The php extension "intl" is not installed, so transliteration to ascii is not managed.' // @translate
            );
            $isLogged = true;
        }
        return mb_strtolower($string, 'UTF-8');
    }
}
