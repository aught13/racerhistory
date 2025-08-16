/** @jest-environment jsdom */
// Tests for shared admin.js confirm delete logic

global.bootstrap = {
    Modal: {
        getOrCreateInstance: jest.fn(() => ({ show: jest.fn() }))
    }
};

document.body.innerHTML = `
  <div id="confirm-delete-modal">
    <ul id="confirm-delete-modal-assoc"></ul>
    <form id="confirm-delete-modal-hidden-form"></form>
    <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
  </div>
  <form id="delete-form-sample" method="post"></form>
`;

const { showConfirmDelete } = require('../admin.js');

describe('admin.js showConfirmDelete', () => {
    test('populates associated list and submits with injected inputs', () => {
        const associated = JSON.stringify(['Item A', 'Item B']);
        const ids = JSON.stringify([11, 22]);
        showConfirmDelete({
            deleteUrl: '/admin/delete/bulk',
            associated,
            ids,
            idsName: 'sport_ids[]',
            formId: 'delete-form-sample',
            bulkAction: 'delete'
        });
        const list = document.getElementById('confirm-delete-modal-assoc');
        expect(list.children.length).toBe(2);
        const form = document.getElementById('delete-form-sample');
        form.submit = jest.fn();
        document.getElementById('confirm-delete-modal-delete-btn').click();
        expect(form.action).toContain('/admin/delete/bulk');
        const injected = form.querySelectorAll('.injected-delete');
        expect(injected.length).toBe(3); // 2 ids + bulk_action
        const bulk = Array.from(injected).find(i => i.name === 'bulk_action');
        expect(bulk.value).toBe('delete');
    });
});
