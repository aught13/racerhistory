// Robust, side-effect-free mocks for jsdom form submission
if (typeof HTMLFormElement !== 'undefined') {
    if (!HTMLFormElement.prototype.submit) {
        Object.defineProperty(HTMLFormElement.prototype, 'submit', {
            value: function () {
                // no-op for jsdom
            },
            configurable: true,
            writable: true
        });
    }
    if (!HTMLFormElement.prototype.requestSubmit) {
        Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', {
            value: function () {
                // no-op for jsdom; do not dispatch events or call submit
            },
            configurable: true,
            writable: true
        });
    }
}
