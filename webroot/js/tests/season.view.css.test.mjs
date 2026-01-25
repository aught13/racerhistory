/** @jest-environment jsdom */

import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const cssPath = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "../../css/frontend.css",
);

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
});
