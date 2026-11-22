/** @jest-environment jsdom */

import { describe, test, expect, beforeAll } from "@jest/globals";
import fs from "fs";

const cssPath = "./webroot/css/frontend.css";

const loadStyles = () => fs.readFileSync(cssPath, "utf8");

describe("People view CSS", () => {
    let css;

    beforeAll(() => {
        css = loadStyles();
    });

    test("person biography styles are no longer present", () => {
        expect(css).not.toMatch(/\.person-view\s+\.person-bio/i);
    });

    test("roster list items use theme borders", () => {
        expect(css).toMatch(
            /\.person-view\s+\.list-group-item[\s\S]*border-color:\s*[^;]+/i,
        );
    });

    test("person game-log tabs share the readable stats-tab colors", () => {
        expect(css).toMatch(
            /\.person-game-log-tabs \.person-game-log-tabs__nav \.nav-link\s*\{[^}]*color:\s*var\(--rh-muted\)/i,
        );
        expect(css).toMatch(
            /\.person-game-log-tabs \.person-game-log-tabs__nav \.nav-link\.active\s*\{[^}]*color:\s*var\(--rh-text\)[^}]*background-color:\s*var\(--rh-surface\)/i,
        );
        expect(css).toMatch(
            /\.person-game-log-tabs \.person-game-log-tabs__nav \.nav-link:focus-visible\s*\{[^}]*outline:\s*2px solid var\(--rh-link\)/i,
        );
    });
});
