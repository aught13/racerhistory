<?php
// tests/TestCase/Service/ImageProcessorTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageProcessor;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

class ImageProcessorTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<int,string>
     */
    public array $fixtures = [
        'app.Images',
        'app.ImageTags',
        'app.ImagesImageTags',
        'app.ImageUsages',
    ];

    public function testProcessDegradedMode(): void
    {
        $processor = new ImageProcessor(null); // Force degraded mode
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'notanimage');
        rewind($stream);
        $file = new UploadedFile($stream, 9, UPLOAD_ERR_OK, 'bad.png', 'image/png');
        $result = $processor->process($file, ['thumb' => ['fit' => [10, 10]]]);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('variants', $result);
    }

    public function testProcessThrowsException(): void
    {
        // Mock file to throw on getStream
        $mock = $this->getMockBuilder(UploadedFile::class)
            ->onlyMethods(['getStream'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->method('getStream')->will($this->throwException(new \RuntimeException('fail')));
        $processor = new ImageProcessor(null);
        $this->expectException(\RuntimeException::class);
        $processor->process($mock, []);
    }

    public function testInferExtension(): void
    {
        $processor = new ImageProcessor(null);
        $this->assertSame('png', $this->invokeInferExtension($processor, 'image/png'));
        $this->assertSame('gif', $this->invokeInferExtension($processor, 'image/gif'));
        $this->assertSame('webp', $this->invokeInferExtension($processor, 'image/webp'));
        $this->assertSame('jpg', $this->invokeInferExtension($processor, 'image/unknown'));
        $this->assertSame('jpg', $this->invokeInferExtension($processor, null));
    }

    /**
     * Test attaching tags to an image.
     */
    public function testAttachTags(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create a test image
        $image = $images->newEntity([
            'hash' => 'test-hash-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Attach tags
        $processor->attachTags((int)$image->id, ['person-123', 'roster']);

        // Verify tags were created and linked
        $imageTags = TableRegistry::getTableLocator()->get('ImageTags');
        $personTag = $imageTags->find()->where(['slug' => 'person-123'])->first();
        $rosterTag = $imageTags->find()->where(['slug' => 'roster'])->first();

        $this->assertNotNull($personTag);
        $this->assertNotNull($rosterTag);

        // Verify image has tags
        $reloaded = $images->get($image->id, ['contain' => ['ImageTags']]);
        $this->assertCount(2, $reloaded->image_tags);
    }

    /**
     * Test attachTags with duplicate tags (idempotent).
     */
    public function testAttachTagsIdempotent(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create a test image
        $image = $images->newEntity([
            'hash' => 'test-hash-dup-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Attach same tags twice
        $processor->attachTags((int)$image->id, ['roster']);
        $processor->attachTags((int)$image->id, ['roster']);

        // Should have only 1 tag
        $imageTags = TableRegistry::getTableLocator()->get('ImageTags');
        $rosterTags = $imageTags->find()->where(['slug' => 'roster'])->all();
        $this->assertCount(1, $rosterTags);
    }

    /**
     * Test recordUsage creates usage entry.
     */
    public function testRecordUsage(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create a test image
        $image = $images->newEntity([
            'hash' => 'test-usage-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Record usage
        $processor->recordUsage(
            (int)$image->id,
            'Persons',
            123,
            'profile-photo',
            'image'
        );

        // Verify usage was recorded
        $usages = TableRegistry::getTableLocator()->get('ImageUsages');
        $usage = $usages->find()->where([
            'image_id' => $image->id,
            'model' => 'Persons',
            'foreign_key' => 123,
        ])->first();

        $this->assertNotNull($usage);
        $this->assertSame('profile-photo', $usage->context);
        $this->assertSame('image', $usage->field);
    }

    /**
     * Test recordUsage is idempotent.
     */
    public function testRecordUsageIdempotent(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create a test image
        $image = $images->newEntity([
            'hash' => 'test-usage-idem-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Record usage twice
        $processor->recordUsage((int)$image->id, 'Persons', 123, 'profile-photo', 'image');
        $processor->recordUsage((int)$image->id, 'Persons', 123, 'profile-photo', 'image');

        // Should have only 1 usage record
        $usages = TableRegistry::getTableLocator()->get('ImageUsages');
        $allUsages = $usages->find()->where([
            'image_id' => $image->id,
            'model' => 'Persons',
            'foreign_key' => 123,
        ])->all();

        $this->assertCount(1, $allUsages);
    }

    /**
     * Test getImagesByAllTags queries by tags.
     */
    public function testGetImagesByAllTags(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create test images
        $image1 = $images->newEntity([
            'hash' => 'test-tag-1-' . time(),
            'original_name' => 'test1.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image1);

        $image2 = $images->newEntity([
            'hash' => 'test-tag-2-' . time(),
            'original_name' => 'test2.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image2);

        // Attach tags
        $processor->attachTags((int)$image1->id, ['person-1', 'roster']);
        $processor->attachTags((int)$image2->id, ['person-1', 'roster']);

        // Query by both tags
        $found = $processor->getImagesByAllTags(['person-1', 'roster']);

        $this->assertGreaterThanOrEqual(2, count($found));
        $ids = array_map(fn($img) => $img->id, $found);
        $this->assertContains($image1->id, $ids);
        $this->assertContains($image2->id, $ids);
    }

    /**
     * Test getImagesForPerson convenience method.
     */
    public function testGetImagesForPerson(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create test image
        $image = $images->newEntity([
            'hash' => 'test-person-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Attach person tag
        $processor->attachTags((int)$image->id, ['person-42']);

        // Query by person
        $found = $processor->getImagesForPerson(42);

        $this->assertGreaterThan(0, count($found));
        $ids = array_map(fn($img) => $img->id, $found);
        $this->assertContains($image->id, $ids);
    }

    /**
     * Test getImagesForTeamSeason convenience method.
     */
    public function testGetImagesForTeamSeason(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create test image
        $image = $images->newEntity([
            'hash' => 'test-teamseason-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Attach teamseason tag
        $processor->attachTags((int)$image->id, ['teamseason-99']);

        // Query by team season
        $found = $processor->getImagesForTeamSeason(99);

        $this->assertGreaterThan(0, count($found));
        $ids = array_map(fn($img) => $img->id, $found);
        $this->assertContains($image->id, $ids);
    }

    /**
     * Test getRosterImages convenience method.
     */
    public function testGetRosterImages(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create test image
        $image = $images->newEntity([
            'hash' => 'test-roster-' . time(),
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'status' => 'active',
        ]);
        $images->save($image);

        // Attach person + teamseason + roster tags
        $processor->attachTags((int)$image->id, ['person-50', 'teamseason-100', 'roster']);

        // Query by all three
        $found = $processor->getRosterImages(50, 100, 1);

        $this->assertGreaterThan(0, count($found));
        $ids = array_map(fn($img) => $img->id, $found);
        $this->assertContains($image->id, $ids);
    }

    /**
     * Test ensureTags creates missing tags.
     */
    public function testEnsureTags(): void
    {
        $processor = new ImageProcessor(null);

        $tags = $processor->ensureTags(['new-tag-1', 'new-tag-2']);

        $this->assertCount(2, $tags);
        $this->assertSame('new-tag-1', $tags[0]->name);
        $this->assertSame('new-tag-2', $tags[1]->name);
    }

    /**
     * Test ensureTags is idempotent.
     */
    public function testEnsureTagsIdempotent(): void
    {
        $processor = new ImageProcessor(null);

        $tags1 = $processor->ensureTags(['idem-tag']);
        $tags2 = $processor->ensureTags(['idem-tag']);

        $this->assertCount(1, $tags1);
        $this->assertCount(1, $tags2);
        $this->assertSame($tags1[0]->id, $tags2[0]->id);
    }

    private function invokeInferExtension(ImageProcessor $proc, $mime): string
    {
        $ref = new \ReflectionClass($proc);
        $meth = $ref->getMethod('inferExtension');
        $meth->setAccessible(true);

        return $meth->invoke($proc, $mime);
    }
}
