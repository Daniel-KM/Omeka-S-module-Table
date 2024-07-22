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
    protected $cleanCodesToLabels;

    /**
     * Associative array of labels in lower case without diacritics and codes.
     *
     * @var array
     */
    protected $cleanLabelsToCodes;

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
        $this->cleanCodesToLabels = [];
        $this->cleanLabelsToCodes = [];

        // Prepare all tables and cleaned codes one time.

        /** @var \Table\Entity\Code $code */
        foreach ($this->resource->getCodes() as $code) {
            $codeCode = $code->getCode();
            $codeLabel = $code->getLabel();
            $this->codes[$codeCode] = $codeLabel;
            // In case of duplicates, the last code is kept, like in database.
            $cleanCode = $this->adapter->stringToLowercaseAscii($codeCode)
            $cleanLabel = $this->adapter->stringToLowercaseAscii($codeLabel);
            $this->cleanCodesToLabels[$cleanCode] = $codeLabel;
            $this->cleanLabelsToCodes[$cleanLabel] = $codeCode;
        }
        return $this->codes;
    }

    public function codeCount(): int
    {
        return $this->resource->getCodes()->count();
    }

    /**
     * @param string|int $code Int is managed in order to fix array issues.
     * @param bool $strict Don't check transliterated lower case code.
     * @return string|null In case of a transliterated lower case duplicate, the
     *   last one is returned, like database.
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

        $cleanCode = $this->adapter->stringToLowercaseAscii($code);
        return $this->cleanCodesToLabels[$cleanCode] ?? null;
    }

    /**
     * @param string|int $code Int is managed in order to fix array issues.
     * @param bool $strict Don't check transliterated lower case code.
     * @return string|null In case of a transliterated lower case duplicate, the
     *   last one is returned, like database.
     */
    public function codeFromLabel($label, bool $strict = false): ?string
    {
        if ($this->codes === null) {
            $this->codes();
        }

        $label = (string) $label;
        $codeCodes = array_keys($this->codes, $label);
        if ($codeCodes) {
            return (string) end($codeCodes);
        }

        if ($strict) {
            return null;
        }

        $cleanLabel = $this->adapter->stringToLowercaseAscii($label);
        return $this->cleanLabelsToCodes[$cleanLabel] ?? null;
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
     */
    public function stringToLowercaseAscii($string): string
    {
        return $this->adapter->stringToLowercaseAscii($string);
    }
}
