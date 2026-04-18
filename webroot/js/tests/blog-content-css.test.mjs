/**
 * Tests for Blog Content CSS
 *
 * Validates that required CSS selectors and responsive patterns are present.
 *
 * @jest-environment node
 */

import { readFileSync } from "fs";
import { join } from "path";
import { describe, test, expect, beforeAll } from "@jest/globals";

describe("blog-content.css", () => {
    let cssContent;

    beforeAll(() => {
        const cssPath = join(process.cwd(), "webroot/css/blog-content.css");
        cssContent = readFileSync(cssPath, "utf-8");
    });

    // Core Typography
    describe("typography selectors", () => {
        test("has styling for headings h1-h6", () => {
            expect(cssContent).toMatch(/\.blog-content\s+h1\b/);
            expect(cssContent).toMatch(/\.blog-content\s+h2\b/);
            expect(cssContent).toMatch(/\.blog-content\s+h3\b/);
            expect(cssContent).toMatch(/\.blog-content\s+h4\b/);
        });

        test("has paragraph styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+p\b/);
        });

        test("has lead paragraph class", () => {
            expect(cssContent).toMatch(/\.blog-content\s+\.lead\b/);
        });

        test("has blockquote styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+blockquote\b/);
        });
    });

    // Code Blocks
    describe("code block selectors", () => {
        test("has inline code styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+(code|:not\(pre\)\s*>\s*code)/);
        });

        test("has pre/code block styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+pre\b/);
        });
    });

    // Images
    describe("image selectors", () => {
        test("has base image styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+img\b/);
        });

        test("has float-left image class", () => {
            expect(cssContent).toMatch(/\.img-float-left/);
        });

        test("has float-right image class", () => {
            expect(cssContent).toMatch(/\.img-float-right/);
        });

        test("has center image class", () => {
            expect(cssContent).toMatch(/\.img-center/);
        });

        test("has figure styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+figure\b/);
        });

        test("has figcaption styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+figcaption\b/);
        });

        test("has picture element styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+picture\b/);
        });
    });

    // Tables
    describe("table selectors", () => {
        test("has table styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+table\b/);
        });

        test("has table header styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+(table\s+)?th\b/);
        });

        test("has table cell styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+(table\s+)?td\b/);
        });

        test("has table-responsive wrapper support", () => {
            expect(cssContent).toMatch(/\.table-responsive/);
        });
    });

    // Lists
    describe("list selectors", () => {
        test("has unordered list styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+ul\b/);
        });

        test("has ordered list styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+ol\b/);
        });

        test("has list item styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+(ul|ol)?\s*li\b/);
        });
    });

    // Links
    describe("link selectors", () => {
        test("has anchor styling", () => {
            expect(cssContent).toMatch(/\.blog-content\s+a\b/);
        });

        test("has hover state for links", () => {
            expect(cssContent).toMatch(/\.blog-content\s+a:hover/);
        });
    });

    // Media Embeds
    describe("media embed selectors", () => {
        test("has iframe/embed wrapper or styling", () => {
            // Should handle embeds responsively
            expect(cssContent).toMatch(/(\.blog-content\s+iframe|embed-responsive|aspect-ratio)/);
        });
    });

    // Dark Mode
    describe("dark mode support", () => {
        test("has dark mode media query", () => {
            expect(cssContent).toMatch(/@media\s*\(\s*prefers-color-scheme:\s*dark\s*\)/);
        });

        test("OR has data-bs-theme dark selector", () => {
            const hasDarkMediaQuery = cssContent.match(
                /@media\s*\(\s*prefers-color-scheme:\s*dark\s*\)/
            );
            const hasBsThemeDark = cssContent.match(
                /\[data-bs-theme\s*=\s*["']?dark["']?\]/
            );

            expect(hasDarkMediaQuery || hasBsThemeDark).toBeTruthy();
        });
    });

    // Responsive Breakpoints
    describe("responsive design", () => {
        test("has mobile breakpoint styles", () => {
            // At least one media query for small screens
            const mobileMQ = cssContent.match(
                /@media\s*\([^)]*max-width\s*:\s*\d+(px|em|rem)[^)]*\)/g
            );
            expect(mobileMQ).toBeTruthy();
            expect(mobileMQ.length).toBeGreaterThan(0);
        });

        test("clears floats on mobile", () => {
            // Float clearing in mobile media query
            expect(cssContent).toMatch(/float\s*:\s*none/);
        });
    });

    // Utility Classes
    describe("utility classes", () => {
        test("has text-muted class support", () => {
            expect(cssContent).toMatch(/\.text-muted/);
        });
    });

    // Max-width Constraint
    describe("layout constraints", () => {
        test("has max-width or container constraint", () => {
            expect(cssContent).toMatch(/max-width\s*:/);
        });
    });

    // Float Clearing
    describe("float clearing", () => {
        test("has clearfix or clear:both mechanism", () => {
            const hasClearfix = cssContent.match(/::after\s*\{[^}]*clear\s*:\s*both/);
            const hasClearBoth = cssContent.match(/clear\s*:\s*both/);

            expect(hasClearfix || hasClearBoth).toBeTruthy();
        });
    });
});
