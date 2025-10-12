<?php
// tests/TestCase/Service/ImageProcessorTest.php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ImageProcessor;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

class ImageProcessorTest extends TestCase
{
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

    private function invokeInferExtension(ImageProcessor $proc, $mime): string
    {
        $ref = new \ReflectionClass($proc);
        $meth = $ref->getMethod('inferExtension');
        $meth->setAccessible(true);

        return $meth->invoke($proc, $mime);
    }
}
