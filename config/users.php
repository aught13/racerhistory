<?php
/**
 * CakeDC/Users configuration overrides
 *
 * The plugin loads its defaults from vendor/cakedc/users/config/users.php first,
 * then loads this file via Users.config (see config/app.php).
 */

return [
    // Use the application's existing Users table (integer IDs)
    'Users' => [
        'table' => 'Users',
        'Email' => [
            // Keep the plugin email field, but disable email-validation workflow for now.
            'required' => true,
            'validate' => false,
        ],
        // Public site is read/search/view. Disable account creation outside /admin.
        'Registration' => [
            'active' => false,
        ],
        // Disable terms-of-service requirement in the plugin templates.
        'Tos' => [
            'required' => false,
        ],
        // Disable social login for now.
        'Social' => [
            'login' => false,
        ],
    ],

    // Let the app keep ownership of authorization policies.
    // (CakeDC/Users ships optional authorization wiring via Configure keys.)
    'Auth' => [
        'Authorization' => [
            'enable' => false,
        ],
        // SetupComponent checks `Auth.AuthorizationComponent.enable` (not `enabled`).
        'AuthorizationComponent' => [
            'enable' => false,
        ],
        // Ensure consistent redirect parameter.
        'AuthenticationComponent' => [
            'load' => true,
            'requireIdentity' => false,
        ],

        // Make Form authentication deterministic:
        // - Only attempts authentication on the login endpoints.
        // - Ensures the expected field names match our login form.
        'Authenticators' => [
            'Form' => [
                'fields' => [
                    'username' => 'username',
                    'password' => 'password',
                ],
                'loginUrl' => [
                    '/users/login',
                    '/login',
                ],
                // Use strict path comparison against the strings above.
                'urlChecker' => [
                    'className' => 'Authentication.Default',
                ],
            ],
        ],
    ],

    // Disable magic-link login on the public site.
    'OneTimeLogin' => [
        'enabled' => false,
    ],
];
