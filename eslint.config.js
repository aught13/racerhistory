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
                $: 'readonly',
                jQuery: 'readonly',
                DataTable: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                fetch: 'readonly',
                setTimeout: 'readonly',
                FormData: 'readonly',
                Blob: 'readonly',
                File: 'readonly',
                bootstrap: 'readonly',
                Event: 'readonly',
                URLSearchParams: 'readonly',
                FileReader: 'readonly',
                Cropper: 'readonly',
            }
        }
    },
    {
        // Module files (ESM)
        files: ['webroot/js/modules/**/*.js', 'webroot/js/modules/**/*.mjs', 'webroot/js/*-loader.js', 'webroot/js/*-loader.mjs'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                $: 'readonly',
                jQuery: 'readonly',
                navigator: 'readonly',
                fetch: 'readonly',
                URL: 'readonly',
                caches: 'readonly',
                self: 'readonly',
                setTimeout: 'readonly',
                Promise: 'readonly',
            }
        }
    },
    {
        // CommonJS and other script-like module files
        files: ['webroot/js/modules/**/*.cjs', 'webroot/js/**/*.cjs'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'script',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                module: 'readonly',
                global: 'readonly',
                $: 'readonly',
                jQuery: 'readonly',
            }
        }
    },
    {
        files: ['webroot/js/hotwire/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                navigator: 'readonly',
                fetch: 'readonly',
                URL: 'readonly',
                caches: 'readonly',
                self: 'readonly',
            }
        }
    },
    {
        files: ['webroot/js/tests/**/*.js', 'webroot/js/tests/**/*.mjs'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                describe: 'readonly',
                it: 'readonly',
                test: 'readonly',
                expect: 'readonly',
                beforeEach: 'readonly',
                afterEach: 'readonly',
                beforeAll: 'readonly',
                afterAll: 'readonly',
                spyOn: 'readonly',
                sinon: 'readonly',
                jest: 'readonly',
                require: 'readonly',
                global: 'readonly',
                exports: 'readonly',
                Event: 'readonly',
                HTMLFormElement: 'readonly',
                MouseEvent: 'readonly',
                KeyboardEvent: 'readonly',
                document: 'readonly',
                window: 'readonly',
                $: 'readonly',
                jQuery: 'readonly',
                DataTable: 'readonly',
                HTMLElement: 'readonly',
                __dirname: 'readonly',
                __filename: 'readonly',
                module: 'readonly',
                // avoid redeclaring built-in Node globals here
            }
        }
    }
];
