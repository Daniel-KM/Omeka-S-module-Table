<?php declare(strict_types=1);

namespace Table\Api\Adapter;

use Common\Stdlib\PsrMessage;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\SiteSlugTrait;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Table\Entity\Code;
use Table\Entity\Table;

class TableAdapter extends AbstractEntityAdapter
{
    use SiteSlugTrait;

    protected $sortFields = [
        'id' => 'id',
        'owner' => 'owner',
        'slug' => 'slug',
        'title' => 'title',
        'lang' => 'lang',
        'source' => 'source',
        'comment' => 'comment',
        'created' => 'created',
        'modified' => 'modified',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'owner' => 'owner',
        'slug' => 'slug',
        'title' => 'title',
        'lang' => 'lang',
        'source' => 'source',
        'comment' => 'comment',
        'created' => 'created',
        'modified' => 'modified',
    ];

    public function getResourceName()
    {
        return 'tables';
    }

    public function getRepresentationClass()
    {
        return \Table\Api\Representation\TableRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Table\Entity\Table::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        $expr = $qb->expr();

        if (isset($query['owner_id']) && strlen((string) $query['owner_id'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.owner',
                $this->createNamedParameter($qb, $query['owner_id'])
            ));
        }

        if (isset($query['slug']) && strlen((string) $query['slug'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.slug',
                $this->createNamedParameter($qb, $query['slug']))
            );
        }

        if (isset($query['title']) && strlen((string) $query['title'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.title',
                $this->createNamedParameter($qb, $query['title']))
            );
        }

        if (isset($query['lang']) && strlen((string) $query['lang'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.lang',
                $this->createNamedParameter($qb, $query['lang']))
            );
        }

        if (isset($query['source']) && strlen((string) $query['source'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.source',
                $this->createNamedParameter($qb, $query['source']))
            );
        }

        if (isset($query['comment']) && strlen((string) $query['comment'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.comment',
                $this->createNamedParameter($qb, $query['comment']))
            );
        }

        /** @see \Omeka\Api\Adapter\AbstractResourceEntityAdapter::buildQuery() */
        $dateSearches = [
            'created' => ['eq', 'created'],
            'created_before' => ['lt', 'created'],
            'created_after' => ['gt', 'created'],
            'created_before_on' => ['lte', 'created'],
            'created_after_on' => ['gte', 'created'],
            'modified' => ['eq', 'modified'],
            'modified_before' => ['lt', 'modified'],
            'modified_before_on' => ['lte', 'modified'],
            'modified_after' => ['gt', 'modified'],
            'modified_after_on' => ['gte', 'modified'],
        ];
        $dateGranularities = [
            DateTime::ISO8601,
            '!Y-m-d\TH:i:s',
            '!Y-m-d\TH:i',
            '!Y-m-d\TH',
            '!Y-m-d',
            '!Y-m',
            '!Y',
        ];
        foreach ($dateSearches as $dateSearchKey => $dateSearch) {
            if (isset($query[$dateSearchKey])) {
                foreach ($dateGranularities as $dateGranularity) {
                    $date = DateTime::createFromFormat($dateGranularity, $query[$dateSearchKey]);
                    if (false !== $date) {
                        break;
                    }
                }
                $qb->andWhere($expr->{$dateSearch[0]} (
                    sprintf('omeka_root.%s', $dateSearch[1]),
                    // If the date is invalid, pass null to ensure no results.
                    $this->createNamedParameter($qb, $date ?: null)
                ));
            }
        }
    }

    public function hydrate(
        Request $request,
        EntityInterface $entity,
        ErrorStore $errorStore
    ): void {
        /** @var \Table\Entity\Table $entity*/

        $data = $request->getContent();

        $this->hydrateOwner($request, $entity);

        if ($this->shouldHydrate($request, 'o:slug')) {
            $title = $entity->getTitle();
            $slug = mb_strtolower(trim($data['o:slug'] ?? ''));
            if ($slug === ''
                && $request->getOperation() === Request::CREATE
                && is_string($title)
                && $title !== ''
            ) {
                $slug = $this->getAutomaticSlug($title);
                if (is_numeric($slug)) {
                    $slug = 't-' . $slug;
                }
                $slug = mb_substr($slug, 0, 190);
            }
            $entity->setSlug($slug);
        }

        if ($this->shouldHydrate($request, 'o:title')) {
            $title = trim($data['o:title'] ?? '') ?: null;
            $entity->setTitle($title);
        }

        if ($this->shouldHydrate($request, 'o:lang')) {
            $lang = trim($data['o:lang'] ?? '') ?: null;
            $entity->setLang($lang);
        }

        if ($this->shouldHydrate($request, 'o:source')) {
            $source = trim($data['o:source'] ?? '') ?: null;
            $entity->setSource($source);
        }

        if ($this->shouldHydrate($request, 'o:comment')) {
            $comment = trim($data['o:comment'] ?? '') ?: null;
            $entity->setComment($comment);
        }

        if ($this->shouldHydrate($request, 'o:codes')) {
            $this->hydrateCodes($request, $entity, $errorStore);
        }

        $this->updateTimestamps($request, $entity);
    }

    /**
     * Adapted from SitePageAdapter.
     *
     * @see \Omeka\Api\Adapter\SitePageAdapter::hydrateAttachments().
     */
    protected function hydrateCodes(
        Request $request,
        Table $table,
        ErrorStore $errorStore
    ): void {
        $codes = $request->getValue('o:codes') ?: [];

        // Codes are already checked in validateRequest().
        $codes = $this->cleanListOfCodesAndLabels($codes);

        // Order codes by code early.
        usort($codes, fn ($a, $b) => strcasecmp((string) $a['code'], (string) $b['code']));

        $codes = $this->deduplicateTransliteratedCodes($codes);

        $tableCodes = $table->getCodes();
        $existingCodes = $tableCodes->toArray();
        $newCodes = [];

        foreach ($codes as $codeLabel) {
            $codeEntity = current($existingCodes);
            if ($codeEntity === false) {
                $codeEntity = new Code();
                $codeEntity->setTable($table);
                $newCodes[] = $codeEntity;
            } else {
                // Null out values as we re-use them.
                $existingCodes[key($existingCodes)] = null;
                next($existingCodes);
            }

            $codeEntity
                ->setCode($codeLabel['code'])
                ->setLabel($codeLabel['label']);
        }

        // Remove any codes that weren't reused.
        foreach ($existingCodes as $key => $existingCode) {
            if ($existingCode !== null) {
                $tableCodes->remove($key);
            }
        }

        // Add any new codes that had to be created.
        foreach ($newCodes as $newCode) {
            $tableCodes->add($newCode);
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore): void
    {
        $data = $request->getContent();

        // A resource template may not have duplicate properties.
        if (isset($data['o:title'])
            && (!is_string($data['o:title']) || $data['o:title'] === '')
        ) {
            $errorStore->addError('o:title', new PsrMessage(
                'A table must have a title.' // @translate
            ));
        }

        if (!empty($data['o:codes'])) {
            // Pre-normalize codes.
            $codes = $this->cleanListOfCodesAndLabels($data['o:codes']);
            $clean = $this->deduplicateTransliteratedCodes($codes);
            if (count($clean) !== count($codes)) {
                $errors = array_map('unserialize', array_diff(array_map('serialize', $codes), array_map('serialize', $clean)));
                $errorStore->addError('o:codes', new PsrMessage(
                    'Some codes are not unique once transliterated: {list}.', // @translate
                    ['list' => implode(', ', array_column('code', $errors))]
                ));
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore): void
    {
        $title = $entity->getTitle();
        if (!is_string($title) || $title === '') {
            $errorStore->addError('o:title', 'A table must have a title.'); // @translate
        }

        $slug = $entity->getSlug();
        if (!is_string($slug) || $slug === '') {
            $errorStore->addError('o:slug', 'The slug cannot be empty.'); // @translate
        } else {
            if (mb_strlen($slug) >= 190) {
                $errorStore->addError('o:slug', 'A slug cannot be longer than 190 characters.'); // @translate
            }
            if (preg_match('/[^a-zA-Z0-9_-]/u', $slug)) {
                $errorStore->addError('o:slug', 'A slug can only contain letters, numbers, underscores, and hyphens.'); // @translate
            } elseif (preg_match('/[^a-z0-9_-]/u', $slug)) {
                $errorStore->addError('o:slug', 'A slug should be lower case.'); // @translate
            } elseif (is_numeric($slug)) {
                $errorStore->addError('o:slug', 'A slug should not be a numeric string.'); // @translate
            } elseif (in_array($slug, ['index', 'search', 'view', 'browse', 'add', 'edit', 'show', 'show-details', 'delete', 'delete-confirm', 'batch-edit', 'batch-edit-all'])) {
                $errorStore->addError('o:slug', 'A slug cannot be a reserved keyword.'); // @translate
            }
            if (!$this->isUnique($entity, ['slug' => $slug])) {
                $errorStore->addError('o:slug', new PsrMessage(
                    'The slug "{slug}" is already taken.', // @translate
                    ['slug' => $slug]
                ));
            }
        }
    }

    /**
     * Check if all values are well-formed with code and label, and deduplicate.
     *
     * Normally, this is checked in form, but it may be skipped via api.
     */
    public function cleanListOfCodesAndLabels(array $codes): array
    {
        // Normalize code and label.
        foreach ($codes as $key => &$codeLabel) {
            $codeLabel = array_filter(array_map(fn ($v) => strlen($v ?? '') ? trim((string) $v) : '', $codeLabel), 'strlen');
            if (isset($codeLabel['code'] && isset($codeLabel['label']))) {
                $codeLabel = [
                    'code' => $codeLabel['code'],
                    'label' => $codeLabel['label'],
                ];
            } elseif (count($codeLabel) === 1) {
                $codeLabel = [
                    'code' => reset($codeLabel),
                    'label' => reset($codeLabel),
                ];
            } else {
                unset($codes[$key]);
            }
        }
        unset($codeLabel);

        // In all cases, remove full duplicates (code and label).
        return array_values(array_map('unserialize', array_unique(array_map('serialize', $codes))));
    }

    /**
     * Deduplicate transliterated codes from an array of arrays with key "code".
     *
     * @param $codes Codes should be already prepared via cleanListOfCodesAndLabels().
     */
    public function deduplicateTransliteratedCodes(array $codes): array
    {
        $result = [];
        foreach ($codes as $codeLabel) {
            $cleanCode = $this->stringToLowercaseAscii($codeLabel['code']);
            $result[$cleanCode] = [
                'code' => $codeLabel['code'],
                'label' => $codeLabel['label'],
            ],
        }
        return array_values($result);
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
