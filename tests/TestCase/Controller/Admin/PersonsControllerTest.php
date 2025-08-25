<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Support\AuthTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PersonsControllerTest extends TestCase
{
    use IntegrationTestTrait;
    use AuthTestTrait;

    protected array $fixtures = [
        'app.Persons',
        'app.Users',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    public function testIndexRequiresAuth(): void
    {
        $this->get('/admin/persons');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexAuthenticated(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons');
        $this->assertResponseOk();
    }

    public function testView(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/view/1');
        $this->assertResponseOk();
        $this->assertResponseContains('Sample biography for John Doe.');
    }

    public function testViewShowsPersonImageWhenSet(): void
    {
        $this->mockIdentity();
        // Create a person with a person_image id referencing fixture image id 1
        $persons = $this->getTableLocator()->get('Persons');
        $person = $persons->newEmptyEntity();
        $person = $persons->patchEntity($person, [
            'first' => 'Pic', 'last' => 'Owner', 'display' => 'Pic Owner', 'person_image' => 1,
        ]);
        $saved = $persons->save($person);
        if ($saved === false) {
            $errors = $person->getErrors();
            $this->fail('Failed to save person: ' . json_encode($errors));
        }
        $this->get('/admin/persons/view/' . $person->id);
        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<img', $body);
        $this->assertStringContainsString('/images/serve/1', $body);
    }

    public function testAddGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/add');
        $this->assertResponseOk();
    }

    public function testAddPostValid(): void
    {
        $this->mockIdentity();
        $data = [
            'first' => 'Alan',
            'last' => 'Turing',
            'display' => 'Alan Turing',
        ];
        $this->post('/admin/persons/add', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    public function testAddPostInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $data = [ 'first' => '', 'last' => '' ];
        $this->post('/admin/persons/add', $data);
        $this->assertResponseOk();
        $this->assertFlashMessage('The person could not be saved. Please, try again.');
    }

    public function testEditGet(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPost(): void
    {
        $this->mockIdentity();
        $data = [ 'display' => 'Updated Display' ];
        $this->post('/admin/persons/edit/1', $data);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('The person has been saved.');
    }

    public function testDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/delete/1');
        $this->assertRedirect('/admin/persons');
    }

    public function testBulkDeleteNoneSelected(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['']]);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('No persons selected for deletion.');
    }

    public function testBulkDeleteSome(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulkDelete', ['person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    public function testBulkDispatcherInvalid(): void
    {
        $this->mockIdentity();
        $this->enableRetainFlashMessages();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'nonsense']);
        $this->assertRedirect('/admin/persons');
        $this->assertFlashMessage('Invalid bulk action.');
    }

    public function testBulkDispatcherDelete(): void
    {
        $this->mockIdentity();
        $this->post('/admin/persons/bulk', ['bulk_action' => 'delete', 'person_ids' => ['1']]);
        $this->assertRedirect('/admin/persons');
    }

    public function testAjaxAddInvalidMethod(): void
    {
        $this->mockIdentity();
        $this->get('/admin/persons/ajaxAdd');
        $this->assertResponseOk();
        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($response['success']);
    }

    public function testAjaxAddValid(): void
    {
        $this->mockIdentity();
        $data = [ 'first' => 'Grace', 'last' => 'Hopper', 'display' => 'Grace Hopper' ];
        $this->post('/admin/persons/ajaxAdd', $data);
        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('Person has been added successfully.', $body['message']);
    }
}
