/* eslint-env jest */
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
        expect(css).toMatch(/\.season-stat-card[\s\S]*border-radius:\s*12px/i);
    });

    test("season image placeholder uses dashed border", () => {
        expect(css).toMatch(
            /\.season-image-placeholder[\s\S]*border:\s*1px\s+dashed/i,
        );
    });
});
