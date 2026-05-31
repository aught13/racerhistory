/** @jest-environment node */
import { describe, test, expect } from "@jest/globals";
import fs from "fs";

const cssPath = "./webroot/css/frontend.css";

describe("Seasons index CSS rules", () => {
    test("seasons table card styles exist", () => {
        const exists = fs.existsSync(cssPath);
        expect(exists).toBe(true);
        const css = fs.readFileSync(cssPath, "utf8");

        expect(css).toContain(".seasons-table-card {");
        expect(css).toContain(".seasons-table-card thead th {");
        expect(css).toContain(".seasons-table-card tbody tr:nth-child(even) {");
    });
});
