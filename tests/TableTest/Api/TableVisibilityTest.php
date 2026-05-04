<?php declare(strict_types=1);

namespace TableTest\Api;

use CommonTest\AbstractHttpControllerTestCase;
use TableTest\TableTestTrait;

/**
 * Tests for table visibility (is_public).
 */
class TableVisibilityTest extends AbstractHttpControllerTestCase
{
    use TableTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->loginAdmin();
    }

    public function tearDown(): void
    {
        $this->cleanupTables();
        parent::tearDown();
    }

    public function testTableIsPublicByDefault(): void
    {
        $table = $this->createAssociativeTable('Public default', ['a' => 'A']);
        $this->assertTrue($table->isPublic());
    }

    public function testTableCanBePrivate(): void
    {
        $table = $this->createTable([
            'o:title' => 'Private table',
            'o:is_associative' => true,
            'o:is_public' => false,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $this->assertFalse($table->isPublic());
    }

    public function testJsonLdExposesIsPublic(): void
    {
        $table = $this->createAssociativeTable('JSON public', ['a' => 'A']);
        $this->assertArrayHasKey('o:is_public', $table->getJsonLd());
        $this->assertTrue($table->getJsonLd()['o:is_public']);
    }

    public function testFilterByIsPublic(): void
    {
        $this->createTable([
            'o:title' => 'Public one',
            'o:slug' => 'visibility-public-one',
            'o:is_associative' => true,
            'o:is_public' => true,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $this->createTable([
            'o:title' => 'Private one',
            'o:slug' => 'visibility-private-one',
            'o:is_associative' => true,
            'o:is_public' => false,
            'o:codes' => [['code' => 'b', 'label' => 'B']],
        ]);

        $public = $this->api()->search('tables', [
            'is_public' => '1',
            'slug' => 'visibility-public-one',
        ])->getContent();
        $this->assertCount(1, $public);

        $private = $this->api()->search('tables', [
            'is_public' => '0',
            'slug' => 'visibility-private-one',
        ])->getContent();
        $this->assertCount(1, $private);
    }
}
