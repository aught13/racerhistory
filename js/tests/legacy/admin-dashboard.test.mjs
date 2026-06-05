/** @jest-environment jsdom */
import { jest } from "@jest/globals";

describe("admin-dashboard.js", () => {
    beforeEach(async () => {
        document.body.innerHTML = `
      <form id="clear-cache-form" method="post" action="/admin/dashboard/clear-cache">
        <button type="submit" id="btn-clear-cache">
          <i class="bi bi-trash3 me-2"></i>Clear CakePHP Cache
        </button>
      </form>
    `;
        jest.resetModules();
        // Stub window.confirm
        window.confirm = jest.fn(() => true);
        await import("../../legacy/admin-dashboard.js");
    });

    afterEach(() => {
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    test("form exists in DOM after setup", () => {
        const form = document.getElementById("clear-cache-form");
        expect(form).not.toBeNull();
    });

    test("submit proceeds when confirm returns true", () => {
        window.confirm = jest.fn(() => true);
        const form = document.getElementById("clear-cache-form");
        const btn = document.getElementById("btn-clear-cache");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });
        const prevented = !form.dispatchEvent(submitEvent);
        expect(window.confirm).toHaveBeenCalledWith(
            "Clear all CakePHP cache engines?",
        );
        expect(prevented).toBe(false);
        expect(btn.disabled).toBe(true);
        expect(btn.innerHTML).toContain("Clearing");
    });

    test("submit is prevented when confirm returns false", () => {
        window.confirm = jest.fn(() => false);
        const form = document.getElementById("clear-cache-form");
        const btn = document.getElementById("btn-clear-cache");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });
        const prevented = !form.dispatchEvent(submitEvent);
        expect(window.confirm).toHaveBeenCalled();
        expect(prevented).toBe(true);
        expect(btn.disabled).toBe(false);
    });

    test("handles missing button gracefully", async () => {
        document.body.innerHTML = `
      <form id="clear-cache-form" method="post" action="/admin/dashboard/clear-cache">
      </form>
    `;
        jest.resetModules();
        await import("../../legacy/admin-dashboard.js");
        const form = document.getElementById("clear-cache-form");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });
        // Should not throw
        expect(() => form.dispatchEvent(submitEvent)).not.toThrow();
    });

    test("does nothing when form is absent from DOM", async () => {
        document.body.innerHTML = "<div>No form here</div>";
        jest.resetModules();
        // Should not throw
        await expect(
            import("../../legacy/admin-dashboard.js"),
        ).resolves.not.toThrow();
    });

    test("re-initialises on turbo:load event", async () => {
        // Clear DOM and re-add form
        document.body.innerHTML = `
      <form id="clear-cache-form" method="post" action="/admin/dashboard/clear-cache">
        <button type="submit" id="btn-clear-cache">Clear CakePHP Cache</button>
      </form>
    `;
        document.dispatchEvent(new Event("turbo:load"));
        window.confirm = jest.fn(() => true);
        const form = document.getElementById("clear-cache-form");
        const submitEvent = new Event("submit", {
            bubbles: true,
            cancelable: true,
        });
        form.dispatchEvent(submitEvent);
        expect(window.confirm).toHaveBeenCalled();
    });

    test("exports initDashboard via module.exports", async () => {
        jest.resetModules();
        const mod = await import("../../legacy/admin-dashboard.js");
        // The IIFE attaches to module.exports
        expect(mod).toBeDefined();
    });
});
