import { jest } from "@jest/globals";

jest.unstable_mockModule("@hotwired/stimulus", () => ({
    Controller: class {},
}));

let ThemeToggleController;

beforeAll(async () => {
    const mod = await import("../../controllers/theme_toggle_controller.js");
    ThemeToggleController = mod.ThemeToggleController || mod.default;
});

describe("hotwire/theme_toggle_controller", () => {
    beforeEach(() => {
        document.body.innerHTML =
            '<button id="toggle"><span id="label"></span></button>';
        document.cookie = "theme=; Max-Age=0; Path=/;";
    });

    const setControllerElement = (controller, element) => {
        Object.defineProperty(controller, "element", {
            value: element,
            configurable: true,
        });
    };

    test("sync updates attributes and label for light mode", () => {
        document.cookie = "theme=light; Path=/;";

        const controller = new ThemeToggleController();
        setControllerElement(controller, document.getElementById("toggle"));
        controller.hasLabelTarget = true;
        controller.labelTarget = document.getElementById("label");

        controller.connect();

        expect(controller.element.dataset.themeMode).toBe("light");
        expect(controller.element.getAttribute("aria-pressed")).toBe("false");
        expect(controller.labelTarget.textContent).toBe("Light");
        expect(controller.element.getAttribute("title")).toContain(
            "Theme: light",
        );
    });

    test("toggle cycles from system to light", () => {
        const controller = new ThemeToggleController();
        setControllerElement(controller, document.getElementById("toggle"));
        controller.hasLabelTarget = true;
        controller.labelTarget = document.getElementById("label");

        controller.toggle();

        expect(document.cookie).toContain("theme=light");
        expect(controller.element.dataset.themeMode).toBe("light");
        expect(controller.labelTarget.textContent).toBe("Light");
    });

    test("sync updates attributes and label for dark mode", () => {
        document.cookie = "theme=dark; Path=/;";

        const controller = new ThemeToggleController();
        setControllerElement(controller, document.getElementById("toggle"));
        controller.hasLabelTarget = true;
        controller.labelTarget = document.getElementById("label");

        controller.sync();

        expect(controller.element.dataset.themeMode).toBe("dark");
        expect(controller.element.getAttribute("aria-pressed")).toBe("true");
        expect(controller.labelTarget.textContent).toBe("Dark");
        expect(controller.element.getAttribute("title")).toContain(
            "Theme: dark",
        );
    });

    test("toggle cycles from dark to system", () => {
        document.cookie = "theme=dark; Path=/;";

        const controller = new ThemeToggleController();
        setControllerElement(controller, document.getElementById("toggle"));
        controller.hasLabelTarget = true;
        controller.labelTarget = document.getElementById("label");

        controller.toggle();

        expect(controller.element.dataset.themeMode).toBe("system");
        expect(controller.labelTarget.textContent).toBe("System");
    });
});
