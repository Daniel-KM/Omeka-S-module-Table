<?php declare(strict_types=1);

namespace Table\Entity;

use Omeka\Entity\AbstractEntity;

/**
 * The index "idx_table_code" cannot be unique: a code may have multiple labels.
 * Anyway, if unique, there is a duplicate issue on update (mysql is not sql).
 *
 * For index: most of the time, we look the code from the label or the label
 * from the code in a specific table.
 *
 * @Entity
 * @Table(
 *     name="table_code",
 *      indexes={
 *         @Index(
 *             name="idx_table_code",
 *             columns={
 *                 "table_id",
 *                 "code"
 *             }
 *         ),
 *         @Index(
 *             name="idx_table_label",
 *             columns={
 *                 "table_id",
 *                 "label"
 *             },
 *             options={
 *                 "lengths": {null, 190}
 *             }
 *         )
 *     }
 * )
 */
class Code extends AbstractEntity
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
     * @ManyToOne(
     *     targetEntity="Table\Entity\Table",
     *     inversedBy="codes"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $table;

    /**
     * @var string
     *
     * @Column(
     *     length=190,
     *     nullable=false
     * )
     */
    protected $code;

    /**
     * @var string
     *
     * @Column(
     *     type="text",
     *     nullable=false
     * )
     */
    protected $label;

    public function getId()
    {
        return $this->id;
    }

    public function setTable(Table $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
