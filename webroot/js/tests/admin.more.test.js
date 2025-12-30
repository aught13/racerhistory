/** @jest-environment jsdom */
// Additional coverage for admin.js: single id string, array ids, no ids, temp form fallback, show.bs.modal event

describe("admin.js more branches", () => {
    let exports;
    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = `
          <div id="confirm-delete-modal">
            <ul id="confirm-delete-modal-assoc"></ul>
            <form id="confirm-delete-modal-hidden-form"></form>
            <button id="confirm-delete-modal-delete-btn" type="button">Delete</button>
          </div>
          <form id="delete-form-sample" method="post"></form>
        `;
        // Reset global.bootstrap
        global.bootstrap = {
            Modal: {
                getOrCreateInstance: jest.fn(() => ({ show: jest.fn() })),
            },
        };
        // Reset requestSubmit
        Object.defineProperty(HTMLFormElement.prototype, "requestSubmit", {
            value: jest.fn(function () {
                if (this.submit) this.submit();
            }),
            configurable: true,
            writable: true,
        });
        // Reset submit
        Object.defineProperty(HTMLFormElement.prototype, "submit", {
            value: jest.fn(),
            configurable: true,
            writable: true,
        });
        jest.resetModules();
        exports = require("../admin.js");
    });
    afterEach(() => {
        document.body.innerHTML = "";
        delete global.bootstrap;
        jest.clearAllMocks();
    });

    test("single numeric id string injects one input", () => {
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        exports.showConfirmDelete({
            deleteUrl: "/del",
            ids: "15",
            idsName: "sport_ids[]",
            formId: "delete-form-sample",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const injected = form.querySelectorAll(".injected-delete");
        expect(injected.length).toBe(1);
        expect(injected[0].name).toBe("sport_ids[]");
        expect(injected[0].value).toBe("15");
    });

    test("ids as array injects multiple inputs", () => {
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        exports.showConfirmDelete({
            deleteUrl: "/del2",
            ids: [3, 4],
            idsName: "sport_ids[]",
            formId: "delete-form-sample",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const ids = Array.from(form.querySelectorAll(".injected-delete"))
            .filter((i) => i.name === "sport_ids[]")
            .map((i) => i.value);
        expect(ids).toEqual(["3", "4"]);
    });

    test("no ids and no bulk produces zero injected inputs", () => {
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        exports.showConfirmDelete({
            deleteUrl: "/nothing",
            formId: "delete-form-sample",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        expect(form.querySelectorAll(".injected-delete").length).toBe(0);
    });

    test("temp form fallback when no formId and no hidden form", () => {
        // Remove hidden form and sample form so fallback must build temp
        document.getElementById("confirm-delete-modal-hidden-form").remove();
        document.getElementById("delete-form-sample").remove();
        exports.showConfirmDelete({
            deleteUrl: "/fallback",
            ids: "[9]",
            idsName: "sport_ids[]",
        });
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const temp = Array.from(document.querySelectorAll("form")).find((f) =>
            f.action.includes("/fallback"),
        );
        expect(temp).toBeTruthy();
        const hidden = temp
            ? temp.querySelectorAll('input[type="hidden"]').length
            : 0;
        expect(hidden).toBeGreaterThanOrEqual(1);
    });

    test("show.bs.modal event populates context from relatedTarget dataset", () => {
        const modal = document.getElementById("confirm-delete-modal");
        const trigger = document.createElement("button");
        trigger.setAttribute("data-bs-target", "#confirm-delete-modal");
        trigger.dataset.deleteUrl = "/evdel";
        trigger.dataset.associated = JSON.stringify(["Alpha"]);
        trigger.dataset.ids = JSON.stringify([42]);
        trigger.dataset.idsName = "sport_ids[]";
        trigger.dataset.formId = "delete-form-sample";
        document.body.appendChild(trigger);
        // dispatch event
        const ev = new Event("show.bs.modal");
        ev.relatedTarget = trigger;
        modal.dispatchEvent(ev);
        // Now delete to trigger injection
        const form = document.getElementById("delete-form-sample");
        form.submit = jest.fn();
        document.getElementById("confirm-delete-modal-delete-btn").click();
        const ids = Array.from(
            form.querySelectorAll(".injected-delete"),
        ).filter((i) => i.name === "sport_ids[]");
        expect(ids.length).toBe(1);
        expect(ids[0].value).toBe("42");
        const assocList = document.getElementById("confirm-delete-modal-assoc");
        expect(assocList.children.length).toBe(1);
    });
});
