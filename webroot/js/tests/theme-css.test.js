import fs from "fs";
import path from "path";
// Note: __dirname computed relative to workspace root for Jest
const __dirname = path.join(process.cwd(), "webroot", "js", "tests");

describe("frontend.css variables", () => {
    const cssPath = path.resolve(__dirname, "../../css/frontend.css");
    let css;

    beforeAll(() => {
        css = fs.readFileSync(cssPath, "utf8");
    });

    test("contains main-bg gutter color for light mode", () => {
        expect(css).toMatch(/--rh-main-bg:\s*#444444/);
    });

    test("contains prefers-color-scheme dark rule", () => {
        expect(css).toMatch(/@media \(prefers-color-scheme: dark\)/);
    });

    test("system dark query includes --rh-main-bg", () => {
        expect(css).toMatch(
            /@media \(prefers-color-scheme: dark\)[\s\S]*?:root:not\(\[data-theme\]\)[\s\S]*?--rh-main-bg:/,
        );
    });

    test("system dark query includes --rh-content-bg", () => {
        expect(css).toMatch(
            /@media \(prefers-color-scheme: dark\)[\s\S]*?:root:not\(\[data-theme\]\)[\s\S]*?--rh-content-bg:/,
        );
    });

    test("explicit dark theme includes --rh-content-bg", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\][\s\S]*?--rh-content-bg:\s*#0f1a2e/,
        );
    });

    test("footer uses CSS variable for background", () => {
        expect(css).toMatch(
            /\.rh-footer[\s\S]*?background:\s*var\(--rh-surface\)/,
        );
    });
});

describe("dark mode component overrides", () => {
    const cssPath = path.resolve(__dirname, "../../css/frontend.css");
    let css;

    beforeAll(() => {
        css = fs.readFileSync(cssPath, "utf8");
    });

    test("overrides .text-muted in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.text-muted[\s\S]*?color:\s*var\(--rh-muted\)/,
        );
    });

    test("overrides .text-dark in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.text-dark[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("overrides .bg-light in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.bg-light[\s\S]*?background-color:/,
        );
    });

    test("overrides .bg-white in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.bg-white[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("overrides .card in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.card[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("overrides .card-header in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.card-header[\s\S]*?border-color:\s*var\(--rh-border\)/,
        );
    });

    test("overrides .form-control in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.form-control[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("overrides .form-select in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.form-select[\s\S]*?border-color:\s*var\(--rh-border\)/,
        );
    });

    test("overrides .list-group-item in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.list-group-item[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("overrides .badge.bg-light in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.badge\.bg-light[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("overrides alerts in dark mode", () => {
        expect(css).toMatch(/\[data-theme="dark"\]\s*\.alert-info/);
        expect(css).toMatch(/\[data-theme="dark"\]\s*\.alert-success/);
        expect(css).toMatch(/\[data-theme="dark"\]\s*\.alert-warning/);
        expect(css).toMatch(/\[data-theme="dark"\]\s*\.alert-danger/);
    });

    test("overrides breadcrumbs in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.breadcrumb-item\s+a[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("overrides .dropdown-menu in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dropdown-menu[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("overrides .page-link in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.page-link[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("overrides .table in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.table[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("overrides .btn-outline-secondary in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.btn-outline-secondary[\s\S]*?border-color:\s*var\(--rh-border\)/,
        );
    });

    test("contains game type badge classes", () => {
        expect(css).toMatch(
            /\.rh-badge-post[\s\S]*?background-color:\s*var\(--rh-navy\)/,
        );
        expect(css).toMatch(
            /\.rh-badge-conf-post[\s\S]*?background-color:\s*var\(--rh-gold\)/,
        );
    });

    test("people-table-card tbody tr has background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.people-table-card\s+tbody\s+tr[\s\S]*?background:\s*rgba\(255,\s*255,\s*255,\s*0\.02\)/,
        );
    });

    test("people-table-card tbody tr.even has striped background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.people-table-card\s+tbody\s+tr\.even[\s\S]*?background:\s*rgba\(255,\s*255,\s*255,\s*0\.05\)/,
        );
    });

    test("people-searchbar form-control has text color in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.people-searchbar\s+\.form-control[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("game-type-card has dark mode background", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.game-type-card[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("stat-type-card has dark mode background", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.stat-type-card[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("game-type-card text-primary uses gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.game-type-card\s+\.text-primary[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("stat-type-card text-primary uses gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.stat-type-card\s+\.text-primary[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("game-type-card hover uses lighter surface in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.game-type-card:hover[\s\S]*?background-color:\s*rgba\(255,\s*255,\s*255,\s*0\.06\)/,
        );
    });

    test("stat-type-card hover uses lighter surface in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.stat-type-card:hover[\s\S]*?background-color:\s*rgba\(255,\s*255,\s*255,\s*0\.06\)/,
        );
    });

    test("DataTables info text uses muted color in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.dataTables_info[\s\S]*?color:\s*var\(--rh-muted\)/,
        );
    });

    test("DataTables filter label uses text color in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.dataTables_filter\s+label[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("DataTables filter input has dark surface in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.dataTables_filter\s+input[\s\S]*?background-color:\s*var\(--rh-surface\)/,
        );
    });

    test("DataTables paginate button uses gold for current page in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.dataTables_paginate\s+\.paginate_button\.current[\s\S]*?background:\s*var\(--rh-link\)/,
        );
    });

    test("DataTables table element has transparent background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable[\s\S]*?background-color:\s*transparent\s*!important/,
        );
    });

    test("DataTables table cells use light text in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable\s*>\s*:not\(caption\)\s*>\s*\*\s*>\s*\*[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("DataTables table cells have transparent background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable\s*>\s*:not\(caption\)\s*>\s*\*\s*>\s*\*[\s\S]*?background-color:\s*transparent/,
        );
    });

    test("DataTables striped rows use subtle background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable\.table-striped\s*>\s*tbody\s*>\s*tr:nth-of-type\(odd\)\s*>\s*\*[\s\S]*?background-color:\s*rgba\(255,\s*255,\s*255,\s*0\.03\)/,
        );
    });

    test("DataTables hover rows highlight in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable\.table-hover\s*>\s*tbody\s*>\s*tr:hover\s*>\s*\*[\s\S]*?background-color:\s*rgba\(255,\s*255,\s*255,\s*0\.07\)/,
        );
    });

    test("DataTables table links use gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_wrapper\s+\.table\.dataTable\s*>\s*:not\(caption\)\s*>\s*\*\s*>\s*\*\s+a[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("DataTables scroll body is transparent in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.dataTables_scrollBody[\s\S]*?background-color:\s*transparent/,
        );
    });

    test("stats-results-table links use gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*#stats-results-table\s+a[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("games-results-table links use gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*#games-results-table\s+a[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("games-record-display uses text color in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*#games-record-display[\s\S]*?color:\s*var\(--rh-text\)/,
        );
    });

    test("btn-outline-primary uses gold in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.btn-outline-primary[\s\S]*?color:\s*var\(--rh-link\)/,
        );
    });

    test("btn-primary uses gold background in dark mode", () => {
        expect(css).toMatch(
            /\[data-theme="dark"\]\s*\.btn-primary[\s\S]*?background-color:\s*var\(--rh-link\)/,
        );
    });
});
