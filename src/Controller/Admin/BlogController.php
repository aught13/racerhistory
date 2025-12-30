<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

class BlogController extends AppController
{
    /**
     * Admin blog shortcut: redirect to BlogPosts index.
     *
     * @return \Cake\Http\Response
     */
    public function index(): Response
    {
        return $this->redirect(['controller' => 'BlogPosts', 'action' => 'index']);
    }
}
