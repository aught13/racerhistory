import { jest } from "@jest/globals";

let SportAwareGameForm;
describe("SportAwareGameForm branch coverage", () => {
    let container;

    beforeEach(async () => {
        jest.resetModules();
        const mod = await import("../../legacy/sport-aware-game-form.js");
        SportAwareGameForm = mod.default || mod;

        document.body.innerHTML = `
            <select id="team-season-select" data-sport-url="/sportmeta"></select>
            <span id="sport-indicator" style="display:none"><span id="current-sport"></span><span id="sport-loading" style="display:none"></span><div class="alert"></div></span>
            <div id="sport-specific-section"></div>
            <input type="hidden" id="game-id-hidden" value="">
            <input type="text" name="period_1_mur" value="">
        `;
        container = document.getElementById("sport-specific-section");
    });

    afterEach(() => {
        jest.restoreAllMocks();
        document.body.innerHTML = "";
    });

    test("renders fallback when no eavTemplate", async () => {
        const sel = document.getElementById("team-season-select");
        sel.value = "123";

        // mock fetch to return success=false
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: false, error: "nope" }),
        });

        const s = new SportAwareGameForm();
        await s.updateSportFields("123");

        expect(container.innerHTML).toMatch(/Game Details/);
        // sport indicator should show warning message text
        expect(document.getElementById("current-sport").textContent).toMatch(
            /nope|Failed/,
        );
    });

    test("renders number field with min/max and text field fallback", () => {
        const s = new SportAwareGameForm();
        const numField = {
            field_type: "number",
            field_name: "numtest",
            display_label: "Number Test",
            min: 0,
            max: 10,
            default_value: 5,
        };
        const textField = {
            field_type: "text",
            field_name: "texttest",
            display_label: "Text Test",
            default_value: "abc",
        };

        const htmlNum = s.renderField(numField);
        expect(htmlNum).toMatch(/type="number"/);
        expect(htmlNum).toMatch(/min="0"/);
        expect(htmlNum).toMatch(/max="10"/);

        const htmlText = s.renderField(textField);
        expect(htmlText).toMatch(/type="text"/);
        expect(htmlText).toMatch(/value="abc"/);
    });

    test("updateSportFields handles non-ok fetch (throws and shows fallback)", async () => {
        // simulate network error / non-ok
        global.fetch = jest.fn().mockResolvedValue({ ok: false, status: 500 });

        const s = new SportAwareGameForm();
        await s.updateSportFields("456");

        expect(container.innerHTML).toMatch(/Game Details/);
        expect(document.getElementById("current-sport").textContent).toMatch(
            /Failed/,
        );
    });

    test("maps period values to legacy inputs when data.values present", async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                success: true,
                sportName: "MockSport",
                eavTemplate: { a: { field_name: "x" } },
                values: { period_1_team: "12" },
            }),
        });

        const periodInput = document.createElement("input");
        periodInput.name = "period_1_mur";
        document.body.appendChild(periodInput);

        const s = new SportAwareGameForm();
        await s.updateSportFields("789");

        const matches = Array.from(document.getElementsByName("period_1_mur"));
        const anyMatch = matches.some((el) => el.value === "12");
        expect(anyMatch).toBe(true);
        expect(document.getElementById("current-sport").textContent).toBe(
            "MockSport",
        );
    });
});
