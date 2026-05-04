<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ImagesImageTagsTable;
use Cake\TestSuite\TestCase;

class ImagesImageTagsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
    ];

    protected ImagesImageTagsTable $ImagesImageTags;

    /**
     * Sets up the test case.
     */
    public function setUp(): void
    {
        parent::setUp();

        $config = $this->getTableLocator()->exists('ImagesImageTags')
            ? []
            : ['className' => ImagesImageTagsTable::class];

        $this->ImagesImageTags = $this->getTableLocator()->get('ImagesImageTags', $config);
    }

    /**
     * Tears down the test case.
     */
    public function tearDown(): void
    {
        unset($this->ImagesImageTags);

        parent::tearDown();
    }

    /**
     * Tests validation default accepts valid ids.
     */
    public function testValidationDefaultAcceptsValidIds(): void
    {
        $entity = $this->ImagesImageTags->newEntity([
            'image_id' => 1,
            'image_tag_id' => 1,
        ]);

        $this->assertEmpty($entity->getErrors());
    }

    /**
     * Tests validation default rejects missing fields.
     */
    public function testValidationDefaultRejectsMissingFields(): void
    {
        $entity = $this->ImagesImageTags->newEntity([
            'image_id' => '',
            'image_tag_id' => '',
        ]);
        $errors = $entity->getErrors();

        $this->assertArrayHasKey('image_id', $errors);
        $this->assertArrayHasKey('image_tag_id', $errors);
    }

    /**
     * Tests validation default rejects non integers.
     */
    public function testValidationDefaultRejectsNonIntegers(): void
    {
        $entity = $this->ImagesImageTags->newEntity([
            'image_id' => 'not-an-int',
            'image_tag_id' => 'also-not-an-int',
        ]);

        $errors = $entity->getErrors();
        $this->assertArrayHasKey('image_id', $errors);
        $this->assertArrayHasKey('image_tag_id', $errors);
    }

    /**
     * Tests associations configured.
     */
    public function testAssociationsConfigured(): void
    {
        $imagesAssoc = $this->ImagesImageTags->getAssociation('Images');
        $this->assertSame('manyToOne', $imagesAssoc->type());
        $this->assertSame('image_id', $imagesAssoc->getForeignKey());

        $tagsAssoc = $this->ImagesImageTags->getAssociation('ImageTags');
        $this->assertSame('manyToOne', $tagsAssoc->type());
        $this->assertSame('image_tag_id', $tagsAssoc->getForeignKey());
    }
}
