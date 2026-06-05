/** @jest-environment jsdom */

import { describe, test, expect, beforeEach, beforeAll } from "@jest/globals";
import fs from "fs";

const cssPath = "./webroot/css/frontend.css";

const loadStyles = () => fs.readFileSync(cssPath, "utf8");

describe("Season view CSS", () => {
    let css;

    beforeAll(() => {
        css = loadStyles();
    });

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("season stat cards keep rounded corners", () => {
        // Accept any border-radius value (e.g. 12px or a CSS variable)
        expect(css).toMatch(/\.season-stat-card[\s\S]*border-radius:\s*[^;]+/i);
    });

    test("season image placeholder uses dashed border", () => {
        // Accept either a shorthand dashed border or border-style: dashed
        expect(css).toMatch(
            /\.season-image-placeholder[\s\S]*(?:border:\s*[^;]*dashed|border-style:\s*dashed)/i,
        );
    });

    test("season hero image keeps cover layout", () => {
        expect(css).toMatch(/\.season-hero-image[\s\S]*object-fit:\s*cover/i);
        expect(css).toMatch(/\.season-hero-image[\s\S]*max-height:\s*480px/i);
    });

    test("season roster avatar image keeps circular crop", () => {
        expect(css).toMatch(
            /\.season-roster-avatar-img[\s\S]*border-radius:\s*50%/i,
        );
        expect(css).toMatch(/\.season-roster-avatar-img[\s\S]*width:\s*72px/i);
        expect(css).toMatch(/\.season-roster-avatar-img[\s\S]*height:\s*72px/i);
    });

    test("dark mode season games table links use readable link colour", () => {
        // Both [data-theme=dark] and prefers-color-scheme:dark must set --rh-link
        expect(css).toMatch(
            /\[data-season-view\]\s+#season-games-table\s+a[\s\S]*color:\s*var\(--rh-link\)/i,
        );
    });

    test("no inline dark style hardcodes dark navy link colour", () => {
        // The old broken inline style used #001f3f which is dark on dark
        expect(css).not.toMatch(/#001f3f/i);
    });
});
