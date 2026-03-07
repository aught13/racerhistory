/**
 * DOM style assertions for critical CSS rules
 * @jest-environment jsdom
 */

import fs from "fs";
import path from "path";
// Note: __dirname computed relative to workspace root for Jest
const __dirname = path.join(process.cwd(), "webroot", "js", "tests");

const cssPath = path.resolve(__dirname, "../../css/frontend.css");

const loadStyles = () => fs.readFileSync(cssPath, "utf8");

describe("CSS regressions", () => {
    let css;

    beforeAll(() => {
        css = loadStyles();
    });

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("navbar uses brand background and shadow", () => {
        expect(css).toMatch(/\.rh-navbar[\s\S]*background:\s*#ECAC00/i);
        expect(css).toMatch(/\.rh-navbar[\s\S]*box-shadow:/i);
    });

    test("logo link keeps fixed width for layout", () => {
        expect(css).toMatch(/\.rh-logo-link[\s\S]*display:\s*flex/i);
        expect(css).toMatch(/\.rh-logo-link[\s\S]*width:\s*140px/i);
        expect(css).toMatch(/\.rh-logo-link[\s\S]*max-width:\s*140px/i);
    });
});
