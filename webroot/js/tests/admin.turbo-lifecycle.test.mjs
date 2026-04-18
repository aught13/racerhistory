/** @jest-environment jsdom */
import { jest } from "@jest/globals";

/**
 * Tests for admin.js Turbo lifecycle integration.
 *
 * Verifies that:
 * - turbo:load and turbo:frame-load listeners re-attach modal listeners
 * - attachModalListener uses a marker to avoid duplicate listeners
 * - The delegated click handler survives Turbo navigations
 */
describe("admin.js Turbo lifecycle", () => {
    let internals;
    let addEventSpy;

    beforeEach(async () => {
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <ul id="confirm-delete-modal-assoc"></ul>
        <form id="confirm-delete-modal-hidden-form"></form>
        <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
      </div>
    `;
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => ({ show: jest.fn() })),
            },
        };
        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            value: jest.fn(),
            configurable: true,
            writable: true,
        });
        Object.defineProperty(HTMLFormElement.prototype, "submit", {
            value: jest.fn(),
            configurable: true,
            writable: true,
        });

        addEventSpy = jest.spyOn(document, "addEventListener");
        jest.resetModules();
        const mod = await import("../admin.js");
        internals = mod.__internals;
    });

    afterEach(() => {
        document.body.innerHTML = "";
        delete global.bootstrap;
        jest.restoreAllMocks();
        jest.resetModules();
    });

    test("registers turbo:load listener for attachModalListener", () => {
        const turboLoadCalls = addEventSpy.mock.calls.filter(
            ([event]) => event === "turbo:load",
        );
        expect(turboLoadCalls.length).toBeGreaterThanOrEqual(1);
    });

    test("registers turbo:frame-load listener for attachModalListener", () => {
        const frameLoadCalls = addEventSpy.mock.calls.filter(
            ([event]) => event === "turbo:frame-load",
        );
        expect(frameLoadCalls.length).toBeGreaterThanOrEqual(1);
    });

    test("attachModalListener sets data-admin-modal-bound marker", () => {
        const modal = document.getElementById("confirm-delete-modal");
        // Clear any marker set during import
        delete modal.dataset.adminModalBound;

        internals.attachModalListener();
        expect(modal.dataset.adminModalBound).toBe("1");
    });

    test("attachModalListener does not duplicate listeners (idempotent)", () => {
        const modal = document.getElementById("confirm-delete-modal");
        const listenerSpy = jest.spyOn(modal, "addEventListener");

        // Clear marker and attach once
        delete modal.dataset.adminModalBound;
        internals.attachModalListener();
        const firstCallCount = listenerSpy.mock.calls.length;

        // Second call should be a no-op (marker is set)
        internals.attachModalListener();
        expect(listenerSpy.mock.calls.length).toBe(firstCallCount);
    });

    test("attachModalListener handles missing modal gracefully", () => {
        document.body.innerHTML = ""; // Remove the modal
        // Should not throw
        expect(() => internals.attachModalListener()).not.toThrow();
    });

    test("modal show.bs.modal event sets context after re-attach", () => {
        const modal = document.getElementById("confirm-delete-modal");
        // Clear marker and re-attach
        delete modal.dataset.adminModalBound;
        internals.attachModalListener();

        // Create a fake trigger button
        const trigger = document.createElement("button");
        trigger.dataset.deleteUrl = "/admin/test/delete/1";
        trigger.dataset.associated = '["Item1"]';

        // Dispatch show.bs.modal with relatedTarget
        const event = new Event("show.bs.modal");
        event.relatedTarget = trigger;
        modal.dispatchEvent(event);

        // Verify the modal assoc list was populated
        const assocList = document.getElementById("confirm-delete-modal-assoc");
        expect(assocList.children.length).toBe(1);
        expect(assocList.children[0].textContent).toBe("Item1");
    });
});
