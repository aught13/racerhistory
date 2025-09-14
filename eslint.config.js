/* eslint-env node */
// @ts-check

const eslint = require('@eslint/js');

module.exports = [
    eslint.configs.recommended,
    {
        // Ignore third-party libraries, config and vendor files
        ignores: [
            'eslint.config.js',
            'jest.setup.js',
            'webroot/js/tinymce/**',
            'vendor/**',
            'node_modules/**',
            'tmp/**',
            'logs/**',
            'coverage-js/**'
        ]
    },
    {
        files: ['webroot/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'script',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                fetch: 'readonly',
                setTimeout: 'readonly',
                FormData: 'readonly',
                Blob: 'readonly',
                File: 'readonly',
                bootstrap: 'readonly',
            }
        }
    },
    {
        files: ['webroot/js/tests/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                describe: 'readonly',
                test: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                jest: 'readonly',
                require: 'readonly',
                global: 'readonly',
                exports: 'readonly',
                Event: 'readonly',
                HTMLFormElement: 'readonly',
            }
        }
    }
];
