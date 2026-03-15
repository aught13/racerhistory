beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
describe("person-image extra coverage", () => {
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = "";
    });

    test("uploadFile rejects on fetch network error", async () => {
        const { uploadFile } = await import("../person-image.js");
        global.fetch = jest.fn(() => Promise.reject(new Error("network fail")));
        const blob = new Blob(["x"], { type: "image/png" });
        await expect(uploadFile(new File([blob], "bad.png"))).rejects.toThrow(
            "network fail",
        );
    });

    test("setPreviewFromId respects variant and shows parent container", async () => {
        const { setPreviewFromId } = await import("../person-image.js");
        document.body.innerHTML =
            '<div id="preview"><img id="pimg" src=""/></div>';
        const img = document.getElementById("pimg");
        setPreviewFromId(77, img, "thumb");
        expect(img.src).toContain("/images/serve/77");
        expect(img.src).toContain("variant=thumb");
        expect(img.parentElement.style.display).toBe("block");
    });
});
