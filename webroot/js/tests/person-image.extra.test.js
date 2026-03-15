beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
/** @jest-environment jsdom */

import { jest } from "@jest/globals";
let personImage;
describe("person-image extra coverage", () => {
    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../person-image.js");
        personImage = mod.default || mod;

        document.body.innerHTML = "";
        jest.resetAllMocks();
    });

    test("uploadFile invalid JSON throws", async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({ text: () => Promise.resolve("not json") }),
        );
        const blob = new Blob(["x"], { type: "image/png" });
        await expect(
            personImage.uploadFile(new File([blob], "bad.png")),
        ).rejects.toThrow("Invalid JSON response");
    });

    test("setPreviewFromId early return with missing element", () => {
        // Should not throw
        personImage.setPreviewFromId(10, null);
    });

    test("initPersonImageSelector wires button and updates preview", async () => {
        document.body.innerHTML = `
          <input id="img-field" value="" />
          <div id="preview"><img id="pvimg" src="" /></div>
          <button id="select-btn">Select</button>
        `;
        // Mock fetch for uploadFile inside init
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () =>
                    Promise.resolve(
                        JSON.stringify({
                            success: true,
                            image: { id: 77, url: "/images/serve/77" },
                        }),
                    ),
            }),
        );
        // Monkey patch window.File / Blob if needed (jsdom provides basic)
        const fileBlob = new Blob(["data"], { type: "image/png" });
        // Simulate selection: intercept created input
        const clickSpies = [];
        const origCreate = document.createElement.bind(document);
        document.createElement = function (tag) {
            const el = origCreate(tag);
            if (tag === "input") {
                // override click to directly trigger onchange with a fake file list
                el.click = function () {
                    Object.defineProperty(el, "files", {
                        value: [
                            new File([fileBlob], "a.png", {
                                type: "image/png",
                            }),
                        ],
                        configurable: true,
                    });
                    setTimeout(() => el.onchange && el.onchange());
                };
                clickSpies.push(el);
            }
            return el;
        };
        personImage.initPersonImageSelector({
            selectBtnId: "select-btn",
            fieldId: "img-field",
            previewId: "preview",
            uploadUrl: "/admin/images/upload",
        });
        document.getElementById("select-btn").click();
        await new Promise((r) => setTimeout(r, 0));
        const field = document.getElementById("img-field");
        expect(field.value).toBe("77");
        const img = document.getElementById("pvimg");
        expect(img.src).toContain("/images/serve/77");
        document.createElement = origCreate; // restore
    });
});
