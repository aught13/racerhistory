/** @jest-environment jsdom */

import { describe, test, expect, beforeAll } from "@jest/globals";
import fs from "fs";

const cssPath = "./webroot/css/frontend.css";

const loadStyles = () => fs.readFileSync(cssPath, "utf8");

describe("Game view CSS", () => {
    let css;

    beforeAll(() => {
        css = loadStyles();
    });

    test("game score badge has rounded corners", () => {
        expect(css).toMatch(/\.game-score-badge[\s\S]*border-radius:\s*[^;]+/i);
    });

    test("game photos grid uses CSS grid", () => {
        expect(css).toMatch(/\.game-photos-grid[\s\S]*grid-template-columns:/i);
    });

    test("dark mode overrides Bootstrap card background variable", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\][\s\S]*--bs-card-bg:\s*var\(--rh-surface\)/i,
        );
    });

    test("dark mode overrides Bootstrap table colour variable", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\][\s\S]*--bs-table-color:\s*var\(--rh-text\)/i,
        );
    });

    test("game box score tabs use readable theme-aware button styles", () => {
        expect(css).toMatch(
            /\.game-box-score-tabs \.game-box-score-tabs__nav \.nav-link\s*\{[^}]*color:\s*var\(--rh-muted\)/i,
        );
        expect(css).toMatch(
            /\.game-box-score-tabs \.game-box-score-tabs__nav \.nav-link\.active\s*\{[^}]*color:\s*var\(--rh-text\)[^}]*background-color:\s*var\(--rh-surface\)/i,
        );
    });
});
