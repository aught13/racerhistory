describe('admin.js remaining branches', () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = `
            <div id="confirm-delete-modal">
                <ul id="confirm-delete-modal-assoc"></ul>
                <form id="confirm-delete-modal-hidden-form"></form>
            </div>
            <form id="sourceForm" action="/fromsource"></form>
            <button id="confirm-delete-modal-delete-btn">Delete</button>
        `;
        // require after DOM setup so event handlers attach

        this.admin = require('../admin');
    });

    afterEach(() => {
        jest.restoreAllMocks();
        document.body.innerHTML = '';
    });

    test('injects extra fields into source form and calls requestSubmit when available', () => {
        const admin = this.admin;
        const source = document.getElementById('sourceForm');
        // add existing injected-delete to be removed
        const old = document.createElement('input');
        old.className = 'injected-delete';
        source.appendChild(old);

        // spy requestSubmit
        source.requestSubmit = jest.fn();

        // set context to use sourceForm and provide ids
        admin.__internals.setContext({
            deleteUrl: '/del',
            ids: [7, 8],
            idsName: 'ids[]',
            formId: 'sourceForm',
        });

        // click delete button
        document.getElementById('confirm-delete-modal-delete-btn').click();

        // requestSubmit should be called
        expect(source.requestSubmit).toHaveBeenCalled();

        // injected inputs should be present with names 'ids[]'
        const injected = Array.from(source.querySelectorAll('.injected-delete'));
        expect(injected.length).toBeGreaterThanOrEqual(2);
        expect(injected.some((i) => i.name === 'ids[]')).toBe(true);
    });

    test('falls back to temp form when source.getAttribute throws (logs error)', () => {
        const admin = this.admin;
        const spyErr = jest.spyOn(console, 'error').mockImplementation(() => {});
        // create a source that throws on getAttribute
        const bad = document.createElement('form');
        bad.id = 'badForm';
        bad.getAttribute = () => {
            throw new Error('getAttr boom');
        };
        document.body.appendChild(bad);

        admin.__internals.setContext({
            deleteUrl: '/x',
            ids: '[1]',
            idsName: 'ids[]',
            formId: 'badForm',
        });
        document.getElementById('confirm-delete-modal-delete-btn').click();

        // Error should be logged when getAttribute throws
        expect(spyErr).toHaveBeenCalled();

        // A temporary form should be appended to the body and target the delete URL
        const tempForm = Array.from(document.querySelectorAll('form')).find(
            (f) => f.action && f.action.endsWith('/x')
        );
        expect(tempForm).toBeDefined();

        spyErr.mockRestore();
    });

    test('handles errors preparing source form (querySelectorAll throws)', () => {
        const admin = this.admin;
        const spyErr = jest.spyOn(console, 'error').mockImplementation(() => {});

        const src = document.getElementById('sourceForm');
        // make querySelectorAll throw when cleaning injected-delete
        src.querySelectorAll = () => {
            throw new Error('qsa boom');
        };

        admin.__internals.setContext({
            deleteUrl: '/p',
            ids: '[2]',
            idsName: 'ids[]',
            formId: 'sourceForm',
        });
        document.getElementById('confirm-delete-modal-delete-btn').click();

        // We expect the error path to be executed and logged
        expect(spyErr).toHaveBeenCalled();
        spyErr.mockRestore();
    });
});
