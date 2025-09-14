/* eslint-env jest, browser */
if (!HTMLFormElement.prototype.requestSubmit) {
    Object.defineProperty(HTMLFormElement.prototype, 'requestSubmit', {
        value: jest.fn(function () {
            console.log('Mock requestSubmit called');
            if (this.submit) {
                this.submit();
            }
        }),
        configurable: true,
        writable: true
    });
    console.log('Mock for requestSubmit applied');
}

console.log('jest.setup.js loaded');
