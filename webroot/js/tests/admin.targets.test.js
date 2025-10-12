beforeAll(() => {
    if (typeof HTMLFormElement !== 'undefined') {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

describe('admin.js targeted branch tests', () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = '';
        if (typeof window !== 'undefined') {
            delete window.showConfirmDelete;
            delete window.AdminToast;
        }
        global.bootstrap = undefined;
        jest.useFakeTimers();
    });

    afterEach(() => {
        jest.useRealTimers();
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

    test('parses single numeric id string with whitespace using parseInt fallback', () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = require('../admin.js');
        // provide ids as a padded numeric string
        showConfirmDelete({ deleteUrl: '/d', ids: '  42  ', idsName: 'ids[]' });
        // trigger delete
        document.getElementById('confirm-delete-modal-delete-btn').click();
        // temp form should be created and contain the injected ids[] hidden input
        const temp = Array.from(document.querySelectorAll('form')).find((f) =>
            f.action.includes('/d')
        );
        const inputs = temp.querySelectorAll('input[type="hidden"][name="ids[]"]');
        expect(inputs.length).toBeGreaterThanOrEqual(1);
        expect(inputs[0].value).toBe('42');
    });

    test('handles array ids correctly and includes bulkAction', () => {
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
        `;
        const { showConfirmDelete } = require('../admin.js');
        // provide ids as an array
        showConfirmDelete({
            deleteUrl: '/arr',
            ids: [1, 2, 3],
            idsName: 'ids[]',
            bulkAction: 'delete',
        });
        document.getElementById('confirm-delete-modal-delete-btn').click();
        // The code prefers injecting into an existing hidden form if present. Check both locations.
        const temp = Array.from(document.querySelectorAll('form')).find(
            (f) => f.action && f.action.includes('/arr')
        );
        let targetForm = temp;
        if (!targetForm) {
            // fallback to the modal hidden form which should have injected inputs
            targetForm = document.getElementById('confirm-delete-modal-hidden-form');
        }
        expect(targetForm).toBeTruthy();
        const names = Array.from(targetForm.querySelectorAll('input[type="hidden"]')).map(
            (i) => i.name
        );
        expect(names).toContain('ids[]');
        expect(names).toContain('bulk_action');
    });

    test('prefers source form when context.formId references existing form with action', () => {
        document.body.innerHTML = `
          <div>
            <form id="sourceForm" action="/fromsource">
            </form>
            <div id="confirm-delete-modal">
              <ul id="confirm-delete-modal-assoc"></ul>
              <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
            </div>
          </div>
        `;
        const { showConfirmDelete } = require('../admin.js');
        // provide formId so source form path is used
        showConfirmDelete({
            ids: '[7]',
            idsName: 'ids[]',
            formId: 'sourceForm',
            deleteUrl: '/shouldnotuse',
        });
        // attach a spy to the form.submit and requestSubmit
        const src = document.getElementById('sourceForm');
        let submitted = false;
        src.submit = function () {
            submitted = true;
        };
        // jsdom may implement requestSubmit; ensure it calls our submit path
        src.requestSubmit = function () {
            submitted = true;
        };
        document.getElementById('confirm-delete-modal-delete-btn').click();
        expect(submitted).toBe(true);
        // ensure action remains source action
        expect(src.action).toContain('/fromsource');
    });

    test('AdminToast adds a node and it is removed after timeout', () => {
        document.body.innerHTML = '<div></div>';
        const { AdminToast } = require('../admin.js');
        AdminToast('hi', 'success');
        expect(document.querySelectorAll('.alert').length).toBe(1);
        // advance timers to allow removal
        jest.advanceTimersByTime(5000);
        expect(document.querySelectorAll('.alert').length).toBe(0);
    });
});
