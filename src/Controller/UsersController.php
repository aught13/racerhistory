<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;

class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'resetPassword']);
    }

    public function login()
    {
        $this->request->allowMethod(['get', 'post']);

        if ($this->request->is('post')) {
            $result = $this->Authentication->getResult();
            if ($result->isValid()) {
                $user = $this->Authentication->getIdentity();
                if ($user->status !== 'active') {
                    $this->Authentication->logout();
                    $this->Flash->error('Your account is not active. Please contact an administrator.');
                    return;
                }
                $redirect = $this->request->getQuery('redirect', [
                    'controller' => 'Pages',
                    'action' => 'home'
                ]);
                return $this->redirect($redirect);
            }
            $this->Flash->error('Invalid username or password');
        }
    }

    public function logout()
    {
        $this->Authentication->logout();
        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    public function register()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['role'] = 'view';
            $data['status'] = 'pending';

            // Check for duplicate username or email
            $duplicate = $this->Users->find()
                ->where([
                    'OR' => [
                        'username' => $data['username'],
                        'email' => $data['email']
                    ]
                ])
                ->first();
            if ($duplicate) {
                if ($duplicate->username === $data['username']) {
                    $this->Flash->error('Username is already taken. Please choose another.');
                }
                if ($duplicate->email === $data['email']) {
                    $this->Flash->error('Email address is already registered. Please use another.');
                }
            } else {
                $user = $this->Users->patchEntity($user, $data);
                if ($this->Users->save($user)) {
                    $this->Flash->success('Registration successful. You can now log in.');
                    return $this->redirect(['action' => 'login']);
                }
                $this->Flash->error('Unable to register user.');
            }
        }
        $this->set(compact('user'));
    }

    public function resetPassword()
    {
        if ($this->request->is('post')) {
            $email = $this->request->getData('email');
            // Here you would generate a token and send an email
            $this->Flash->success('If your email exists, a reset link will be sent.');
        }
    }
}