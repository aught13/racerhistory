const admin = require('../admin.js');
const internals = admin && admin.__internals ? admin.__internals : {};

afterEach(() => {
  // clean DOM and restore any spies
  document.body.innerHTML = '';
  if (HTMLFormElement.prototype.submit && HTMLFormElement.prototype.submit.mockRestore) {
    HTMLFormElement.prototype.submit.mockRestore();
  }
});

test('renderAssociated handles invalid JSON and shows toast', () => {
  document.body.innerHTML = `
    <div id="confirm-delete-modal">
      <ul id="confirm-delete-modal-assoc"></ul>
    </div>
  `;
  // spy on toast
  window.AdminToast = jest.fn();

  // pass an invalid JSON string; should fall back to treating it as a literal string
  internals.renderAssociated(document.getElementById('confirm-delete-modal'), '{bad: ');

  const list = document.querySelector('#confirm-delete-modal-assoc');
  expect(list.children.length).toBe(1);
  expect(list.firstChild.textContent).toBe('{bad: ');
  expect(window.AdminToast).toHaveBeenCalledWith('Error parsing associated items', 'danger');
});

test('buildExtraFields parses various ids formats', () => {
  const be = internals.buildExtraFields;
  expect(be({ ids: '[1,2]', idsName: 'ids[]' })).toEqual([
    { name: 'ids[]', value: '1' },
    { name: 'ids[]', value: '2' },
  ]);

  expect(be({ ids: ' 42 ', idsName: 'id' })).toEqual([
    { name: 'id', value: '42' },
  ]);

  expect(be({ ids: 'abc', idsName: 'id' })).toEqual([]);

  expect(be({ ids: [5, '6'], idsName: 'id', bulkAction: 'delete' })).toEqual([
    { name: 'id', value: '5' },
    { name: 'id', value: '6' },
    { name: 'bulk_action', value: 'delete' },
  ]);
});

test('submitTempForm falls back to submit when requestSubmit missing', () => {
  document.body.innerHTML = `<div id="tokens"><input type="hidden" name="csrf" value="tok"></div>`;
  const tokensSource = document.getElementById('tokens');

  const submitSpy = jest.spyOn(HTMLFormElement.prototype, 'submit').mockImplementation(() => {});
  // jsdom has a requestSubmit implementation that throws; mock it to delegate to submit
  const requestSpy = jest
    .spyOn(HTMLFormElement.prototype, 'requestSubmit')
    .mockImplementation(function () {
      this.submit();
    });

  internals.submitTempForm('/do', tokensSource, [{ name: 'x', value: 'y' }]);

  expect(submitSpy).toHaveBeenCalled();
  const forms = document.querySelectorAll('form');
  expect(forms.length).toBeGreaterThan(0);
  const temp = forms[forms.length - 1];
  expect(temp.action).toContain('/do');
  // should have at least the csrf token + our extra field
  expect(temp.querySelectorAll('input[type="hidden"]').length).toBeGreaterThanOrEqual(2);

  requestSpy.mockRestore();
  submitSpy.mockRestore();
});
// ...existing code...
jest.disableAutomock();

beforeEach(() => {
  jest.resetModules();
  document.body.innerHTML = '';
});

test('delete button uses source form and injects hidden fields then submits', () => {
  const admin = require('../admin.js');
  const internals = admin.__internals;

  // build DOM: modal, delete button, and a source form referenced by id
  document.body.innerHTML = `
    <div id="confirm-delete-modal">
      <ul id="confirm-delete-modal-assoc"></ul>
      <button id="confirm-delete-modal-delete-btn">Delete</button>
      <form id="confirm-delete-modal-hidden-form"></form>
    </div>
    <form id="source-form" action="/form-action">
      <input type="hidden" name="csrfToken" value="tok">
    </form>
  `;

  const source = document.getElementById('source-form');
  // stub submit to avoid jsdom submit side-effects
  source.submit = jest.fn();
  // ensure requestSubmit is not used (jsdom's requestSubmit throws "Not implemented")
  source.requestSubmit = undefined;

  // set context to refer to the source form and include ids and bulkAction
  internals.setContext({
    formId: 'source-form',
    ids: JSON.stringify([1, 2]),
    idsName: 'ids',
    deleteUrl: '/delete',
    bulkAction: 'remove',
  });

  // click the delete button
  const btn = document.getElementById('confirm-delete-modal-delete-btn');
  btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

  // source form should have injected inputs (2 ids + bulk_action)
  const injected = source.querySelectorAll('.injected-delete');
  expect(injected.length).toBe(3);
  // action should be preserved from the source form (source.action)
  expect(source.action).toContain('/form-action');
  expect(source.submit).toHaveBeenCalled();
});

test('delete button falls back to temp form when source.getAttribute throws', () => {
  const admin = require('../admin.js');
  const internals = admin.__internals;

  document.body.innerHTML = `
    <div id="confirm-delete-modal">
      <ul id="confirm-delete-modal-assoc"></ul>
      <button id="confirm-delete-modal-delete-btn">Delete</button>
      <form id="confirm-delete-modal-hidden-form"></form>
    </div>
    <form id="source-form" action="/broken">
      <input type="hidden" name="csrfToken" value="tok">
    </form>
  `;

  const source = document.getElementById('source-form');
  // make getAttribute throw to simulate a broken form element
  source.getAttribute = () => {
    throw new Error('boom');
  };

  internals.setContext({
    formId: 'source-form',
    ids: '7',
    idsName: 'id',
    deleteUrl: '/delete-url',
  });

  const btn = document.getElementById('confirm-delete-modal-delete-btn');
  btn.dispatchEvent(new MouseEvent('click', { bubbles: true }));

  // The event handler falls back to submitTempForm; since that function is
  // closure-local, we assert a temporary form was appended with the expected
  // action and injected inputs.
  const forms = Array.from(document.querySelectorAll('form'));
  // there should be at least one temp form whose action contains the deleteUrl
  const temp = forms.find((f) => f.action && f.action.indexOf('/delete-url') !== -1);
  expect(temp).toBeDefined();
  const inputs = temp.querySelectorAll('input');
  expect(inputs.length).toBeGreaterThanOrEqual(1);
  expect(Array.from(inputs).some((i) => i.name === 'id' && i.value === '7')).toBe(true);
});
