<?php
/**
 * CakeDC Users Plugin Configuration
 *
 * This file configures the CakeDC/Users plugin to work with our existing users table
 * and provides role-based authorization for admin and public access.
 */

return [
    'Users' => [
        // Table configuration - use our existing users table with integer IDs
        'table' => 'Users',
        'primaryKey' => 'id',

        // Disable UUID - we use integer IDs
        'useUuid' => false,

        // Email configuration
        'Email' => [
            'required' => true,
            'validate' => true,
        ],

        // Registration configuration - controlled by site_options table
        'Registration' => [
            'active' => true, // We'll check site_options in beforeFilter
            'defaultRole' => 'user',
            'allowSocialLogin' => false, // Disable social login for now
        ],

        // Profile fields
        'Profile' => [
            'viewTemplate' => 'CakeDC/Users.Profile/view',
            'editTemplate' => 'CakeDC/Users.Profile/edit',
            'fields' => [
                'username' => true,
                'email' => true,
                'first_name' => true,
                'last_name' => true,
            ],
        ],

        // Google Authenticator / Two-Factor
        'GoogleAuthenticator' => [
            'login' => false, // Disable 2FA for now
            'checker' => false,
        ],

        // reCAPTCHA - disabled
        'reCaptcha' => [
            'registration' => false,
            'login' => false,
        ],

        // RememberMe
        'RememberMe' => [
            'active' => true,
            'checked' => true,
            'Cookie' => [
                'name' => 'remember_me',
                'expires' => '+1 month',
            ],
        ],

        // Routes configuration
        'routes' => [
            'prefix' => false, // Login/register are NOT in admin prefix
        ],

        // Controller configuration
        'controller' => [
            'Users' => 'CakeDC/Users.Users',
        ],

        // Auth / Auth component configuration
        'auth' => [
            // Redirect after successful login
            'loginRedirect' => '/',
            // Redirect after successful logout
            'logoutRedirect' => '/users/login',
            // Unauthorized redirect
            'unauthorizedRedirect' => '/users/login',
        ],

        // Key: field to identify user (username)
        'Key' => [
            'Data' => [
                'username' => 'username',
                'email' => 'email',
            ],
        ],

        // Social authentication (disabled)
        'Social' => [
            'login' => false,
        ],

        // oneTimePasswordAuthenticator (disabled)
        'OneTimePasswordAuthenticator' => [
            'login' => false,
            'checker' => false,
        ],
    ],
];
