/** @jest-environment jsdom */

import { jest } from "@jest/globals";
// Tests for person-image module

beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});

describe("person-image module", () => {
    let personImage;

    beforeEach(async () => {
        // Ensure no global fetch leftover; individual tests set their own fetch mock when needed
        document.body.innerHTML = "";
        jest.resetModules();
        const module = await import("../person-image.js");
        personImage = module;
    });

    test("setPreviewFromId sets image src and shows container", () => {
        document.body.innerHTML =
            '<div id="preview"><img id="pimg" src=""/></div>';
        const img = document.getElementById("pimg");
        personImage.setPreviewFromId(
            "/img/storage/2026/05/profile-thumb.webp",
            img,
        );
        expect(img.src).toContain("/img/storage/2026/05/profile-thumb.webp");
        expect(img.parentElement.style.display).toBe("block");
    });

    test("uploadFile parses JSON response", async () => {
        global.fetch = jest.fn(() =>
            Promise.resolve({
                text: () =>
                    Promise.resolve(
                        JSON.stringify({
                            success: true,
                            image: {
                                id: 10,
                                url: "/img/storage/2026/05/10.jpg",
                            },
                        }),
                    ),
            }),
        );
        const blob = new Blob(["x"], { type: "image/png" });
        const res = await personImage.uploadFile(new File([blob], "f.png"));
        expect(res.success).toBe(true);
        expect(res.image.id).toBe(10);
    });
});
