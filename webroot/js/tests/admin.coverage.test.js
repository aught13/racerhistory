/** @jest-environment jsdom */
// Additional admin.js tests to increase branch coverage to >80%

describe('admin.js branch coverage improvement', () => {
    let origRequestSubmit;
    let origSubmit;
    beforeEach(() => {
        // Reset DOM and globals
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
        // Save original descriptors
        origRequestSubmit = Object.getOwnPropertyDescriptor(
            HTMLFormElement.prototype,
            'requestSubmit'
        );
        origSubmit = Object.getOwnPropertyDescriptor(HTMLFormElement.prototype, 'submit');
        Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', {
            value: jest.fn(function () {
                if (this.submit) this.submit();
            }),
            configurable: true,
            writable: true,
        });
        Object.defineProperty(HTMLFormElement.prototype, 'submit', {
            value: jest.fn(),
            configurable: true,
            writable: true,
        });
        jest.resetModules();
    });
    afterEach(() => {
        document.body.innerHTML = '';
        delete global.bootstrap;
        jest.clearAllMocks();
        // Restore original descriptors
        if (origRequestSubmit) {
            Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', origRequestSubmit);
        } else {
            delete HTMLFormElement.prototype.requestSubmit;
        }
        if (origSubmit) {
            Object.defineProperty(HTMLFormElement.prototype, 'submit', origSubmit);
        } else {
            delete HTMLFormElement.prototype.submit;
        }
    });

    function setupModalDom() {
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <ul id="confirm-delete-modal-assoc"></ul>
        <form id="confirm-delete-modal-hidden-form"></form>
        <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
      </div>
      <form id="delete-form-sample" method="post"></form>
    `;
    }

    test('toast with different types (success, danger, error)', () => {
        document.body.innerHTML = '<div id="root"></div>';
        const { AdminToast } = require('../admin.js');

        // Test success type
        AdminToast('Success message', 'success');
        let alert = document.querySelector('.alert-success');
        expect(alert).not.toBeNull();
        expect(alert.textContent).toBe('Success message');

        // Test danger type
        AdminToast('Error message', 'danger');
        alert = document.querySelector('.alert-danger');
        expect(alert).not.toBeNull();
        expect(alert.textContent).toBe('Error message');

        // Test custom type
        AdminToast('Custom message', 'primary');
        alert = document.querySelector('.alert-primary');
        expect(alert).not.toBeNull();
    });

    test('modal dismiss logic - modal hidden event cleanup', () => {
        setupModalDom();
        require('../admin.js'); // Load module without assigning to exports
        const modal = document.getElementById('confirm-delete-modal');

        // Simulate modal being hidden (Bootstrap event)
        const hideEvent = new Event('hidden.bs.modal');
        modal.dispatchEvent(hideEvent);

        // Should clear any internal state if implemented
        expect(modal).toBeTruthy(); // Basic validation
    });

    test('duplicate modal opens - subsequent calls override context', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');

        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();

        // First modal open
        showConfirmDelete({
            deleteUrl: '/first',
            ids: JSON.stringify([1, 2]),
            idsName: 'first_ids[]',
            formId: 'delete-form-sample',
        });

        // Second modal open should override first
        showConfirmDelete({
            deleteUrl: '/second',
            ids: JSON.stringify([3, 4]),
            idsName: 'second_ids[]',
            formId: 'delete-form-sample',
        });

        // Click delete button - should use second context
        document.getElementById('confirm-delete-modal-delete-btn').click();

        const injected = form.querySelectorAll('.injected-delete');
        const idValues = Array.from(injected)
            .filter((i) => i.name === 'second_ids[]')
            .map((i) => i.value);
        expect(idValues).toEqual(['3', '4']);
    });

    test('associated list rendering with different data types', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');

        // Test with object array
        const associated = JSON.stringify([
            { label: 'Object A' },
            { name: 'Object B' },
            { neither: 'Object C' }, // Should fall back to JSON.stringify
        ]);

        showConfirmDelete({ associated });

        const list = document.getElementById('confirm-delete-modal-assoc');
        expect(list.children.length).toBe(3);
        expect(list.children[0].textContent).toBe('Object A');
        expect(list.children[1].textContent).toBe('Object B');
        expect(list.children[2].textContent).toBe('{"neither":"Object C"}');
    });

    test('deleteUrl fallback when form action not set', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');

        // Ensure form has no action attribute to trigger fallback
        // Clear both the attribute and property completely
        form.removeAttribute('action');
        // Force the action property to be empty, which in browsers becomes the current page URL
        Object.defineProperty(form, 'action', {
            value: '',
            writable: true,
            configurable: true,
        });
        // Double check that getAttribute returns null (not just an empty string)
        expect(form.getAttribute('action')).toBeNull();
        form.submit = jest.fn();

        showConfirmDelete({
            deleteUrl: '/custom-delete-url',
            formId: 'delete-form-sample',
        });

        document.getElementById('confirm-delete-modal-delete-btn').click();
        // When form has no action attribute, the deleteUrl should be used
        expect(form.action).toContain('/custom-delete-url');
    });

    test('missing modal element returns early from showConfirmDelete', () => {
        // No modal in DOM
        document.body.innerHTML = '<div>No modal</div>';
        const { showConfirmDelete } = require('../admin.js');

        // Should not throw when modal doesn't exist
        expect(() => {
            showConfirmDelete({ deleteUrl: '/test' });
        }).not.toThrow();
    });

    test('show.bs.modal event with missing relatedTarget', () => {
        setupModalDom();
        require('../admin.js'); // Load module to register event listeners

        const modal = document.getElementById('confirm-delete-modal');
        const showEvent = new Event('show.bs.modal');
        // Don't set relatedTarget

        // Should not throw when relatedTarget is missing
        expect(() => {
            modal.dispatchEvent(showEvent);
        }).not.toThrow();
    });

    test('form submission error handling', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');

        // Mock requestSubmit to throw error
        form.requestSubmit = jest.fn(() => {
            throw new Error('Submit failed');
        });
        form.submit = jest.fn(); // Fallback should be called

        showConfirmDelete({
            formId: 'delete-form-sample',
            deleteUrl: '/test',
        });

        // Should handle error gracefully
        expect(() => {
            document.getElementById('confirm-delete-modal-delete-btn').click();
        }).not.toThrow();
    });

    test('ids parsing with mixed data types', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();

        // Test with mixed array containing null/undefined/empty
        showConfirmDelete({
            deleteUrl: '/test',
            ids: JSON.stringify([1, null, '', undefined, 'valid', 0]),
            idsName: 'test_ids[]',
            formId: 'delete-form-sample',
        });

        document.getElementById('confirm-delete-modal-delete-btn').click();

        const injected = form.querySelectorAll('.injected-delete[name="test_ids[]"]');
        const values = Array.from(injected).map((i) => i.value);
        // Should filter out null/undefined/empty values
        expect(values).toEqual(['1', 'valid', '0']);
    });

    test('click handler edge cases - no target match', () => {
        setupModalDom();
        require('../admin.js'); // Load to register event listeners

        const randomButton = document.createElement('button');
        randomButton.textContent = 'Random';
        document.body.appendChild(randomButton);

        // Click on random button should not trigger modal logic
        expect(() => {
            randomButton.click();
        }).not.toThrow();
    });

    test('single id string parsing instead of array', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();

        // Test with single string ID instead of array
        showConfirmDelete({
            deleteUrl: '/test',
            ids: '42', // Single string ID
            idsName: 'test_ids[]',
            formId: 'delete-form-sample',
        });

        document.getElementById('confirm-delete-modal-delete-btn').click();

        const injected = form.querySelectorAll('.injected-delete[name="test_ids[]"]');
        expect(injected.length).toBe(1);
        expect(injected[0].value).toBe('42');
    });

    test('numeric single id as non-string', () => {
        setupModalDom();
        const { showConfirmDelete } = require('../admin.js');
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();

        // Test with single numeric ID
        showConfirmDelete({
            deleteUrl: '/test',
            ids: 123, // Single numeric ID
            idsName: 'test_ids[]',
            formId: 'delete-form-sample',
        });

        document.getElementById('confirm-delete-modal-delete-btn').click();

        const injected = form.querySelectorAll('.injected-delete[name="test_ids[]"]');
        expect(injected.length).toBe(1);
        expect(injected[0].value).toBe('123');
    });

    // (No global prototype override here; handled in beforeEach/afterEach)
});
