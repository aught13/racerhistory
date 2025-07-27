<?php
/**
 * AdminController.php
 *
 * This file is part of the RacerHistory project.
 *
 * @package App\Controller
 */
namespace App\Controller;

use Cake\Controller\Controller;

class AdminController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $result = $this->Authentication->getResult();
        if (!$result || !$result->isValid()) {
            // Redirect to login if not authenticated
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }

    public function index()
    {
        $this->set('title', 'Admin Dashboard');
    }
}
