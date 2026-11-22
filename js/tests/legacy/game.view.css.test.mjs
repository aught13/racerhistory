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
            /\.season-stats-tabs \.season-stats-tabs__nav \.nav-link,\s*\.game-box-score-tabs \.game-box-score-tabs__nav \.nav-link,\s*\.person-game-log-tabs \.person-game-log-tabs__nav \.nav-link\s*\{[^}]*color:\s*var\(--rh-muted\)/i,
        );
        expect(css).toMatch(
            /\.season-stats-tabs \.season-stats-tabs__nav \.nav-link\.active,\s*\.game-box-score-tabs \.game-box-score-tabs__nav \.nav-link\.active,\s*\.person-game-log-tabs \.person-game-log-tabs__nav \.nav-link\.active\s*\{[^}]*color:\s*var\(--rh-text\)[^}]*background-color:\s*var\(--rh-surface\)/i,
        );
    });

    test("box score leaders use a visible theme-aware highlight", () => {
        const leaderStyles =
            css.match(/\.box-score-stat-leader\s*\{([^}]*)\}/i)?.[1] ?? "";

        expect(leaderStyles).toMatch(
            /background-color:\s*color-mix\([^;]*var\(--rh-gold\)[^;]*var\(--rh-surface\)/i,
        );
        expect(leaderStyles).toMatch(/color:\s*var\(--rh-text\)/i);
        expect(leaderStyles).toMatch(/font-weight:\s*700/i);
    });
});
