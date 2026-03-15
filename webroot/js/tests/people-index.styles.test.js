import fs from "fs";
import path from "path";
// Note: __dirname computed relative to workspace root for Jest
const __dirname = path.join(process.cwd(), "webroot", "js", "tests");

describe("People index styles", () => {
    test("frontend.css includes people index selectors", () => {
        const cssPath = path.resolve(__dirname, "../../css/frontend.css");
        const css = fs.readFileSync(cssPath, "utf8");

        expect(css).toContain(".people-table-card");
        expect(css).toContain(".people-searchbar");
        expect(css).toContain(".people-display-name");
    });
});
