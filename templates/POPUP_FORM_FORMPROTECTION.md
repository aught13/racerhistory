# Popup Form with FormProtection Implementation

## Overview

This implementation provides a secure popup form component that properly handles CakePHP's FormProtection security tokens for AJAX requests in the admin area.

## How It Works

### 1. FormProtection Security

CakePHP's FormProtection component requires a `_Token` field containing security hash data for all form submissions. This prevents CSRF attacks and form tampering.

For AJAX requests, we need to:

- Generate proper FormProtection tokens
- Include them in the AJAX request
- Maintain security without disabling protection

### 2. Implementation Strategy

Instead of disabling FormProtection (which reduces security), we:

1. **Generate Tokens in Template**: Create a hidden form that generates proper FormProtection tokens
2. **Extract Tokens in JavaScript**: Use JavaScript to extract tokens from the hidden form
3. **Include in AJAX Request**: Append the tokens to the FormData before submission

### 3. Code Structure

#### Hidden Form in Template (`templates/Admin/Teams/add.php`)

```php
<!-- Hidden form to generate FormProtection tokens for AJAX endpoints (e.g. team creation or SiteOptions actions) -->
<div style="display: none;">
    <?= $this->Form->create(null, [
        'url' => ['prefix' => 'Admin', 'controller' => 'Teams', 'action' => 'ajaxAdd'],
        'id' => 'hidden-ajax-form'
    ]) ?>
    <?= $this->Form->control('team_name', ['type' => 'text']) ?>
    <?= $this->Form->end() ?>
</div>
```

#### JavaScript Token Extraction (`templates/element/Admin/popup_form.php`)

```javascript
// Add FormProtection tokens from hidden form
const hiddenForm = document.getElementById('hidden-sport-form');
if (hiddenForm) {
    const tokenFields = hiddenForm.querySelectorAll('input[name^="_Token"]');
    tokenFields.forEach(field => {
        formData.append(field.name, field.value);
    });
}
```

#### Controller AJAX Endpoint (`src/Controller/Admin/SportsController.php`)

```php
public function ajaxAdd(): Response
{
    // FormProtection automatically validates _Token fields
    $sport = $this->Sports->newEmptyEntity();

    if ($this->request->is('post')) {
        // Normal processing - FormProtection handles security
        $sport = $this->Sports->patchEntity($sport, $this->request->getData());
        // ... rest of logic
    }

    return $this->response
        ->withType('application/json')
        # Popup Form with FormProtection Implementation (moved)

        The canonical documentation for the popup form component and FormProtection integration lives under `templates/POPUP_FORM_COMPONENT.md` and `templates/POPUP_FORM_FORMPROTECTION.md`.

        Please refer to those files for the complete implementation details and examples. This top-level file has been replaced to avoid duplication.
