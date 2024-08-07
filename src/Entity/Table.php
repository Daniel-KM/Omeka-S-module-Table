<?php declare(strict_types=1);

namespace Table\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * Uses a specific name, because "`table`" is a reserved sql keyword that is not fully managed in Omeka (ticks may be skipped).
 *
 * @Entity
 * @Table(
 *     name="tables"
 * )
 */
class Table extends AbstractEntity
{
    /**
     * @var int
     *
     * @Id
     * @Column(
     *     type="integer"
     * )
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var \Omeka\Entity\User|null
     *
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $owner;

    /**
     * @var string
     *
     * @Column(
     *     unique=true,
     *     length=190
     * )
     */
    protected $slug;

    /**
     * @var bool
     *
     * @Column(
     *     type="boolean",
     *     nullable=false,
     *     options={
     *         "default"=0
     *     }
     * )
     */
    protected $isAssociative = false;

    /**
     * @var string
     *
     * @Column(
     *     length=190
     * )
     */
    protected $title;

    /**
     * @var string|null
     *
     * @Column(
     *     nullable=true,
     *     length=190
     * )
     */
    protected $lang;

    /**
     * @var string
     *
     * @Column(
     *     type="text",
     *     length=65535,
     *     nullable=true
     * )
     */
    protected $source;

    /**
     * @var string
     *
     * @Column(
     *     type="text",
     *     length=65535,
     *     nullable=true
     * )
     */
    protected $comment;

    /**
     * @var \DateTime
     *
     * @Column(
     *     type="datetime"
     * )
     */
    protected $created;

    /**
     * @var \DateTime|null
     *
     * @Column(
     *      type="datetime",
     *      nullable=true
     * )
     */
    protected $modified;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     *
     * @OneToMany(
     *     targetEntity="Table\Entity\Code",
     *     mappedBy="table",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"},
     *     indexBy="property_id"
     * )
     * @OrderBy(
     *     {
     *         "code"="ASC",
     *         "label"="ASC",
     *         "lang"="ASC"
     *     }
     * )
     */
    protected $codes;

    public function __construct()
    {
        $this->codes = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setIsAssociative(?bool $isAssociative): self
    {
        $this->isAssociative = (bool) $isAssociative;
        return $this;
    }

    public function getIsAssociative(): bool
    {
        return $this->isAssociative;
    }

    /**
     * Alias of getIsAssociative().
     */
    public function isAssociative(): bool
    {
        return $this->isAssociative;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setLang(?string $lang): self
    {
        $this->lang = $lang ?: null;
        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source ?: null;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment ?: null;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(?DateTime $modified): self
    {
        $this->modified = $modified;
        return $this;
    }

    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|\Doctrine\ORM\PersistentCollection
     */
    public function getCodes()
    {
        return $this->codes;
    }
}
