const fs = require("fs");
const path = require("path");

describe("frontend.css variables", () => {
    const cssPath = path.resolve(__dirname, "../../css/frontend.css");
    test("contains main-bg gutter color for light mode", () => {
        const css = fs.readFileSync(cssPath, "utf8");
        expect(css).toMatch(/--rh-main-bg:\s*#444444/);
    });

    test("contains prefers-color-scheme dark rule", () => {
        const css = fs.readFileSync(cssPath, "utf8");
        expect(css).toMatch(/@media \(prefers-color-scheme: dark\)/);
    });
});
