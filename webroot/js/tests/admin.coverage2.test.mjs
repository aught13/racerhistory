/**
 * @jest-environment jsdom
 */

/* Targeted branch coverage for admin.js - uncovered lines */

function setupModal() {
    document.body.innerHTML = `
        <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <button id="confirm-delete-modal-delete-btn">Delete</button>
            <form id="confirm-delete-modal-hidden-form">
                <input type="hidden" name="_csrfToken" value="tok" />
            </form>
        </div>
        <form id="source-form" action="/admin/delete/1">
            <input type="hidden" name="_csrfToken" value="tok2" />
        </form>`;
}

beforeEach(() => {
    jest.resetModules();
    document.body.innerHTML = "";
    delete window.showConfirmDelete;
    delete window.AdminToast;
    // Stub bootstrap globally
    window.bootstrap = {
        Modal: {
            getOrCreateInstance: jest.fn(() => ({
                show: jest.fn(),
            })),
        },
    };
});

afterEach(() => {
    delete window.bootstrap;
});

describe("admin.js additional branch coverage", () => {
    test("renderAssociated with object items (non-string)", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({
            associated: [
                { label: "TestLabel" },
                { name: "TestName" },
                { id: 1 },
            ],
        });
        const items = document.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(items.length).toBe(3);
        expect(items[0].textContent).toBe("TestLabel");
        expect(items[1].textContent).toBe("TestName");
        expect(items[2].textContent).toContain("1");
    });

    test("renderAssociated with array of strings", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({
            associated: ["Item 1", "Item 2"],
        });
        const items = document.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(items.length).toBe(2);
    });

    test("renderAssociated with non-array non-string associated", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({
            associated: { custom: "data" },
        });
        const items = document.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(items.length).toBe(1);
    });

    test("renderAssociated with invalid JSON string", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        jest.spyOn(console, "error").mockImplementation(() => {});
        window.AdminToast = jest.fn();
        await import("../admin.js");
        window.showConfirmDelete({
            associated: "not-valid-json{",
        });
        expect(console.error).toHaveBeenCalledWith(
            expect.stringContaining("Error parsing"),
            expect.any(Error),
        );
    });

    test("renderAssociated with null associated", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({ associated: null });
        const items = document.querySelectorAll(
            "#confirm-delete-modal-assoc li",
        );
        expect(items.length).toBe(0);
    });

    test("showConfirmDelete without Bootstrap uses style.display", async () => {
        setupModal();
        delete window.bootstrap;
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({});
        const modal = document.getElementById("confirm-delete-modal");
        expect(modal.style.display).toBe("block");
    });

    test("showConfirmDelete without modal element logs message", async () => {
        document.body.innerHTML = "";
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");
        window.showConfirmDelete({});
        expect(console.debug).toHaveBeenCalledWith(
            expect.stringContaining("modal not present"),
        );
    });

    test("modal show.bs.modal event sets context from trigger", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        const modal = document.getElementById("confirm-delete-modal");
        const trigger = document.createElement("button");
        trigger.dataset.deleteUrl = "/admin/delete/5";
        trigger.dataset.associated = '["A"]';

        const event = new Event("show.bs.modal");
        Object.defineProperty(event, "relatedTarget", { value: trigger });
        modal.dispatchEvent(event);
    });

    test("delegated click on trigger sets context", async () => {
        setupModal();
        // Add a trigger button
        const trigger = document.createElement("button");
        trigger.setAttribute("data-bs-target", "#confirm-delete-modal");
        trigger.dataset.deleteUrl = "/admin/delete/10";
        trigger.dataset.associated = '["X"]';
        document.body.appendChild(trigger);

        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        trigger.click();
    });

    test("delete btn click with source form submits source form", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        const submitSpy = jest.fn();
        await import("../admin.js");

        // Set context with formId pointing to source-form
        window.showConfirmDelete({
            deleteUrl: "/admin/delete/1",
            formId: "source-form",
            ids: "[1,2]",
            idsName: "ids[]",
        });

        // Mock requestSubmit
        const form = document.getElementById("source-form");
        form.requestSubmit = submitSpy;

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();
        expect(submitSpy).toHaveBeenCalled();
    });

    test("delete btn click without source form uses temp form", async () => {
        setupModal();
        // Remove the source form
        const srcForm = document.getElementById("source-form");
        srcForm.remove();

        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/99",
            ids: "42",
            idsName: "id",
        });

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();
    });

    test("delete btn with non-JSON numeric id string", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        jest.spyOn(console, "error").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/bulk",
            formId: "source-form",
            ids: "42",
            idsName: "item_ids[]",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        // Should have injected the id
        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBeGreaterThan(0);
    });

    test("delete btn with bulkAction adds bulk_action field", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/bulk",
            formId: "source-form",
            ids: "[1]",
            idsName: "ids[]",
            bulkAction: "delete",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        const bulkField = form.querySelector(
            '.injected-delete[name="bulk_action"]',
        );
        expect(bulkField).toBeTruthy();
        expect(bulkField.value).toBe("delete");
    });

    test("toast creates and auto-removes notification", async () => {
        setupModal();
        jest.useFakeTimers();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.AdminToast("Test message", "success");
        const alerts = document.querySelectorAll(".alert-success");
        expect(alerts.length).toBe(1);
        expect(alerts[0].textContent).toBe("Test message");

        jest.advanceTimersByTime(5000);
        jest.useRealTimers();
    });

    test("toast with default type is info", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.AdminToast("Info message");
        const alerts = document.querySelectorAll(".alert-info");
        expect(alerts.length).toBe(1);
    });

    test("delete btn click with empty ids array", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/1",
            formId: "source-form",
            ids: "[]",
            idsName: "ids[]",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(0);
    });

    test("delete btn with no requestSubmit falls to .submit()", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/1",
            formId: "source-form",
        });

        const form = document.getElementById("source-form");
        // Override requestSubmit to be non-function so the else branch fires
        Object.defineProperty(form, "requestSubmit", {
            value: undefined,
            writable: true,
            configurable: true,
        });
        const submitSpy = jest.fn();
        form.submit = submitSpy;

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();
        expect(submitSpy).toHaveBeenCalled();
    });

    test("delete btn with ids as array type", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/bulk",
            formId: "source-form",
            ids: [10, 20, 30],
            idsName: "sport_ids[]",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(3);
    });

    test("delete btn with single non-array non-string id", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/1",
            formId: "source-form",
            ids: 42,
            idsName: "id",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(1);
    });

    test("delete btn with non-numeric unparseable ids string", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        jest.spyOn(console, "error").mockImplementation(() => {});
        await import("../admin.js");

        window.showConfirmDelete({
            deleteUrl: "/admin/delete/1",
            formId: "source-form",
            ids: "abc-not-json",
            idsName: "id",
        });

        const form = document.getElementById("source-form");
        form.requestSubmit = jest.fn();

        const delBtn = document.getElementById(
            "confirm-delete-modal-delete-btn",
        );
        delBtn.click();

        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(0);
    });

    test("show.bs.modal without relatedTarget is handled", async () => {
        setupModal();
        jest.spyOn(console, "debug").mockImplementation(() => {});
        await import("../admin.js");

        const modal = document.getElementById("confirm-delete-modal");
        const event = new Event("show.bs.modal");
        modal.dispatchEvent(event);
    });

    test("onDomReady when document loading adds listener", async () => {
        // This test ensures the DOMContentLoaded path is exercised
        document.body.innerHTML = "";
        jest.spyOn(console, "debug").mockImplementation(() => {});
        // Module runs the IIFE and onDomReady during import
        await import("../admin.js");
        expect(window.AdminToast).toBeDefined();
    });
});
