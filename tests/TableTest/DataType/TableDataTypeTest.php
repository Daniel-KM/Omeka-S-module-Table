<?php declare(strict_types=1);

namespace TableTest\DataType;

use CommonTest\AbstractHttpControllerTestCase;
use TableTest\TableTestTrait;

/**
 * Tests for the table:<base> data type registration and validation.
 */
class TableDataTypeTest extends AbstractHttpControllerTestCase
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

    public function testDataTypeIsRegisteredWhenTableExists(): void
    {
        $this->createTable([
            'o:title' => 'Type registered',
            'o:slug' => 'type-registered',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'A']],
        ]);
        $manager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $this->assertContains('table:type-registered', $manager->getRegisteredNames());
    }

    public function testDataTypeUsesBaseForSiblings(): void
    {
        $this->createTable([
            'o:title' => 'EN side',
            'o:slug' => 'shared-base-eng',
            'o:lang' => 'eng',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'Alpha']],
        ]);
        $this->createTable([
            'o:title' => 'FR side',
            'o:slug' => 'shared-base-fra',
            'o:lang' => 'fra',
            'o:is_associative' => true,
            'o:codes' => [['code' => 'a', 'label' => 'Alpha (fr)']],
        ]);
        $manager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $names = $manager->getRegisteredNames();
        $this->assertContains('table:shared-base', $names);
        $this->assertNotContains('table:shared-base-eng', $names);
        $this->assertNotContains('table:shared-base-fra', $names);
    }

    public function testIsValidAcceptsKnownCode(): void
    {
        $this->createTable([
            'o:title' => 'Valid codes',
            'o:slug' => 'valid-codes',
            'o:is_associative' => true,
            'o:codes' => [
                ['code' => 'fra', 'label' => 'French'],
                ['code' => 'eng', 'label' => 'English'],
            ],
        ]);
        $manager = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $dataType = $manager->get('table:valid-codes');
        $this->assertTrue($dataType->isValid(['@value' => 'fra']));
        $this->assertFalse($dataType->isValid(['@value' => 'xxx']));
        $this->assertFalse($dataType->isValid(['@value' => '']));
    }
}
