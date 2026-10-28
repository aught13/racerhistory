<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use App\Service\ImageStorageService;
use App\Service\UserProfileService;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @covers \App\Service\UserProfileService
 */
class UserProfileServiceTest extends TestCase
{
    protected array $fixtures = ['app.Users'];

    private UsersTable $usersTable;

    private ImageStorageService&MockObject $imageStorage;

    private UserProfileService $service;

    /**
     * Initialize the profile service with fixture-backed users and a mock store.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->usersTable = $this->getTableLocator()->get('Users');
        $this->imageStorage = $this->createMock(ImageStorageService::class);
        $this->service = new UserProfileService($this->usersTable, $this->imageStorage);
    }

    /**
     * Verify blank social-link input normalizes to null.
     *
     * @return void
     */
    public function testNormalizeSocialLinksReturnsNullForBlankValues(): void
    {
        $this->assertNull($this->service->normalizeSocialLinks(null));
        $this->assertNull($this->service->normalizeSocialLinks(''));
        $this->assertNull($this->service->normalizeSocialLinks(" \n "));
    }

    /**
     * Verify profile lookup uses the active finder and user identifier.
     *
     * @return void
     */
    public function testProfileLookupsUseActiveFinderAndUserIdentifier(): void
    {
        $activeUser = $this->usersTable->find('active')->first();
        $this->assertInstanceOf(User::class, $activeUser);
        $lookedUp = $this->service->findActiveByUsername((string)$activeUser->username);
        $this->assertSame($activeUser->id, $lookedUp->id);
        $this->assertSame(1, $this->service->getById(1)->id);

        $this->expectException(RecordNotFoundException::class);
        $this->service->findActiveByUsername('missing-profile');
    }

    /**
     * Verify legacy JSON social-link input is normalized.
     *
     * @return void
     */
    public function testNormalizeSocialLinksAcceptsLegacyJsonText(): void
    {
        $this->assertSame(
            '["https://example.com/a","https://example.com/b"]',
            $this->service->normalizeSocialLinks('[" https://example.com/a ","","https://example.com/b"]'),
        );
    }

    /**
     * Verify newline-delimited social-link input is normalized.
     *
     * @return void
     */
    public function testNormalizeSocialLinksAcceptsOneUrlPerLine(): void
    {
        $this->assertSame(
            '["https://example.com/a","https://example.com/b"]',
            $this->service->normalizeSocialLinks(" https://example.com/a \r\n\r\nhttps://example.com/b "),
        );
    }

    /**
     * Verify array input is normalized and unsupported values are dropped.
     *
     * @return void
     */
    public function testNormalizeSocialLinksAcceptsArraysAndDropsUnsupportedValues(): void
    {
        $this->assertSame(
            '["https://example.com/a","42"]',
            $this->service->normalizeSocialLinks([
                ' https://example.com/a ',
                '',
                ['not', 'a', 'link'],
                42,
            ]),
        );
    }

    /**
     * Verify unsupported scalar social-link input is rejected.
     *
     * @return void
     */
    public function testNormalizeSocialLinksRejectsUnsupportedScalars(): void
    {
        $this->assertNull($this->service->normalizeSocialLinks(42));
        $this->assertNull($this->service->normalizeSocialLinks(false));
    }

    /**
     * Verify profile patches normalize links and restrict editable fields.
     *
     * @return void
     */
    public function testPatchProfileNormalizesSocialLinksAndLimitsFields(): void
    {
        $user = $this->usersTable->get(1);
        $originalEmail = $user->email;

        $patched = $this->service->patchProfile($user, [
            'display_name' => 'Profile update',
            'email' => 'changed@example.com',
            'social_links' => "https://example.com/profile\nhttps://example.com/team",
        ], ['display_name', 'social_links']);

        $this->assertSame('Profile update', $patched->display_name);
        $this->assertSame($originalEmail, $patched->email);
        $this->assertSame(
            '["https://example.com/profile","https://example.com/team"]',
            $patched->social_links,
        );
    }

    /**
     * Verify a valid profile save returns the saved user.
     *
     * @return void
     */
    public function testSaveProfileReturnsSavedUser(): void
    {
        $user = $this->usersTable->get(1);
        $user->display_name = 'Saved profile';

        $saved = $this->service->saveProfile($user);

        $this->assertInstanceOf(User::class, $saved);
        $this->assertSame('Saved profile', $this->usersTable->get(1)->display_name);
    }

    /**
     * Verify an invalid profile save returns false.
     *
     * @return void
     */
    public function testSaveProfileReturnsFalseForInvalidUser(): void
    {
        $user = $this->usersTable->get(1);
        $user = $this->usersTable->patchEntity($user, ['username' => 'x'], ['fields' => ['username']]);

        $this->assertFalse($this->service->saveProfile($user));
    }

    /**
     * Verify a successful profile image upload attaches the image ID.
     *
     * @return void
     */
    public function testUploadProfileImageAttachesImageId(): void
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $this->imageStorage
            ->expects($this->once())
            ->method('upload')
            ->with($file, [], [], 7)
            ->willReturn(['image' => ['id' => 42]]);

        $user = $this->usersTable->get(1);

        $this->assertNull($this->service->uploadProfileImage($user, $file, 7));
        $this->assertSame(42, $user->profile_image_id);
    }

    /**
     * Verify a failed image upload returns the storage service error.
     *
     * @return void
     */
    public function testUploadProfileImageReturnsStorageErrorWhenNoImageWasSaved(): void
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $this->imageStorage
            ->expects($this->once())
            ->method('upload')
            ->with($file, [], [], null)
            ->willReturn(['success' => false]);
        $this->imageStorage
            ->expects($this->once())
            ->method('getLastError')
            ->willReturn('Upload failed');

        $this->assertSame(
            'Upload failed',
            $this->service->uploadProfileImage($this->usersTable->get(1), $file, null),
        );
    }

    /**
     * Verify a failed image upload uses the fallback error message.
     *
     * @return void
     */
    public function testUploadProfileImageUsesFallbackErrorMessage(): void
    {
        $file = $this->createMock(UploadedFileInterface::class);
        $this->imageStorage->method('upload')->willReturn([]);
        $this->imageStorage->method('getLastError')->willReturn(null);

        $this->assertSame(
            'Failed to process avatar image.',
            $this->service->uploadProfileImage($this->usersTable->get(1), $file, null),
        );
    }
}
