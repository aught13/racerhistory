/**
 * DOM style assertions for critical CSS rules
 * @jest-environment jsdom
 */

import fs from "fs";
import path from "path";
// Note: __dirname computed relative to workspace root for Jest
const __dirname = path.join(process.cwd(), "webroot", "js", "tests");

const cssPath = path.resolve(__dirname, "../../../webroot/css/frontend.css");

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

    test("public scope bar is white below the gold navbar", () => {
        expect(css).toMatch(
            /\.rh-public-scope-bar[\s\S]*background:\s*#ffffff/i,
        );
        expect(css).toMatch(
            /\.rh-games-team-selection-note[\s\S]*background:\s*#e9ecef/i,
        );
        expect(css).toMatch(
            /\.rh-stats-selection-note[\s\S]*background:\s*#e9ecef/i,
        );
    });

    test("public scope subnav spans the viewport with a More dropdown", () => {
        expect(css).toMatch(
            /\.rh-public-scope-bar-inner[\s\S]*width:\s*100%[\s\S]*max-width:\s*var\(--rh-main-max\)[\s\S]*margin:\s*0 auto[\s\S]*padding:\s*0\.5rem 1rem/i,
        );
        expect(css).toMatch(
            /\.rh-public-scope-menu[\s\S]*inset:\s*100% auto auto 0/i,
        );
        expect(css).toMatch(
            /\.rh-public-scope-menu\.show[\s\S]*grid-template-columns:/i,
        );
        expect(css).toMatch(
            /\.rh-public-scope-current[\s\S]*font-family:\s*inherit[\s\S]*font-size:\s*1rem[\s\S]*font-weight:\s*500/i,
        );
        expect(css).toMatch(
            /\.rh-public-scope-menu[\s\S]*background:\s*#ffffff/i,
        );
        expect(css).toMatch(
            /\.rh-public-scope-menu[\s\S]*padding:\s*0\.5rem max\(1rem, calc\(\(100vw - var\(--rh-main-max\)\) \/ 2 \+ 1rem\)\)/i,
        );
        expect(css).toMatch(
            /:root\[data-theme="dark"\] \.rh-games-team-selection-note[\s\S]*background:\s*var\(--rh-surface\)/i,
        );
    });

    test("logo link keeps fixed width for layout", () => {
        expect(css).toMatch(/\.rh-logo-link[\s\S]*display:\s*flex/i);
        expect(css).toMatch(/\.rh-logo-link[\s\S]*width:\s*140px/i);
        expect(css).toMatch(/\.rh-logo-link[\s\S]*max-width:\s*140px/i);
    });
});
