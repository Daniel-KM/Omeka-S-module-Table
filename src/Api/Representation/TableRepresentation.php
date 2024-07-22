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
     * Associative array of codes in lower case without diacritics and codes.
     *
     * @var array
     */
    protected $cleanCodesToCodes;

    /**
     * Associative array of labels in lower case without diacritics and codes.
     *
     * @var array
     */
    protected $cleanLabelsToCodes;

    /**
     * Associative array of labels and codes.
     *
     * @var array
     */
    protected $labelsToCodes;

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
            'o:is_associative' => $this->isAssociative(),
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

    public function isAssociative(): bool
    {
        return $this->resource->isAssociative();
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

    /**
     * Get the codes of the tables.
     *
     * The codes are a flat array of codes/labels pairs when the table is
     * associative, else it is a list of codes associated to an array of labels.
     */
    public function codes(): array
    {
        if (is_array($this->codes)) {
            return $this->codes;
        }

        $this->codes = [];
        $this->cleanCodesToCodes = [];
        $this->cleanLabelsToCodes = [];
        $this->labelsToCodes = [];

        // Prepare all tables and cleaned codes one time.

        /** @var \Table\Entity\Code $code */
        if ($this->isAssociative()) {
            foreach ($this->resource->getCodes() as $code) {
                $codeCode = $code->getCode();
                $codeLabel = $code->getLabel();
                $this->codes[$codeCode] = $codeLabel;
                // In case of duplicates, the last code is kept, like in database.
                // TODO Find a way to convert only the last one (but useless, because it should be the first one in most of the cases).
                $cleanCode = $this->adapter->stringToLowercaseAscii($codeCode)
                $cleanLabel = $this->adapter->stringToLowercaseAscii($codeLabel);
                $this->cleanCodesToCodes[$cleanCode] = $codeCode;
                $this->cleanLabelsToCodes[$cleanLabel] = $codeCode;
                $this->labelsToCodes[$label] = $codeCode;
            }
        } else {
            foreach ($this->resource->getCodes() as $code) {
                $codeCode = $code->getCode();
                $label = $code->getLabel();
                $this->codes[$codeCode][] = $label;
                // In case of duplicates, the last code is kept, like in database.
                $cleanCode = $this->adapter->stringToLowercaseAscii($codeCode)
                $cleanLabel = $this->adapter->stringToLowercaseAscii($codeLabel);
                $this->cleanCodesToCodes[$cleanCode] = $codeCode;
                $this->cleanLabelsToCodes[$cleanLabel] = $codeCode;
                $this->labelsToCodes[$label] = $codeCode;
            }
        }

        return $this->codes;
    }

    /**
     * Get the codes of the tables as a list of pairs code/label.
     *
     * When the original table is not associative, the label is the last one.
     */
    public function codesAssociative(): array
    {
        if ($this->isAssociative()) {
            return $this->codes();
        }

        if ($this->codes === null) {
            $this->codes();
        }

        return array_column($this->codes, 'label', 'code');
    }

    /**
     * Get the codes of the tables as a list of code with associated labels.
     */
    public function codesMultiple(): array
    {
        if (!$this->isAssociative()) {
            return $this->codes();
        }

        if ($this->codes === null) {
            $this->codes();
        }

        $result = [];
        foreach ($this->codes as $code => $label) {
            $result[$code] = [$label];
        }

        return $result;
    }

    /**
     * Get the codes of the tables as a list of code with data as array.
     */
    public function codesData(): array
    {
        $result = [];
        foreach ($this->resource->getCodes() as $codeEntity) {
            $code = $codeEntity->getCode();
            $result[] = [
                'code' => $code,
                'label' => $codeEntity->getLabel(),
            ];
        }
        return $result;
    }

    /**
     * Get the total codes of the table.
     */
    public function codeCount(): int
    {
        return $this->resource->getCodes()->count();
    }

    /**
     * Get a label from a code.
     *
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
            return $this->isAssociative()
                ? $this->codes[$code]
                : end($this->codes[$code]);
        }

        if ($strict) {
            return null;
        }

        $cleanCode = $this->adapter->stringToLowercaseAscii($code);
        if (!isset($this->cleanCodesToCodes[$cleanCode])) {
            return null;
        }

        $realCode = $this->cleanCodesToCodes[$cleanCode];
        return $this->isAssociative()
            ? $this->codes[$realCode]
            : end($this->codes[$realCode]);
    }

    /**
     * Get all labels for a code.
     *
     * @param string|int $code Int is managed in order to fix array issues.
     * @param bool $strict Don't check transliterated lower case code.
     * @return string|null In case of a transliterated lower case duplicate, the
     *   last one is returned, like database.
     */
    public function labelsFromCode($code, bool $strict = false): array
    {
        if ($this->isAssociative()) {
            return (array) $this->labelFromCode($code, $strict);
        }

        if ($this->codes === null) {
            $this->codes();
        }

        $code = (string) $code;
        if (isset($this->codes[$code])) {
            return $this->codes[$code];
        }

        if ($strict) {
            return [];
        }

        $cleanCode = $this->adapter->stringToLowercaseAscii($code);
        return isset($this->cleanCodesToCodes[$cleanCode])
            ? $this->codes[$this->cleanCodesToCodes[$cleanCode]]
            : [];
    }

    /**
     * Get a code from a label.
     *
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

        if (isset($this->labelsToCodes[$label])) {
            return $this->labelsToCodes[$label];
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
