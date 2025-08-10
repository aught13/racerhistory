<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        // Only load authentication for admin controllers and user-related actions
        // This prevents authentication from being enforced on all public pages
        $isAdminController = str_contains($this->getRequest()->getParam('prefix') ?? '', 'Admin');
        $isUsersController = $this->getRequest()->getParam('controller') === 'Users';

        if (!($this instanceof ErrorController) && ($isAdminController || $isUsersController)) {
            $this->loadComponent('FormProtection');
            $this->loadComponent('Authentication.Authentication');
        }
    }

    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Allow unauthenticated access to login actions for Users controller
        // Admin controllers handle authentication in their own beforeFilter
        if (method_exists($this, 'Authentication') && $this->getRequest()->getParam('controller') === 'Users') {
            $this->Authentication->allowUnauthenticated(['login', 'register']);
        }
    }
}
