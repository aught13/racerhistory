<?php
// tests/TestCase/Service/ImageProcessorTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageProcessor;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Intervention\Image\ImageManager;
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

    public function testProcessDegradedModeWithNoVariantsConfigured(): void
    {
        $processor = new ImageProcessor(null);
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, 'notanimage');
        rewind($stream);
        $file = new UploadedFile($stream, 9, UPLOAD_ERR_OK, 'bad.png', 'image/png');

        $result = $processor->process($file, []);

        $this->assertIsArray($result);
        $this->assertSame([], $result['variants'] ?? null);
        $this->assertSame('png', $result['original']['ext'] ?? null);
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
            'filename' => 'test.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/test.jpg',
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-hash-' . time(),
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
        $reloaded = $images->get($image->id, contain: ['ImageTags']);
        $this->assertCount(2, (array)$reloaded->get('image_tags'));
    }

    public function testAttachTagsWithEmptyTagsIsNoOp(): void
    {
        $processor = new ImageProcessor(null);
        $processor->attachTags(1, []);

        $images = TableRegistry::getTableLocator()->get('Images');
        $reloaded = $images->get(1, contain: ['ImageTags']);
        $this->assertNotEmpty((array)$reloaded->get('image_tags'), 'Fixture should still have its original tags');
    }

    public function testAttachTagsUpdatesGenericExistingNameWhenBetterNameProvided(): void
    {
        $processor = new ImageProcessor(null);

        $tagsTable = TableRegistry::getTableLocator()->get('ImageTags');
        $existing = $tagsTable->newEntity([
            'name' => 'person-999',
            'slug' => 'person-999',
        ]);
        $tagsTable->saveOrFail($existing);

        // Provide a nicer display name.
        $processor->attachTags(1, [[
            'slug' => 'person-999',
            'name' => 'John Q Public',
        ]]);

        $reloaded = $tagsTable->find()->where(['slug' => 'person-999'])->first();
        $this->assertNotNull($reloaded);
        $this->assertSame('John Q Public', (string)$reloaded->name);
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
            'filename' => 'test2.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/test2.jpg',
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-hash-dup-' . time(),
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
     * Test getImagesByAllTags queries by tags.
     */
    public function testGetImagesByAllTags(): void
    {
        $processor = new ImageProcessor(null);
        $images = TableRegistry::getTableLocator()->get('Images');

        // Create test images
        $image1 = $images->newEntity([
            'filename' => 'test1.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/test1.jpg',
            'original_name' => 'test1.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-tag-1-' . time(),
            'status' => 'active',
        ]);
        $images->save($image1);

        $image2 = $images->newEntity([
            'filename' => 'test2.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/test2.jpg',
            'original_name' => 'test2.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-tag-2-' . time(),
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

    public function testGetImagesByAllTagsWithEmptyInputReturnsEmpty(): void
    {
        $processor = new ImageProcessor(null);

        $this->assertSame([], $processor->getImagesByAllTags([]));
        $this->assertSame([], $processor->getImagesByAllTags(['', '   ']));
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
            'filename' => 'person.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/person.jpg',
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-person-' . time(),
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
            'filename' => 'team.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/team.jpg',
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-teamseason-' . time(),
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
            'filename' => 'roster.jpg',
            'storage_subdir' => '',
            'storage_path' => 'test/roster.jpg',
            'original_name' => 'test.jpg',
            'mime' => 'image/jpeg',
            'ext' => 'jpg',
            'byte_size' => 10,
            'width' => 1,
            'height' => 1,
            'variants' => json_encode([]),
            'hash' => 'test-roster-' . time(),
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

    public function testEnsureTagsNormalizesSlugs(): void
    {
        $processor = new ImageProcessor(null);

        $tags = $processor->ensureTags(['My Tag']);
        $this->assertCount(1, $tags);
        $this->assertSame('My-Tag', (string)$tags[0]->slug);
        $this->assertSame('My-Tag', (string)$tags[0]->name);
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
        if (PHP_VERSION_ID < 80500) {
            $meth->setAccessible(true);
        }

        return $meth->invoke($proc, $mime);
    }

    public function testRotateBeforeCropOrder(): void
    {
        // Build a simple in-memory PNG (100x50)
        $im = imagecreatetruecolor(100, 50);
        $bg = imagecolorallocate($im, 200, 0, 0);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagepng($im);
        $raw = (string)ob_get_clean();

        $proc = new ImageProcessor();
        $result = $proc->manipulateExisting(
            $raw,
            'image/png',
            [],
            [
                'rotate' => 90,
                'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 20],
            ]
        );

        // If rotate happens first, then crop (0,0,10x20) on rotated 50x100 yields 10x20
        $this->assertSame(10, $result['original']['width']);
        $this->assertSame(20, $result['original']['height']);
    }

    public function testNegativeRotationAccepted(): void
    {
        // In-memory PNG (100x50)
        $im = imagecreatetruecolor(100, 50);
        $bg = imagecolorallocate($im, 0, 200, 0);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagepng($im);
        $raw = (string)ob_get_clean();

        $proc = new ImageProcessor();
        $result = $proc->manipulateExisting(
            $raw,
            'image/png',
            [],
            [ 'rotate' => -90 ]
        );

        // -90 should be treated like 270 CCW: width/height swap to 50x100
        $this->assertSame(50, $result['original']['width']);
        $this->assertSame(100, $result['original']['height']);
    }

    public function testManipulateExistingDegradedWhenManagerMissing(): void
    {
        $proc = new ImageProcessor();

        // Force manager=null regardless of environment so we cover the degraded branch.
        $ref = new \ReflectionClass($proc);
        $prop = $ref->getProperty('manager');
        if (PHP_VERSION_ID < 80500) {
            $prop->setAccessible(true);
        }
        $prop->setValue($proc, null);

        $result = $proc->manipulateExisting('raw', 'image/png', ['thumb' => ['fit' => [10, 10]]], []);
        $this->assertSame(0, $result['original']['width']);
        $this->assertSame(0, $result['original']['height']);
        $this->assertSame([], $result['variants']);
    }

    public function testManipulateExistingFallsBackWhenReadFails(): void
    {
        $proc = new ImageProcessor();
        // Invalid bytes should cause Intervention read() to throw and we should degrade gracefully.
        $result = $proc->manipulateExisting('not-an-image', 'image/png', ['thumb' => ['fit' => [10, 10]]], []);

        $this->assertSame(0, $result['original']['width']);
        $this->assertSame(0, $result['original']['height']);
        $this->assertSame([], $result['variants']);
    }

    public function testProcessWithRealManagerCoversManipulationsAndVariantFormatting(): void
    {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            $this->markTestSkipped('Requires gd or imagick for ImageManager-backed processing');
        }

        $im = imagecreatetruecolor(80, 40);
        $bg = imagecolorallocate($im, 10, 20, 30);
        imagefill($im, 0, 0, $bg);
        ob_start();
        imagepng($im);
        $raw = (string)ob_get_clean();

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $raw);
        rewind($stream);
        $file = new UploadedFile($stream, strlen($raw), UPLOAD_ERR_OK, 'in.png', 'image/png');

        // Use a real manager to ensure we hit the non-degraded path.
        $manager = extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
        $proc = new ImageProcessor($manager);

        $result = $proc->process(
            $file,
            [
                'thumb' => ['fit' => [10, 10], 'format' => 'webp'],
                'cropped' => ['crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10]],
                'medium' => ['maxWidth' => 20],
            ],
            [
                'rotate' => 90,
                'crop' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 20],
                'brightness' => 10,
                'contrast' => -10,
                'blur' => 5,
            ],
        );

        $this->assertArrayHasKey('original', $result);
        $this->assertArrayHasKey('variants', $result);
        $this->assertArrayHasKey('thumb', $result['variants']);
        $this->assertSame('webp', $result['variants']['thumb']['ext'] ?? null);
        $this->assertSame('image/webp', $result['variants']['thumb']['mime'] ?? null);

        $this->assertArrayHasKey('cropped', $result['variants']);
        $this->assertSame(10, (int)($result['variants']['cropped']['width'] ?? 0));
        $this->assertSame(10, (int)($result['variants']['cropped']['height'] ?? 0));
    }
}
