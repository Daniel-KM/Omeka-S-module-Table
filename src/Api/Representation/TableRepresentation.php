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
            'o:title' => $this->title(),
            'o:source' => $this->source(),
            'o:comment' => $this->comment(),
            'o:slug' => $this->slug(),
            'o:lang' => $this->lang(),
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

    public function source(): ?string
    {
        return $this->resource->getSource();
    }

    public function comment(): ?string
    {
        return $this->resource->getComment();
    }

    public function slug(): string
    {
        return $this->resource->getSlug();
    }

    public function lang(): ?string
    {
        return $this->resource->getLang();
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
        $this->cleanCodeCodes = [];
        /** @var \Table\Entity\Code $code */
        foreach ($this->resource->getCodes() as $code) {
            $codeCode = $code->getCode();
            $label = $code->getLabel();
            $this->codes[$codeCode] = $label;
            $this->cleanCodes[$codeCode] = $this->cleanString($codeCode);
            $this->cleanLabels[$codeCode] = $this->cleanString($label);
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
        $cleanCode = $this->cleanString($code);
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
        $cleanLabel = $this->cleanString($label);
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
     * @see \Omeka\Api\Adapter\SiteSlugTrait::slugify()
     */
    public function cleanString($string): string
    {
        $string = (string) $string;
        if (extension_loaded('intl')) {
            $transliterator = \Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;');
            $string = $transliterator->transliterate($string);
        } elseif (extension_loaded('iconv')) {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        }
        return mb_strtolower($string, 'UTF-8');
    }
}
