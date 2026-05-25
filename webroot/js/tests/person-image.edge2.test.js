/** @jest-environment jsdom */

import { jest } from "@jest/globals";
let PI;
describe("person-image edge behaviors", () => {
    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../person-image.js");
        PI = mod.default || mod;
        document.body.innerHTML = `
            <button id="select-btn">Select</button>
            <input id="image-field" />
            <div id="preview"><img /></div>
        `;
    });

    test("setPreviewFromId sets src and makes parent visible", () => {
        const img = document.querySelector("#preview img");
        PI.setPreviewFromId("/img/storage/abc123-thumb.webp", img);
        expect(img.src).toContain("/img/storage/abc123-thumb.webp");
        expect(img.parentElement.style.display).toBe("block");
    });

    test("uploadFile throws on invalid JSON response", async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({ text: () => Promise.resolve("not-json") }),
        );
        const file = new Blob(["x"], { type: "text/plain" });
        file.name = "x.txt";
        await expect(PI.uploadFile(file, "/fake")).rejects.toThrow(
            /Invalid JSON response/,
        );
    });
});
