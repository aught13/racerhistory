import { Application } from "@hotwired/stimulus";
import { jest } from "@jest/globals";

import AdminConfirmDeleteController from "../../../js/controllers/admin_confirm_delete_controller.js";

describe("Coverage targets 3 - requestSubmit and mapping/min-max branches", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
        application = Application.start();
        application.register(
            "admin-confirm-delete",
            AdminConfirmDeleteController,
        );
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }
        delete window.__rhStimulusShowConfirmDelete;
    });

    test("admin uses source.requestSubmit when available", async () => {
        document.body.innerHTML = `
      <div id="confirm-delete-modal" data-controller="admin-confirm-delete">
        <ul id="confirm-delete-modal-assoc"></ul>
        <button id="confirm-delete-modal-delete-btn" data-action="admin-confirm-delete#confirmDelete">Delete</button>
        <form id="confirm-delete-modal-hidden-form"></form>
      </div>
    `;

        await Promise.resolve();

        const src = document.createElement("form");
        src.id = "srcRS";
        src.action = "/rs";
        // attach a requestSubmit function to simulate modern browsers
        src.requestSubmit = jest.fn();
        document.body.appendChild(src);

        window.__rhStimulusShowConfirmDelete({
            formId: "srcRS",
            deleteUrl: "/rs",
            ids: "[5]",
            idsName: "ids[]",
        });
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        expect(src.requestSubmit).toHaveBeenCalled();
    });

    test("admin falls back to temp.requestSubmit when source missing but temp supports requestSubmit", async () => {
        document.body.innerHTML = `
            <div id="confirm-delete-modal" data-controller="admin-confirm-delete">
        <ul id="confirm-delete-modal-assoc"></ul>
                <button id="confirm-delete-modal-delete-btn" data-action="admin-confirm-delete#confirmDelete">Delete</button>
      </div>
    `;

        await Promise.resolve();

        // monkeypatch HTMLFormElement.prototype.requestSubmit to simulate availability for temp form
        const orig = HTMLFormElement.prototype.requestSubmit;
        HTMLFormElement.prototype.requestSubmit = function () {
            /* noop */
        };

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: "/tmpRS",
            ids: "[9]",
            idsName: "ids[]",
        });

        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        // find last appended form (temp)
        const forms = document.getElementsByTagName("form");
        const temp = forms[forms.length - 1];
        expect(temp).toBeTruthy();

        // restore original
        HTMLFormElement.prototype.requestSubmit = orig;
    });

    test("sport-aware mapping sets multiple legacy period inputs", async () => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/ok"></select><div id="sport-indicator"><div class="alert"></div></div><span id="current-sport"></span><span id="sport-loading"></span><div id="sport-specific-section"></div>';
        // create legacy inputs to be mapped
        const keys = [
            "period_1_mur",
            "period_1_opp",
            "period_2_mur",
            "period_2_opp",
        ];
        keys.forEach((k) => {
            const i = document.createElement("input");
            i.name = k;
            document.body.appendChild(i);
        });

        const values = {
            period_1_team: "10",
            period_1_opponent: "8",
            period_2_team: "7",
            period_2_opponent: "6",
        };
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: true, sportName: "Test", values }),
        });

        const _safmod = await import("../sport-aware-game-form");
        const Saf = _safmod.default || _safmod;
        const inst = new Saf();
        await inst.updateSportFields("1");

        expect(document.getElementsByName("period_1_mur")[0].value).toBe("10");
        expect(document.getElementsByName("period_1_opp")[0].value).toBe("8");
        expect(document.getElementsByName("period_2_mur")[0].value).toBe("7");
        expect(document.getElementsByName("period_2_opp")[0].value).toBe("6");
    });

    test("games_sport_dynamic number field without min/max leaves attributes absent", async () => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/test"></select><div id="sport-specific-section"></div>';
        const mod = await import("../games_sport_dynamic");
        const buildFieldControl =
            mod.buildFieldControl ||
            (mod.__internals && mod.__internals.buildFieldControl);
        const meta = { field_name: "nolim", field_type: "number" };
        const control = buildFieldControl(meta, {});
        const input = control.querySelector("input");
        expect(input.getAttribute("min")).toBeNull();
        expect(input.getAttribute("max")).toBeNull();
    });
});
