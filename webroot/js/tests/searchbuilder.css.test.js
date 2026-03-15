/** @jest-environment node */
import fs from "fs";
import path from "path";
// Note: __dirname computed relative to workspace root for Jest
const __dirname = path.join(process.cwd(), "webroot", "js", "tests");
describe("SearchBuilder CSS rules", () => {
    test("dark-mode SearchBuilder button color rule exists", () => {
        const cssPath = path.resolve(
            __dirname,
            "..",
            "..",
            "css",
            "frontend.css",
        );
        const exists = fs.existsSync(cssPath);
        expect(exists).toBe(true);
        const css = fs.readFileSync(cssPath, "utf8");

        // Look for the dark-mode rule we added
        const selectorA =
            ':root[data-theme="dark"] div.dtsb-searchBuilder button.dtsb-button';
        const selectorB =
            ':root[data-theme="dark"] .dtsb-searchBuilder button.dtsb-button';
        expect(css.includes(selectorA) || css.includes(selectorB)).toBe(true);
    });
});
