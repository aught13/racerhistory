<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @property \App\Controller\Component\UserProfileComponent $UserProfile
 */
class ProfilesController extends AppController
{
    /**
     * Initialize the profile controller and its component.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('UserProfile');
    }

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
        $user = $this->UserProfile->findActiveByUsername($username);

        $this->set(compact('user'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function edit()
    {
        $identity = $this->Authentication->getIdentity();
        $user = $this->UserProfile->getById((int)$identity->getIdentifier());
        $this->Authorization->skipAuthorization(); // Users can edit their own profile by definition

        if ($this->request->is(['post', 'put', 'patch'])) {
            $data = $this->request->getData();

            $allowedFields = ['display_name', 'bio', 'website_url', 'social_links'];
            $user = $this->UserProfile->patchProfile($user, $data, $allowedFields);

            $file = $this->request->getData('avatar');
            if ($file instanceof UploadedFileInterface && $file->getError() === UPLOAD_ERR_OK) {
                $ownerId = $identity !== null && method_exists($identity, 'getIdentifier')
                    ? (int)$identity->getIdentifier()
                    : null;
                $uploadError = $this->UserProfile->uploadProfileImage($user, $file, $ownerId);
                if ($uploadError !== null) {
                    $this->Flash->error($uploadError);
                }
            }

            $savedUser = $this->UserProfile->saveProfile($user);
            if ($savedUser !== false) {
                $this->Flash->success('Profile updated successfully.');

                return $this->redirect(['action' => 'view', $savedUser->username]);
            }
            $this->Flash->error('Could not update profile.');
        }

        $this->set(compact('user'));

        return null;
    }
}
