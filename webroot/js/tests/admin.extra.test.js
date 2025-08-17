/** @jest-environment jsdom */
// Additional tests: fallback (no bootstrap), multiple invocations cleanup, invalid JSON, toast helper

describe('admin.js additional scenarios', () => {
    beforeEach(() => {
        jest.resetModules();
        jest.useFakeTimers();
        document.body.innerHTML = '';
        delete global.bootstrap; // ensure fallback path by default; individual tests can override
    });

    afterEach(() => {
        jest.runOnlyPendingTimers();
        jest.useRealTimers();
    });

    function setupModalDom() {
        document.body.innerHTML = `
      <div id="confirm-delete-modal" style="display:none">
        <ul id="confirm-delete-modal-assoc"></ul>
        <form id="confirm-delete-modal-hidden-form"></form>
        <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
      </div>
      <form id="delete-form-sample" method="post"></form>
    `;
    }

    test('fallback path without bootstrap shows modal by setting display:block', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        showConfirmDelete({ associated: JSON.stringify(['One']), formId: 'delete-form-sample' });
        const modal = document.getElementById('confirm-delete-modal');
        expect(modal.style.display).toBe('block');
    });

    test('multiple invocations clean up previously injected inputs', () => {
        setupModalDom();
        // provide a bootstrap mock so we also exercise that branch
        global.bootstrap = { Modal: { getOrCreateInstance: jest.fn(() => ({ show: jest.fn() })) } };
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();
        // first invocation
        showConfirmDelete({ deleteUrl: '/x', ids: JSON.stringify([1, 2]), idsName: 'sport_ids[]', formId: 'delete-form-sample', bulkAction: 'delete' });
        document.getElementById('confirm-delete-modal-delete-btn').click();
        expect(form.querySelectorAll('.injected-delete').length).toBe(3); // 2 ids + bulk
        // second invocation with different ids
        showConfirmDelete({ deleteUrl: '/x2', ids: JSON.stringify([7]), idsName: 'sport_ids[]', formId: 'delete-form-sample', bulkAction: 'delete' });
        document.getElementById('confirm-delete-modal-delete-btn').click();
        const injected = form.querySelectorAll('.injected-delete');
        expect(injected.length).toBe(2); // 1 id + bulk
        const idValues = Array.from(injected).filter(i => i.name === 'sport_ids[]').map(i => i.value);
        expect(idValues).toEqual(['7']);
    });

    test('invalid JSON associated and ids do not throw and produce no injected id inputs', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();
        expect(() => showConfirmDelete({ deleteUrl: '/bad', associated: 'not-json', ids: 'nope', idsName: 'sport_ids[]', formId: 'delete-form-sample' })).not.toThrow();
        document.getElementById('confirm-delete-modal-delete-btn').click();
        expect(form.querySelectorAll('.injected-delete').length).toBe(0); // Ensure no inputs are injected
    });

    test('AdminToast creates and removes alert with default info type', () => {
        // minimal DOM without modal still allows toast export
        document.body.innerHTML = '<div id="root"></div>';
        const { AdminToast } = require('../admin.js');
        AdminToast('Hello');
        let alerts = document.querySelectorAll('.alert');
        expect(alerts.length).toBe(1);
        expect(alerts[0].className).toContain('alert-info');
        jest.advanceTimersByTime(4000);
        alerts = document.querySelectorAll('.alert');
        expect(alerts.length).toBe(0);
    });

    test('AdminToast with custom type warning', () => {
        document.body.innerHTML = '<div id="root"></div>';
        const { AdminToast } = require('../admin.js');
        AdminToast('Warn', 'warning');
        const alert = document.querySelector('.alert');
        expect(alert).not.toBeNull();
        expect(alert.className).toContain('alert-warning');
    });

    Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', {
        value: jest.fn(function () {
            console.log('Forced mock requestSubmit called in admin.extra.test.js');
            if (this.submit) {
                this.submit();
            }
        }),
        configurable: true,
        writable: true
    });
});
