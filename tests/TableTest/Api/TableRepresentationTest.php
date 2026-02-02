<?php declare(strict_types=1);

namespace TableTest\Api;

use CommonTest\AbstractHttpControllerTestCase;
use TableTest\TableTestTrait;

/**
 * Tests for the TableRepresentation methods (labelFromCode, codeFromLabel, etc.).
 */
class TableRepresentationTest extends AbstractHttpControllerTestCase
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

    /**
     * Test labelFromCode with exact match.
     */
    public function testLabelFromCodeExactMatch(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
            'DE' => 'Germany',
            'ES' => 'Spain',
        ]);

        $this->assertEquals('France', $table->labelFromCode('FR'));
        $this->assertEquals('Germany', $table->labelFromCode('DE'));
        $this->assertEquals('Spain', $table->labelFromCode('ES'));
    }

    /**
     * Test labelFromCode with non-existent code.
     */
    public function testLabelFromCodeNonExistent(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        $this->assertNull($table->labelFromCode('XX'));
    }

    /**
     * Test labelFromCode with case-insensitive match.
     */
    public function testLabelFromCodeCaseInsensitive(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        // Non-strict mode should find the code case-insensitively.
        $this->assertEquals('France', $table->labelFromCode('fr'));
        $this->assertEquals('France', $table->labelFromCode('Fr'));
    }

    /**
     * Test labelFromCode with strict mode.
     */
    public function testLabelFromCodeStrictMode(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        // Strict mode should not find lowercase version.
        $this->assertNull($table->labelFromCode('fr', true));
        $this->assertEquals('France', $table->labelFromCode('FR', true));
    }

    /**
     * Test labelFromCode with diacritics.
     */
    public function testLabelFromCodeWithDiacritics(): void
    {
        $table = $this->createAssociativeTable('Accented Codes', [
            'café' => 'Coffee',
            'thé' => 'Tea',
        ]);

        // Non-strict mode should match without diacritics.
        $this->assertEquals('Coffee', $table->labelFromCode('cafe'));
        $this->assertEquals('Tea', $table->labelFromCode('the'));
    }

    /**
     * Test codeFromLabel with exact match.
     */
    public function testCodeFromLabelExactMatch(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
            'DE' => 'Germany',
        ]);

        $this->assertEquals('FR', $table->codeFromLabel('France'));
        $this->assertEquals('DE', $table->codeFromLabel('Germany'));
    }

    /**
     * Test codeFromLabel with non-existent label.
     */
    public function testCodeFromLabelNonExistent(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        $this->assertNull($table->codeFromLabel('Italy'));
    }

    /**
     * Test codeFromLabel with case-insensitive match.
     */
    public function testCodeFromLabelCaseInsensitive(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        $this->assertEquals('FR', $table->codeFromLabel('france'));
        $this->assertEquals('FR', $table->codeFromLabel('FRANCE'));
    }

    /**
     * Test codeFromLabel with strict mode.
     */
    public function testCodeFromLabelStrictMode(): void
    {
        $table = $this->createAssociativeTable('Countries', [
            'FR' => 'France',
        ]);

        $this->assertNull($table->codeFromLabel('france', true));
        $this->assertEquals('FR', $table->codeFromLabel('France', true));
    }

    /**
     * Test codes() returns correct structure for associative table.
     */
    public function testCodesAssociativeStructure(): void
    {
        $table = $this->createAssociativeTable('Simple', [
            'a' => 'Alpha',
            'b' => 'Beta',
        ]);

        $codes = $table->codes();

        $this->assertIsArray($codes);
        $this->assertArrayHasKey('a', $codes);
        $this->assertArrayHasKey('b', $codes);
        $this->assertEquals('Alpha', $codes['a']);
        $this->assertEquals('Beta', $codes['b']);
    }

    /**
     * Test codes() returns correct structure for multilingual table.
     */
    public function testCodesMultilingualStructure(): void
    {
        $table = $this->createMultilingualTable('Multilingual', [
            ['code' => 'hello', 'label' => 'Hello'],
            ['code' => 'hello', 'label' => 'Bonjour'],
        ]);

        $codes = $table->codes();

        $this->assertIsArray($codes);
        $this->assertArrayHasKey('hello', $codes);
        $this->assertIsArray($codes['hello']);
        $this->assertCount(2, $codes['hello']);
        $this->assertContains('Hello', $codes['hello']);
        $this->assertContains('Bonjour', $codes['hello']);
    }

    /**
     * Test labelsFromCode for multilingual table.
     */
    public function testLabelsFromCodeMultilingual(): void
    {
        $table = $this->createMultilingualTable('Greetings', [
            ['code' => 'hello', 'label' => 'Hello'],
            ['code' => 'hello', 'label' => 'Bonjour'],
            ['code' => 'hello', 'label' => 'Hola'],
        ]);

        $labels = $table->labelsFromCode('hello');

        $this->assertCount(3, $labels);
        $this->assertContains('Hello', $labels);
        $this->assertContains('Bonjour', $labels);
        $this->assertContains('Hola', $labels);
    }

    /**
     * Test codesAssociative() converts multilingual to associative.
     */
    public function testCodesAssociativeConversion(): void
    {
        $table = $this->createMultilingualTable('Multilingual', [
            ['code' => 'a', 'label' => 'Alpha EN'],
            ['code' => 'a', 'label' => 'Alpha FR'],
            ['code' => 'b', 'label' => 'Beta'],
        ]);

        $codes = $table->codesAssociative();

        $this->assertIsArray($codes);
        $this->assertArrayHasKey('a', $codes);
        $this->assertArrayHasKey('b', $codes);
        // Should return the last label for each code.
        $this->assertIsString($codes['a']);
        $this->assertIsString($codes['b']);
    }

    /**
     * Test codesMultiple() converts associative to multiple format.
     */
    public function testCodesMultipleConversion(): void
    {
        $table = $this->createAssociativeTable('Simple', [
            'a' => 'Alpha',
            'b' => 'Beta',
        ]);

        $codes = $table->codesMultiple();

        $this->assertIsArray($codes);
        $this->assertArrayHasKey('a', $codes);
        $this->assertArrayHasKey('b', $codes);
        // Each code should have an array of labels.
        $this->assertIsArray($codes['a']);
        $this->assertIsArray($codes['b']);
        $this->assertEquals(['Alpha'], $codes['a']);
        $this->assertEquals(['Beta'], $codes['b']);
    }

    /**
     * Test codesData() returns raw code data.
     */
    public function testCodesDataReturnsRawData(): void
    {
        $table = $this->createMultilingualTable('Test', [
            ['code' => 'a', 'label' => 'Alpha'],
            ['code' => 'a', 'label' => 'Alpha FR'],
        ]);

        $data = $table->codesData();

        $this->assertCount(2, $data);
        foreach ($data as $item) {
            $this->assertArrayHasKey('code', $item);
            $this->assertArrayHasKey('label', $item);
        }
    }

    /**
     * Test codeCount() returns correct count.
     */
    public function testCodeCountReturnsCorrectCount(): void
    {
        $table = $this->createAssociativeTable('Count Test', [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
        ]);

        $this->assertEquals(3, $table->codeCount());
    }

    /**
     * Test codeCount() for multilingual table counts all entries.
     */
    public function testCodeCountMultilingual(): void
    {
        $table = $this->createMultilingualTable('Multilingual Count', [
            ['code' => 'a', 'label' => 'A EN'],
            ['code' => 'a', 'label' => 'A FR'],
            ['code' => 'b', 'label' => 'B'],
        ]);

        // Should count all code entries, not unique codes.
        $this->assertEquals(3, $table->codeCount());
    }

    /**
     * Test labelFromCode with integer code.
     */
    public function testLabelFromCodeWithIntegerCode(): void
    {
        $table = $this->createAssociativeTable('Numeric Codes', [
            '1' => 'One',
            '2' => 'Two',
            '100' => 'Hundred',
        ]);

        // Should work with integer input.
        $this->assertEquals('One', $table->labelFromCode(1));
        $this->assertEquals('Two', $table->labelFromCode(2));
        $this->assertEquals('Hundred', $table->labelFromCode(100));
    }

    /**
     * Test getJsonLd() returns expected structure.
     */
    public function testGetJsonLdStructure(): void
    {
        $table = $this->createAssociativeTable('JSON-LD Test', [
            'x' => 'X Value',
        ]);

        $jsonLd = $table->getJsonLd();

        $this->assertArrayHasKey('o:id', $jsonLd);
        $this->assertArrayHasKey('o:slug', $jsonLd);
        $this->assertArrayHasKey('o:is_associative', $jsonLd);
        $this->assertArrayHasKey('o:title', $jsonLd);
        $this->assertArrayHasKey('o:codes', $jsonLd);
        $this->assertArrayHasKey('o:created', $jsonLd);

        $this->assertEquals('JSON-LD Test', $jsonLd['o:title']);
        $this->assertTrue($jsonLd['o:is_associative']);
    }
}
