beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
// person-image.edge.test.js - Tests for person-image.js preview/upload with bad/missing data

let personImage;
describe("person-image.js edge cases", () => {
    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../../legacy/person-image.js");
        personImage = mod.default || mod;

        document.body.innerHTML = "";
        jest.resetAllMocks();
        delete global.fetch;
    });

    test("setPreviewFromId with missing img element does not throw", () => {
        expect(() => personImage.setPreviewFromId(123, null)).not.toThrow();
    });

    test("setPreviewFromId with invalid imageId does not throw", () => {
        document.body.innerHTML = '<img id="pimg" src="" />';
        const img = document.getElementById("pimg");
        expect(() => personImage.setPreviewFromId(null, img)).not.toThrow();
    });

    test("initPersonImageSelector with missing elements does not throw", () => {
        document.body.innerHTML = "<div></div>";
        expect(() =>
            personImage.initPersonImageSelector({
                selectBtnId: "x",
                fieldId: "y",
                previewId: "z",
            }),
        ).not.toThrow();
    });

    test("uploadFile with network error rejects", async () => {
        global.fetch = jest.fn(() => Promise.reject(new Error("fail")));
        const blob = new Blob(["x"], { type: "image/png" });
        await expect(
            personImage.uploadFile(new File([blob], "bad.png")),
        ).rejects.toThrow("fail");
    });

    test("uploadFile with invalid server response rejects", async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () => Promise.resolve("<html>fail</html>"),
            }),
        );
        const blob = new Blob(["x"], { type: "image/png" });
        await expect(
            personImage.uploadFile(new File([blob], "bad.png")),
        ).rejects.toThrow(/Invalid JSON response/i);
    });
});
