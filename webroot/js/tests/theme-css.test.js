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
});
