/**
 * DOM style assertions for critical CSS rules
 * @jest-environment jsdom
 */

const fs = require("fs");
const path = require("path");

const cssPath = path.resolve(__dirname, "../../css/frontend.css");

const loadStyles = () => {
    const css = fs.readFileSync(cssPath, "utf8");
    const style = document.createElement("style");
    style.textContent = css;
    document.head.appendChild(style);
};

describe("CSS regressions", () => {
    beforeAll(() => {
        loadStyles();
    });

    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("navbar uses brand background and shadow", () => {
        const nav = document.createElement("div");
        nav.className = "rh-navbar";
        document.body.appendChild(nav);

        const styles = window.getComputedStyle(nav);
        expect(styles.backgroundColor).toBe("rgb(236, 172, 0)");
        expect(styles.boxShadow).not.toBe("none");
    });

    test("logo link keeps fixed width for layout", () => {
        const link = document.createElement("a");
        link.className = "rh-logo-link";
        document.body.appendChild(link);

        const styles = window.getComputedStyle(link);
        expect(styles.display).toBe("flex");
        expect(styles.width).toBe("140px");
        expect(styles.maxWidth).toBe("140px");
    });
});
