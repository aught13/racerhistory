/** @jest-environment jsdom */
// Additional tests: fallback (no bootstrap), multiple invocations cleanup, invalid JSON, toast helper

describe("admin.js additional scenarios", () => {
    function setupModalDom() {
        document.body.innerHTML = `
      <div id="confirm-delete-modal" style="display:none">
        <ul id="confirm-delete-modal-assoc"></ul>
        <form id="confirm-delete-modal-hidden-form"></form>
        <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
      </div>
      <form id="delete-form-sample" method="post"></form>
    `;
    }

    let origRequestSubmit, origSubmit, origBootstrap;
    beforeEach(() => {
        // Reset DOM and globals
        document.body.innerHTML = "";
        origBootstrap = Object.prototype.hasOwnProperty.call(
            global,
            "bootstrap",
        )
            ? global.bootstrap
            : undefined;
        global.bootstrap = undefined;
        origRequestSubmit = Object.getOwnPropertyDescriptor(
            HTMLFormElement.prototype,
            "requestSubmit",
        );
        origSubmit = Object.getOwnPropertyDescriptor(
            HTMLFormElement.prototype,
            "submit",
        );
        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            value: jest.fn(function () {
                if (this.submit) this.submit();
            }),
            configurable: true,
            writable: true,
        });
        Object.defineProperty(HTMLFormElement.prototype, "submit", {
            value: jest.fn(),
            configurable: true,
            writable: true,
        });
        jest.resetModules();
        jest.useFakeTimers();
    });
    afterEach(() => {
        document.body.innerHTML = "";
        if (origBootstrap !== undefined) {
            global.bootstrap = origBootstrap;
        } else {
            delete global.bootstrap;
        }
        if (origRequestSubmit) {
            Object.defineProperty(
                HTMLFormElement.prototype,
                "requestSubmit",
                origRequestSubmit,
            );
        } else {
            delete HTMLFormElement.prototype.requestSubmit;
        }
        if (origSubmit) {
            Object.defineProperty(
                HTMLFormElement.prototype,
                "submit",
                origSubmit,
            );
        } else {
            delete HTMLFormElement.prototype.submit;
        }
        jest.runOnlyPendingTimers();
        jest.useRealTimers();
        jest.clearAllMocks();
    });

    test("fallback path without bootstrap shows modal by setting display:block", async () => {
        setupModalDom();
        const { showConfirmDelete } = await import("../admin.js");
        showConfirmDelete({
            associated: JSON.stringify(["One"]),
            formId: "delete-form-sample",
        });
        const modal = document.getElementById("confirm-delete-modal");
        expect(modal.style.display).toBe("block");
    });

    test("multiple invocations clean up previously injected inputs", async () => {
        setupModalDom();
        // provide a bootstrap mock so we also exercise that branch
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => ({ show: jest.fn() })),
            },
        };
        const { showConfirmDelete } = await import("../admin.js");
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        // first invocation
        showConfirmDelete({
            deleteUrl: "/x",
            ids: JSON.stringify([1, 2]),
            idsName: "sport_ids[]",
            formId: "delete-form-sample",
            bulkAction: "delete",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(form.querySelectorAll(".injected-delete").length).toBe(3); // 2 ids + bulk
        // second invocation with different ids
        showConfirmDelete({
            deleteUrl: "/x2",
            ids: JSON.stringify([7]),
            idsName: "sport_ids[]",
            formId: "delete-form-sample",
            bulkAction: "delete",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(2); // 1 id + bulk
        const idValues = Array.from(injected)
            .filter((i) => i.name === "sport_ids[]")
            .map((i) => i.value);
        expect(idValues).toEqual(["7"]);
    });

    test("invalid JSON associated and ids do not throw and produce no injected id inputs", async () => {
        setupModalDom();
        const { showConfirmDelete } = await import("../admin.js");
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        expect(() =>
            showConfirmDelete({
                deleteUrl: "/bad",
                associated: "not-json",
                ids: "nope",
                idsName: "sport_ids[]",
                formId: "delete-form-sample",
            }),
        ).not.toThrow();
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(form.querySelectorAll(".injected-delete").length).toBe(0); // Ensure no inputs are injected
    });

    test("AdminToast creates and removes alert with default info type", async () => {
        // minimal DOM without modal still allows toast export
        document.body.innerHTML = '<div id="root"></div>';
        const mod = await import("../admin.js");
        const AdminToast = mod.AdminToast || (mod.default && mod.default.AdminToast) || (typeof require === 'function' ? require('../admin.js').AdminToast : undefined);
        AdminToast("Hello");
        let alerts = document.querySelectorAll(".alert");
        expect(alerts.length).toBe(1);
        expect(alerts[0].className).toContain("alert-info");
        jest.advanceTimersByTime(4000);
        alerts = document.querySelectorAll(".alert");
        expect(alerts.length).toBe(0);
    });

    test("AdminToast with custom type warning", async () => {
        document.body.innerHTML = '<div id="root"></div>';
        const mod = await import("../admin.js");
        const AdminToast = mod.AdminToast || (mod.default && mod.default.AdminToast) || (typeof require === 'function' ? require('../admin.js').AdminToast : undefined);
        AdminToast("Warn", "warning");
        const alert = document.querySelector(".alert");
        expect(alert).not.toBeNull();
        expect(alert.className).toContain("alert-warning");
    });

    // (No global prototype override here; handled in beforeEach/afterEach)
});
