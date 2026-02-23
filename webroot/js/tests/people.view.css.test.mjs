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

    test("people bio text has a readable line height", () => {
        expect(css).toMatch(
            /\.person-view\s+\.person-bio[\s\S]*line-height:\s*[^;]+/i,
        );
    });

    test("roster list items use theme borders", () => {
        expect(css).toMatch(
            /\.person-view\s+\.list-group-item[\s\S]*border-color:\s*[^;]+/i,
        );
    });
});
