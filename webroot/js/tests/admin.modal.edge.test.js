/* eslint-env jest */

beforeAll(() => {
    if (typeof HTMLFormElement !== 'undefined') {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */
// admin.modal.edge.test.js - Tests for admin.js confirm-delete/modal with missing/malformed elements

describe('admin.js confirm-delete modal edge cases', () => {
    let exports;
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = '';
        if (typeof window !== 'undefined') {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => ({ show: jest.fn() })),
            },
        };
        exports = require('../admin.js');
    });

    afterEach(() => {
        // Clean up DOM and globals
        document.body.innerHTML = '';
        if (typeof window !== 'undefined') {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
        // Restore HTMLFormElement methods if patched
        if (typeof HTMLFormElement !== 'undefined') {
            HTMLFormElement.prototype.submit = function () {};
            HTMLFormElement.prototype.requestSubmit = function () {};
        }
    });

    test('showConfirmDelete does nothing if modal missing', () => {
        expect(() =>
            exports.showConfirmDelete({ deleteUrl: '/x', ids: 1, idsName: 'ids[]', formId: 'f' })
        ).not.toThrow();
    });

    test('showConfirmDelete does nothing if delete button missing', () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
          </div>
        `;
        expect(() =>
            exports.showConfirmDelete({ deleteUrl: '/x', ids: 1, idsName: 'ids[]', formId: 'f' })
        ).not.toThrow();
    });

    test('showConfirmDelete handles missing associated list gracefully', () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        expect(() =>
            exports.showConfirmDelete({ deleteUrl: '/x', ids: 1, idsName: 'ids[]', formId: 'f' })
        ).not.toThrow();
    });

    test('showConfirmDelete handles missing hidden form gracefully', () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        expect(() =>
            exports.showConfirmDelete({ deleteUrl: '/x', ids: 1, idsName: 'ids[]', formId: 'f' })
        ).not.toThrow();
    });
});
