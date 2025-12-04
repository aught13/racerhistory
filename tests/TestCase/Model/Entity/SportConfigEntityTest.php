<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\SportConfig;
use Cake\TestSuite\TestCase;

/**
 * SportConfig Entity Test Case
 */
class SportConfigEntityTest extends TestCase
{
    /**
     * Test decoded_value virtual property for JSON
     */
    public function testDecodedValueJson(): void
    {
        $config = new SportConfig(['config_value' => '["value1","value2"]']);
        $this->assertSame(['value1', 'value2'], $config->decoded_value);
    }

    /**
     * Test decoded_value for non-JSON
     */
    public function testDecodedValuePlainText(): void
    {
        $config = new SportConfig(['config_value' => 'plain text']);
        $this->assertSame('plain text', $config->decoded_value);
    }

    /**
     * Test decoded_value for empty
     */
    public function testDecodedValueEmpty(): void
    {
        $config = new SportConfig();
        $this->assertNull($config->decoded_value);
    }

    /**
     * Test isJsonValue returns true for JSON
     */
    public function testIsJsonValueTrue(): void
    {
        $config = new SportConfig(['config_value' => '["value"]']);
        $this->assertTrue($config->isJsonValue());
    }

    /**
     * Test isJsonValue returns false for non-JSON
     */
    public function testIsJsonValueFalse(): void
    {
        $config = new SportConfig(['config_value' => 'not json']);
        $this->assertFalse($config->isJsonValue());
    }

    /**
     * Test isJsonValue returns false for empty
     */
    public function testIsJsonValueEmpty(): void
    {
        $config = new SportConfig();
        $this->assertFalse($config->isJsonValue());
    }

    /**
     * Test getDisplayValue for JSON array
     */
    public function testGetDisplayValueJsonArray(): void
    {
        $config = new SportConfig(['config_value' => '["val1","val2"]']);
        $this->assertSame('val1, val2', $config->getDisplayValue());
    }

    /**
     * Test getDisplayValue for plain text
     */
    public function testGetDisplayValuePlainText(): void
    {
        $config = new SportConfig(['config_value' => 'test value']);
        $this->assertSame('test value', $config->getDisplayValue());
    }
}
