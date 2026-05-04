<?php declare(strict_types=1);

namespace TableTest\Api;

use CommonTest\AbstractHttpControllerTestCase;
use TableTest\TableTestTrait;

/**
 * Tests for baseSlug() and siblingForLang() conventions.
 */
class TableSiblingTest extends AbstractHttpControllerTestCase
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

    public function testBaseSlugStripsMatchingLangSuffix(): void
    {
        $table = $this->createTable([
            'o:title' => 'ISO 639 French',
            'o:slug' => 'iso639-fra',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'fra', 'label' => 'français']],
        ]);
        $this->assertEquals('iso639', $table->baseSlug());
    }

    public function testBaseSlugKeepsSlugWhenNoLang(): void
    {
        $table = $this->createTable([
            'o:title' => 'Country codes',
            'o:slug' => 'country-codes',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'fr', 'label' => 'France']],
        ]);
        $this->assertEquals('country-codes', $table->baseSlug());
    }

    public function testBaseSlugKeepsSlugWhenSuffixDoesNotMatchLang(): void
    {
        $table = $this->createTable([
            'o:title' => 'Mismatched',
            'o:slug' => 'foo-bar',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $this->assertEquals('foo-bar', $table->baseSlug());
    }

    public function testSiblingForLangReturnsSelfWhenSameLang(): void
    {
        $table = $this->createTable([
            'o:title' => 'FR table',
            'o:slug' => 'sib-fra',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $this->assertSame($table->id(), $table->siblingForLang('fra')->id());
    }

    public function testSiblingForLangFindsSibling(): void
    {
        $fr = $this->createTable([
            'o:title' => 'FR sibling',
            'o:slug' => 'siblings-fra',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'fra', 'label' => 'français']],
        ]);
        $en = $this->createTable([
            'o:title' => 'EN sibling',
            'o:slug' => 'siblings-eng',
            'o:lang' => 'eng',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'fra', 'label' => 'French']],
        ]);
        $this->assertSame($en->id(), $fr->siblingForLang('eng')->id());
        $this->assertSame($fr->id(), $en->siblingForLang('fra')->id());
    }

    public function testSiblingForLangFallsBackToSelf(): void
    {
        $table = $this->createTable([
            'o:title' => 'Lonely',
            'o:slug' => 'lonely-fra',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $this->assertSame($table->id(), $table->siblingForLang('eng')->id());
    }
}
