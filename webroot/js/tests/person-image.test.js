/** @jest-environment jsdom */
const fs = require('fs');
const path = require('path');
const personImage = require('../person-image.js');

describe('person-image module', () => {
    beforeEach(() => {
        // Ensure no global fetch leftover; individual tests set their own fetch mock when needed
        document.body.innerHTML = '';
    });

    test('setPreviewFromId sets image src and shows container', () => {
        document.body.innerHTML = '<div id="preview"><img id="pimg" src=""/></div>';
        const img = document.getElementById('pimg');
        personImage.setPreviewFromId(5, img, 'thumb');
        expect(img.src).toContain('/images/serve/5');
        expect(img.src).toContain('variant=thumb');
        expect(img.parentElement.style.display).toBe('block');
    });

    test('uploadFile parses JSON response', async () => {
        global.fetch = jest.fn(() => Promise.resolve({
            text: () => Promise.resolve(JSON.stringify({ success: true, image: { id: 10, url: '/images/serve/10' } })),
        }));
        const blob = new Blob(['x'], { type: 'image/png' });
        const res = await personImage.uploadFile(new File([blob], 'f.png'));
        expect(res.success).toBe(true);
        expect(res.image.id).toBe(10);
    });
});
