# Authorization Setup Documentation

## Overview

This application uses **CakePHP Authorization Plugin** with policy-based authorization. We use **CakeDC/Auth** only for its authorization infrastructure, not the full Users plugin.

## Architecture

### Authorization Flow

1. **Request arrives** → Authentication middleware identifies user
2. **Authorization middleware** activates → Injects authorization service into request
3. **Controller checks permissions** → Uses policies to authorize actions
4. **Policy resolves** → Determines if user can perform action

### Components

#### 1. Application.php - Authorization Service Configuration

```php
public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    // MapResolver: Maps concrete classes to policies
    $mapResolver = new MapResolver();
    $mapResolver->map(\Cake\Http\ServerRequest::class, \App\Policy\RequestPolicy::class);

    // ResolverCollection: Combines resolvers
    $resolvers = new ResolverCollection([
        $mapResolver,   // For Request objects → RequestPolicy
        new OrmResolver(), // For ORM entities → {Entity}Policy
    ]);

    return new AuthorizationService($resolvers);
}
```

**Key Points:**
- Uses `MapResolver` for Request objects (maps ServerRequest → RequestPolicy)
- Uses `OrmResolver` for ORM entities (maps User entity → UserPolicy)
- Authorization middleware added AFTER Authentication middleware

#### 2. Policy Classes

##### RequestPolicy - Request-Level Authorization

**Location:** `src/Policy/RequestPolicy.php`

**Purpose:** Handles authorization for ServerRequest objects (route-level access control)

**Key Method:**
```php
public function canAccessAdmin(IdentityInterface $identity, ServerRequest $request): bool
{
    // Admin access requires:
    // 1. role = 'admin'
    // 2. active = true OR status = 'active'

    return $this->extractUserField($identity, 'role') === 'admin'
        && ($this->extractUserField($identity, 'active') === true
            || $this->extractUserField($identity, 'status') === 'active');
}
```

**Usage in Admin/AppController:**
```php
$this->Authorization->authorize($this->request, 'accessAdmin');
```

##### UserPolicy - Entity-Level Authorization

**Location:** `src/Policy/UserPolicy.php`

**Purpose:** Controls what actions users can perform on User entities

**Key Methods:**
- `canView()` - Who can view user profiles
- `canEdit()` - Who can edit user records (admin or own profile)
- `canDelete()` - Who can delete users (admin only, cannot delete self)
- `canAdd()` - Who can add new users (admin only)
- `canApprove()` - Who can approve pending users (admin only)

**Usage:**
```php
$this->Authorization->authorize($user, 'edit');
```

##### ApplicationPolicy - Fallback Policy

**Location:** `src/Policy/ApplicationPolicy.php`

**Purpose:** Provides default authorization methods (currently redundant with RequestPolicy)

**Note:** May be removed in future refactoring as RequestPolicy handles request-level checks.

#### 3. User Entity - Authentication Identity Only

**Location:** `src/Model/Entity/User.php`

**Interface Implemented:**
- `AuthenticationIdentityInterface` - For authentication only

**Important:** The User entity does NOT implement `AuthorizationIdentityInterface`. The Authorization plugin automatically wraps authenticated identities in an `IdentityDecorator` that provides authorization methods (`can()`, `cannot()`, `canResult()`, `applyScope()`).

**Key Methods:**
```php
// AuthenticationIdentityInterface methods
public function getIdentifier(): array|string|int|null;
public function getOriginalData(): array|\ArrayAccess;
```

**Usage in Templates:**
```php
<?php
// The identity is automatically wrapped with authorization methods
$identity = $this->request->getAttribute('identity');
if ($identity && $identity->can('edit', $user)) {
    // Show edit button
}
?>
```

**Why NOT AuthorizationIdentityInterface:**
- Implementing both interfaces on the same class causes conflicts
- The Authorization middleware handles decoration automatically
- The decorator adds authorization methods dynamically
- This prevents "Authorization has not been set" errors

#### 4. UsersTable - Entity Synchronization

**Location:** `src/Model/Table/UsersTable.php`

**beforeSave() Logic:**
- Syncs `active` (boolean) ↔ `status` (string) fields for backward compatibility
- Sets `is_superuser = true` when `role = 'admin'`
- Sets `activation_date` when user becomes active
- Hashes passwords using DefaultPasswordHasher

## Controller Authorization Patterns

### Admin Controllers

**Base:** `Admin/AppController`

**Pattern:**
```php
class AppController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authorization.Authorization');
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Skip login action
        if ($this->request->getParam('action') === 'login') {
            return;
        }

        // Check authentication
        if (!$this->Authentication->getIdentity()) {
            // Redirect to login
        }

        // Authorize admin access
        $this->Authorization->authorize($this->request, 'accessAdmin');
    }
}
```

**Child Controllers:** Inherit authorization automatically

**Login Exception:** `Admin/UsersController::login` skips authorization:
```php
$this->Authorization->skipAuthorization(['login']);
```

### Public Controllers

**Pattern:**
```php
class PagesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        // Load and skip authorization (public pages)
        $this->loadComponent('Authorization.Authorization');
        $this->Authorization->skipAuthorization();
    }
}
```

**Controllers Using This Pattern:**
- `PagesController` - Static pages
- `ImagesController` - Public image serving
- `ErrorController` - Error pages

### Semi-Public Controllers (UsersController)

**Pattern:**
```php
class UsersController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Skip authentication for public actions
        $this->Authentication->addUnauthenticatedActions(['login', 'register', 'resetPassword']);

        // Skip authorization for public actions
        $this->Authorization->skipAuthorization(['login', 'logout', 'register', 'resetPassword']);
    }
}
```

## Database Schema

### Migration: 20251130120000_AddCakeDcUsersFields.php

**Added Fields:**
- `first_name`, `last_name` - User names (VARCHAR 50)
- `active` - Boolean status (TINYINT, default 1)
- `is_superuser` - Admin flag (TINYINT, default 0)
- `token`, `token_expires` - Password reset tokens
- `api_token` - API authentication (VARCHAR 255)
- `activation_date`, `tos_date`, `last_login` - Timestamps
- `secret`, `secret_verified` - 2FA fields (unused)
- `additional_data` - JSON data (TEXT)

**Migration Logic:**
```php
// Sync status → active
->addColumn('active', 'boolean', ['default' => true])
->addColumn('is_superuser', 'boolean', ['default' => false])

// Set active based on existing status
$this->execute("UPDATE users SET active = 1 WHERE status = 'active'");
$this->execute("UPDATE users SET active = 0 WHERE status != 'active'");

// Set is_superuser for admins
$this->execute("UPDATE users SET is_superuser = 1 WHERE role = 'admin'");
```

**Backward Compatibility:**
- `status` field maintained alongside `active`
- UsersTable syncs both fields on save

## Testing

### Test Helpers

**AuthTestTrait:**
```php
protected function mockIdentity(array $data = []): void
{
    // Injects authenticated user into session
    $this->session(['Auth' => $data]);
}
```

**Usage:**
```php
$this->mockIdentity([
    'id' => 1,
    'username' => 'admin',
    'role' => 'admin',
    'status' => 'active',
]);
$this->get('/admin');
$this->assertResponseOk();
```

### Test Coverage

**Passing Test Suites:**
- `Admin/UsersControllerTest` - 19 tests, 47 assertions
- `UsersControllerTest` - 11 tests, 20 assertions
- `Admin/AppControllerTest` - 11 tests, 15 assertions
- All controller tests - 278 tests, 774 assertions

### Key Test Scenarios

1. **Unauthenticated Access**
   - Admin routes redirect to login
   - Public routes work without authentication

2. **Authenticated Non-Admin**
   - Redirected from admin area with error message
   - Can access public routes

3. **Authenticated Admin (Inactive)**
   - Blocked from admin area (must be active)
   - Error: "You do not have permission to access the admin area."

4. **Authenticated Admin (Active)**
   - Full access to admin area
   - Can perform all admin actions

## Configuration

### Authentication Service - Simple Configuration

**The Authentication service requires NO special identity decorator configuration.** The Authorization middleware automatically decorates authenticated identities.

**Application.php:**
```php
public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
{
    $service = new AuthenticationService([
        'unauthenticatedRedirect' => '/users/login',
        'queryParam' => 'redirect',
    ]);

    $service->loadAuthenticator('Authentication.Session');
    $service->loadAuthenticator('Authentication.Form', [
        'identifier' => 'Authentication.Password',
        'fields' => [
            'username' => 'username',
            'password' => 'password',
        ],
        'loginUrl' => '/users/login',
    ]);

    return $service;
}
```

**How Authorization Works:**
1. User logs in → User entity is authenticated and stored in session
2. On subsequent requests → Authentication middleware retrieves User entity
3. Authorization middleware runs → Automatically wraps User entity in `IdentityDecorator`
4. Identity decorator adds authorization methods → `can()`, `cannot()`, `canResult()`, `applyScope()`
5. Controllers and templates use these methods → Permission checks work correctly

**Critical:** Do NOT implement `AuthorizationIdentityInterface` on the User entity. Let the middleware handle decoration.

### CakeDC/Auth Plugin Loading

**Application.php:**
```php
$this->addPlugin('CakeDC/Auth', [
    'bootstrap' => false, // Don't load their config (prevents 2FA dependencies)
    'routes' => false,
]);
```

**Why bootstrap = false?**
- CakeDC/Auth's bootstrap config loads 2FA features (RobThree\Auth)
- We don't need 2FA, only authorization infrastructure
- Disabling bootstrap prevents "Class not found" errors for unused dependencies

## Common Operations

### Check Admin Access in Policy

```php
// In RequestPolicy or ApplicationPolicy
public function canAccessAdmin(IdentityInterface $identity, $request): bool
{
    $role = $this->extractUserField($identity, 'role');
    $active = $this->extractUserField($identity, 'active');
    $status = $this->extractUserField($identity, 'status');

    return $role === 'admin'
        && ($active === true || $status === 'active');
}
```

### Authorize Action in Controller

```php
// Check request-level permission
$this->Authorization->authorize($this->request, 'accessAdmin');

// Check entity-level permission
$user = $this->Users->get($id);
$this->Authorization->authorize($user, 'edit');

// Skip authorization for specific actions
$this->Authorization->skipAuthorization(['login', 'register']);
```

### Check Permission in Template

```php
<?php if ($identity->can('edit', $user)): ?>
    <a href="<?= $this->Url->build(['action' => 'edit', $user->id]) ?>">Edit</a>
<?php endif; ?>
```

## Troubleshooting

### Error: "Authorization has not been set on this identity"

**Cause:** The User entity was implementing `AuthorizationIdentityInterface` directly, which conflicts with the automatic decoration done by the Authorization middleware.

**Solution:** Remove `AuthorizationIdentityInterface` from the User entity:

```php
// ❌ WRONG - Don't do this
class User extends Entity implements AuthenticationIdentity, AuthorizationIdentity
{
    protected ?AuthorizationServiceInterface $_authorization = null;

    public function can(string $action, mixed $resource): bool
    {
        return $this->getAuthorization()->can($this, $action, $resource);
    }
    // ... other authorization methods
}

// ✅ CORRECT - Only implement AuthenticationIdentity
class User extends Entity implements AuthenticationIdentity
{
    // NO authorization-related properties or methods
    // The Authorization middleware handles this automatically
}
```

The Authorization middleware automatically wraps authenticated identities in an `IdentityDecorator` that provides all authorization methods.

### Error: "The request did not apply any authorization checks"

**Cause:** Controller action didn't call `authorize()` or `skipAuthorization()`

**Solution:** Add to controller:
```php
$this->Authorization->skipAuthorization(); // For public actions
// OR
$this->Authorization->authorize($this->request, 'accessAdmin'); // For protected actions
```

### Error: "Resource class does not exist"

**Cause:** MapResolver can only map concrete classes, not interfaces

**Solution:** Map the concrete class:
```php
// ✅ Correct
$mapResolver->map(\Cake\Http\ServerRequest::class, \App\Policy\RequestPolicy::class);

// ❌ Wrong
$mapResolver->map(\Psr\Http\Message\ServerRequestInterface::class, \App\Policy\RequestPolicy::class);
```

### Error: "Class 'RobThree\Auth\Algorithm' not found"

**Cause:** CakeDC/Auth bootstrap config trying to load 2FA features

**Solution:** Disable bootstrap when loading plugin:
```php
$this->addPlugin('CakeDC/Auth', ['bootstrap' => false]);
```

## Future Enhancements

1. **Remove ApplicationPolicy** - Redundant with RequestPolicy
2. **Add Resource-Level Policies** - Games, Seasons, etc.
3. **Implement Scope Authorization** - Filter queries based on user permissions
4. **Add Policy Tests** - Direct unit tests for policy logic
5. **Registration Toggle Integration** - Verify SiteOptions integration works as expected

## References

- [CakePHP Authorization Plugin](https://book.cakephp.org/authorization/2/en/index.html)
- [CakeDC/Auth Documentation](https://github.com/CakeDC/auth)
- [Policy-Based Authorization](https://book.cakephp.org/authorization/2/en/policies.html)
