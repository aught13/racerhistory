/** @jest-environment jsdom */

describe('person-image extra coverage', () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = '';
    });

    test('uploadFile rejects on fetch network error', async () => {
        const personImage = require('../person-image.js');
        global.fetch = jest.fn(() => Promise.reject(new Error('network fail')));
        const blob = new Blob(['x'], { type: 'image/png' });
        await expect(personImage.uploadFile(new File([blob], 'bad.png'))).rejects.toThrow(
            'network fail'
        );
    });

    test('setPreviewFromId respects variant and shows parent container', () => {
        const personImage = require('../person-image.js');
        document.body.innerHTML = '<div id="preview"><img id="pimg" src=""/></div>';
        const img = document.getElementById('pimg');
        personImage.setPreviewFromId(77, img, 'thumb');
        expect(img.src).toContain('/images/serve/77');
        expect(img.src).toContain('variant=thumb');
        expect(img.parentElement.style.display).toBe('block');
    });
});
