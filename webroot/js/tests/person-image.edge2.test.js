/** @jest-environment jsdom */
const PI = require('../../js/person-image.js');

describe('person-image edge behaviors', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <button id="select-btn">Select</button>
            <input id="image-field" />
            <div id="preview"><img /></div>
        `;
    });

    test('setPreviewFromId sets src and makes parent visible', () => {
        const img = document.querySelector('#preview img');
        PI.setPreviewFromId('abc123', img, 'thumb');
        expect(img.src).toContain('/images/serve/abc123');
        expect(img.parentElement.style.display).toBe('block');
    });

    test('uploadFile throws on invalid JSON response', async () => {
        global.fetch = jest.fn(() => Promise.resolve({ text: () => Promise.resolve('not-json') }));
        const file = new Blob(['x'], { type: 'text/plain' });
        file.name = 'x.txt';
        await expect(PI.uploadFile(file, '/fake')).rejects.toThrow(/Invalid JSON response/);
    });
});
