<?php declare(strict_types=1);

namespace Table\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\SiteSlugTrait;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;
use Table\Entity\Code;
use Table\Entity\Table;

class TableAdapter extends AbstractEntityAdapter
{
    use SiteSlugTrait;

    protected $sortFields = [
        'id' => 'id',
        'owner' => 'owner',
        'title' => 'title',
        'slug' => 'slug',
        'lang' => 'lang',
        'created' => 'created',
        'modified' => 'modified',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'owner' => 'owner',
        'title' => 'title',
        'slug' => 'slug',
        'lang' => 'lang',
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

        if (isset($query['title']) && strlen((string) $query['title'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.title',
                $this->createNamedParameter($qb, $query['title']))
            );
        }

        if (isset($query['slug']) && strlen((string) $query['slug'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.slug',
                $this->createNamedParameter($qb, $query['slug']))
            );
        }

        if (isset($query['lang']) && strlen((string) $query['lang'])) {
            $qb->andWhere($expr->eq(
                'omeka_root.lang',
                $this->createNamedParameter($qb, $query['lang']))
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
        $data = $request->getContent();

        $this->hydrateOwner($request, $entity);

        if ($this->shouldHydrate($request, 'o:title')) {
            $title = trim($data['o:title'] ?? '') ?: null;
            $entity->setTitle($title);
        }

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

        if ($this->shouldHydrate($request, 'o:lang')) {
            $entity->setLang($data['o:lang'] ?? null);
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
        $codeData = $request->getValue('o:codes') ?: [];

        // First, clean input.
        $clean = [];
        foreach ($codeData as $code => $label) {
            $code = trim((string) $code);
            if (!strlen($code)) {
                unset($codeData[$code]);
                continue;
            }
            $label = trim((string) $label);
            if (!strlen($label)) {
                $label = $code;
            }
            $clean[$code] = $label;
        }
        $codeData = $clean;

        // Order codes by code early.
        // Code may be a number, so avoid a strict type issue with direct uksort().
        $cmp = function ($a, $b) {
            return strcasecmp((string) $a, (string) $b);
        };
        uksort($codeData, $cmp);

        $codes = $table->getCodes();
        $existingCodes = $codes->toArray();
        $newCodes = [];

        foreach ($codeData as $code => $label) {
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
                ->setCode($code)
                ->setLabel($label);
        }

        // Remove any codes that weren't reused.
        foreach ($existingCodes as $key => $existingCode) {
            if ($existingCode !== null) {
                $codes->remove($key);
            }
        }

        // Add any new codes that had to be created.
        foreach ($newCodes as $newCode) {
            $codes->add($newCode);
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore): void
    {
        $data = $request->getContent();

        // A resource template may not have duplicate properties.
        if (isset($data['o:title'])
            && (!is_string($data['o:title']) || $data['o:title'] === '')
        ) {
            $errorStore->addError('o:title', new Message(
                'A table must have a title.' // @translate
            ));
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
                $errorStore->addError('o:slug', new Message(
                    'The slug "%s" is already taken.', // @translate
                    $slug
                ));
            }
        }
    }
}
