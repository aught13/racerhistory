/** @jest-environment jsdom */

import { describe, test, expect, beforeAll } from "@jest/globals";
import fs from "fs";

const cssPath = "./webroot/css/frontend.css";

const loadStyles = () => fs.readFileSync(cssPath, "utf8");

describe("Stats view CSS", () => {
    let css;

    beforeAll(() => {
        css = loadStyles();
    });

    test("stats navbar keeps a constrained container layout", () => {
        expect(css).toMatch(
            /\.rh-stats-navbar\s+\.navbar-container[\s\S]*max-width:\s*var\(--rh-main-max\)/i,
        );
    });

    test("active stats nav link has explicit active styling", () => {
        expect(css).toMatch(
            /\.rh-stats-navbar\s+\.nav-link\.active[\s\S]*background/i,
        );
    });

    test("light theme defines stats nav link colors", () => {
        expect(css).toMatch(
            /\[data-theme="light"\]\s+\.rh-stats-navbar\s+\.nav-link[\s\S]*color:\s*[^;]+/i,
        );
    });

    test("dark theme defines stats nav link colors", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s+\.rh-stats-navbar\s+\.nav-link[\s\S]*color:\s*[^;]+/i,
        );
    });
});
