<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageStorageService;
use Cake\Event\EventInterface;

/**
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 */
class ProfilesController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['view']);
        if ($this->request->getParam('action') === 'view') {
            $this->Authorization->skipAuthorization();
        }
    }

    /**
     * @param string $username
     */
    public function view(string $username)
    {
        $user = $this->fetchTable('Users')->find()
            ->where(['username' => $username, 'active' => true])
            ->firstOrFail();

        $this->set(compact('user'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function edit()
    {
        $identity = $this->Authentication->getIdentity();
        $user = $this->fetchTable('Users')->get($identity->getIdentifier());
        $this->Authorization->skipAuthorization(); // Users can edit their own profile by definition

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();

            // Normalize `social_links` to a valid JSON array string before save.
            if (array_key_exists('social_links', $data)) {
                $sl = $data['social_links'];
                if ($sl === '' || $sl === null) {
                    $data['social_links'] = null;
                } elseif (is_string($sl)) {
                    $decoded = json_decode((string)$sl, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $arr = array_values(array_filter(
                            array_map('trim', $decoded),
                            static fn($v) => trim((string)$v) !== '',
                        ));
                        $data['social_links'] = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    } else {
                        $parts = preg_split("/\r\n|\n|\r/", (string)$sl);
                        $arr = array_values(array_filter(array_map('trim', $parts), static fn($v) => $v !== ''));
                        $data['social_links'] = $arr === [] ? null :
                        json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                    }
                } elseif (is_array($sl)) {
                    $arr = array_values(array_filter(array_map('trim', $sl), static fn($v) => trim((string)$v) !== ''));
                    $data['social_links'] = $arr === [] ? null :
                    json_encode($arr, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
                } else {
                    $data['social_links'] = null;
                }
            }

            // Validate and sanitize specific fields
            $allowedFields = ['display_name', 'bio', 'website_url', 'social_links'];

            $user = $this->fetchTable('Users')->patchEntity($user, $data, [
                'fields' => $allowedFields,
            ]);

            // Handle Avatar Upload
            $file = $this->request->getData('avatar');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $imageStorage = new ImageStorageService();
                $ownerId = null;
                if ($identity !== null && method_exists($identity, 'getIdentifier')) {
                    $ownerId = (int)$identity->getIdentifier();
                }
                $uploadResult = $imageStorage->upload($file, [], [], $ownerId);

                if (isset($uploadResult['image']['id'])) {
                    $user->profile_image_id = $uploadResult['image']['id'];
                } else {
                    $this->Flash->error($imageStorage->getLastError() ?: 'Failed to process avatar image.');
                }
            }

            if ($this->fetchTable('Users')->save($user)) {
                $this->Flash->success('Profile updated successfully.');

                return $this->redirect(['action' => 'view', $user->username]);
            }
            $this->Flash->error('Could not update profile.');
        }

        $this->set(compact('user'));

        return null;
    }
}
