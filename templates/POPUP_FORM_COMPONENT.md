# Popup Form Component Documentation

This document describes how to use the reusable popup form component for adding related entities via AJAX in the RacerHistory admin interface.

## Overview

The popup form component (`templates/element/Admin/popup_form.php`) provides a modal dialog with AJAX form submission functionality. It's designed to be reusable across different admin forms where you need to add related entities without leaving the current page.

## Features

- **Modal popup interface** using Bootstrap 5.3.2 modals
- **AJAX form submission** with JSON responses
- **Automatic select option updates** when new entities are created
- **Error handling and validation display**
- **Toast notifications** for success/error feedback
- **CSRF protection** with automatic token handling
- **Customizable field configurations**
- **Success callback hooks** for custom logic

## Basic Usage

### 1. Include the Element in Your Template

```php
<?php
echo $this->element('Admin/popup_form', [
    'popupId' => 'add-entity-modal',
    'title' => 'Add New Entity',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Entities', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'entity-id-select',
    'successCallback' => 'handleEntityAdded',
    'fields' => [
        [
            'name' => 'entity_name',
            'type' => 'text',
            'label' => 'Entity Name',
            'placeholder' => 'Enter entity name',
            'required' => true,
            'attributes' => [
                'maxlength' => 100
            ]
        ]
    ]
]);
?>
```

### 2. Add Trigger Button

```php
<div class="input-group">
    <?= $this->Form->control('entity_id', [
        'type' => 'select',
        'options' => $entities,
        'empty' => 'Select an Entity',
        'class' => 'form-control',
        'id' => 'entity-id-select',
        'label' => false
    ]) ?>
    <button type="button" class="btn btn-outline-secondary"
            data-bs-toggle="modal"
            data-bs-target="#add-entity-modal"
            title="Add New Entity">
        <i class="bi bi-plus-circle"></i>
    </button>
</div>
```

### 3. Create AJAX Controller Method

```php
/**
 * AJAX endpoint for adding entities from popup forms.
 *
 * @return \Cake\Http\Response
 */
public function ajaxAdd(): Response
{
    $entity = $this->Entities->newEmptyEntity();

    if ($this->request->is('post')) {
        $entity = $this->Entities->patchEntity($entity, $this->request->getData());

        if ($this->Entities->save($entity)) {
            $response = [
                'success' => true,
                'message' => 'Entity has been added successfully.',
                'newOption' => [
                    'value' => $entity->id,
                    'text' => $entity->display_field // Adjust based on your entity
                ]
            ];
        } else {
            $errors = [];
            foreach ($entity->getErrors() as $field => $fieldErrors) {
                foreach ($fieldErrors as $error) {
                    $errors[] = ucfirst($field) . ': ' . $error;
                }
            }

            $response = [
                'success' => false,
                'errors' => $errors ?: ['Unable to save entity. Please try again.']
            ];
        }
    } else {
        $response = [
            'success' => false,
            'errors' => ['Invalid request method.']
        ];
    }

    return $this->response
        ->withType('application/json')
        ->withStringBody(json_encode($response));
}
```

## Configuration Options

### Required Parameters

- **`popupId`**: Unique identifier for the modal (string)
- **`title`**: Modal title displayed in header (string)
- **`formUrl`**: URL for AJAX form submission (string)
- **`fields`**: Array of field configurations (array)

### Optional Parameters

- **`targetSelectId`**: ID of select element to update on success (string)
- **`successCallback`**: JavaScript function name to call on success (string)

### Field Configuration

Each field in the `fields` array supports:

```php
[
    'name' => 'field_name',           // Required: field name attribute
    'type' => 'text|textarea|select', // Required: input type
    'label' => 'Display Label',       // Required: field label
    'placeholder' => 'Placeholder',   // Optional: placeholder text
    'required' => true|false,         // Optional: required validation
    'options' => [],                  // Optional: options for select fields
    'attributes' => []                // Optional: additional HTML attributes
]
```

### Supported Field Types

- **`text`**: Standard text input
- **`textarea`**: Multi-line text area
- **`select`**: Dropdown selection with options

## Advanced Examples

### Multi-Field Form

```php
echo $this->element('Admin/popup_form', [
    'popupId' => 'add-team-modal',
    'title' => 'Add New Team',
    'formUrl' => $this->Url->build(['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'ajaxAdd']),
    'targetSelectId' => 'team-select',
    'fields' => [
        [
            'name' => 'team_name',
            'type' => 'text',
            'label' => 'Team Name',
            'placeholder' => 'Enter team name',
            'required' => true,
            'attributes' => ['maxlength' => 162]
        ],
        [
            'name' => 'sport_key',
            'type' => 'select',
            'label' => 'Sport',
            'required' => true,
            'options' => $sportOptions // Provided by SiteOptionsService::getAvailableSports()
        ],
        [
            'name' => 'description',
            'type' => 'textarea',
            'label' => 'Description',
            'placeholder' => 'Enter team description',
            'attributes' => ['rows' => 3]
        ]
    ]
]);
```

### Custom Success Callback

```php
<script>
function handleTeamAdded(data) {
    // Custom logic after team is added
    console.log('New team added:', data.newOption);

    // Update other UI elements
    updateTeamCounter();

    // Show custom notification
    showCustomNotification('Team added successfully!');
}
</script>
```

## Error Handling

The component automatically handles:

- **Validation errors**: Displayed in alert box within modal
- **Network errors**: Generic error message for connection issues
- **Server errors**: Custom error messages from controller

## Security Considerations

- **CSRF Protection**: Automatically includes CSRF token in requests
- **Input Validation**: Server-side validation still required in controller
- **XSS Prevention**: All output is properly escaped

## Testing

### Controller Tests

```php
public function testAjaxAddValid()
{
    $this->mockIdentity();
    $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

    $data = ['entity_name' => 'Test Entity'];
    $this->post('/admin/entities/ajaxAdd', $data);

    $this->assertResponseOk();
    $this->assertContentType('application/json');

    $response = json_decode((string)$this->_response->getBody(), true);
    $this->assertTrue($response['success']);
    $this->assertEquals('Test Entity', $response['newOption']['text']);
}
```

### Template Tests

```php
public function testAddFormWithPopup()
{
    $this->mockIdentity();
    $this->get('/admin/entities/add');

    $this->assertResponseOk();
    $this->assertResponseContains('data-bs-toggle="modal"');
    $this->assertResponseContains('Add New Entity');
}
```

## Real-World Example: Sport selection in Teams

See the implementation in:

- **Template**: `templates/Admin/Teams/add.php` (team form uses `sport_key` select populated from SiteOptions)
- **Controller**: `src/Controller/Admin/SiteOptionsController.php::editSportConfigs()` (sport configuration surface) and `src/Controller/Admin/TeamsController.php::ajaxAdd()` (team creation endpoint)
- **Tests**: `tests/TestCase/Controller/Admin/TeamsControllerTest.php` (verify `sport_key` handling and SiteOptions integration)

This example demonstrates selecting a sport via `sport_key` (the canonical runtime identifier). Sport configuration/creation is managed through the Admin Site Options UI rather than a popup-backed Sports CRUD.

## Troubleshooting

### Common Issues

1. **Modal not opening**: Check Bootstrap JavaScript is loaded
2. **AJAX request failing**: Verify CSRF token is properly set in layout
3. **Select not updating**: Ensure `targetSelectId` matches the select element ID
4. **Validation errors not showing**: Check controller returns proper error format

### Debug Tips

- Use browser developer tools to monitor AJAX requests
- Check console for JavaScript errors
- Verify JSON response format in Network tab
- Ensure controller method returns proper JSON structure

## Browser Support

- Modern browsers with ES6 support
- Bootstrap 5.3.2 compatible browsers
- JavaScript required for functionality
