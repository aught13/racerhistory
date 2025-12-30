describe("More coverage targets to reach branch threshold", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        jest.resetModules();
    });

    test("admin uses source form and injects numeric parsed id and bulkAction", () => {
        document.body.innerHTML = `
      <div id="confirm-delete-modal">
        <ul id="confirm-delete-modal-assoc"></ul>
        <button id="confirm-delete-modal-delete-btn">Delete</button>
        <form id="confirm-delete-modal-hidden-form"></form>
      </div>
    `;

        // create source form that should be used
        const src = document.createElement("form");
        src.id = "sourceForm";
        src.action = "/fromsource";
        // add an existing hidden token input
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_csrf";
        token.value = "tok";
        src.appendChild(token);
        document.body.appendChild(src);

        const admin = require("../../js/admin");
        const internals = admin.__internals || {};

        // set context with ids being JSON number (parsed non-array branch)
        internals.setContext({
            formId: "sourceForm",
            deleteUrl: "/bulkdel",
            ids: JSON.stringify(123),
            idsName: "ids[]",
            bulkAction: "delete",
        });

        // click delete button
        const btn = document.getElementById("confirm-delete-modal-delete-btn");
        btn.dispatchEvent(new MouseEvent("click", { bubbles: true }));

        // the source form should now contain injected inputs with class injected-delete
        const injected = src.querySelectorAll(".injected-delete");
        // expect at least two injected fields (ids[] and bulk_action)
        expect(injected.length).toBeGreaterThanOrEqual(2);
        const idInput = Array.from(injected).find((n) => n.name === "ids[]");
        expect(idInput).toBeTruthy();
        expect(idInput.value).toBe("123");
        const bulk = Array.from(injected).find((n) => n.name === "bulk_action");
        expect(bulk).toBeTruthy();
        expect(bulk.value).toBe("delete");
    });

    test("games_sport_dynamic.fetchMeta loads and renders template on success", async () => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/meta"></select><div id="sport-specific-section"></div><span id="current-sport"></span><span id="sport-loading"></span>';
        // mock fetch success
        global.fetch = jest.fn().mockResolvedValue({
            json: async () => ({
                success: true,
                sportName: "Hockey",
                eavTemplate: [{ field_name: "a", field_type: "text" }],
                values: { a: "1" },
            }),
        });

        const gs = require("../../js/games_sport_dynamic");
        const fetchMeta =
            gs.fetchMeta || (gs.__internals && gs.__internals.fetchMeta);
        await fetchMeta("1");
        expect(document.getElementById("current-sport").textContent).toBe(
            "Hockey",
        );
        expect(
            document.querySelectorAll("#sport-specific-section .card").length,
        ).toBeGreaterThanOrEqual(1);
    });

    test("SportAwareGameForm shows fallback on fetch failure", async () => {
        document.body.innerHTML =
            '<select id="team-season-select" data-sport-url="/bad"></select><div id="sport-indicator"><div class="alert"></div></div><span id="current-sport"></span><span id="sport-loading"></span><div id="sport-specific-section"></div>';
        // mock fetch returning non-ok
        global.fetch = jest.fn().mockResolvedValue({ ok: false, status: 500 });

        const Saf = require("../../js/sport-aware-game-form");
        const inst = new Saf();
        await inst.updateSportFields("1");
        // fallback shows Game Details (in sportSection) and error set on currentSportSpan
        expect(
            document.getElementById("sport-specific-section").textContent,
        ).toContain("Game Details");
        expect(document.getElementById("current-sport").textContent).toContain(
            "Failed to load sport-specific fields",
        );
    });
});
