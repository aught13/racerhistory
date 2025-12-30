describe("games_sport_dynamic additional branches", () => {
    let buildFieldControl, groupFields, renderEav;
    beforeEach(() => {
        jest.resetModules();
        document.body.innerHTML = `
            <div id="sport-specific-section"></div>
            <select id="team-season-select" data-sport-url="/api/sport"></select>
        `;
        // require after DOM setup so the IIFE in the module runs and exports helpers

        const mod = require("../games_sport_dynamic");
        buildFieldControl = mod.buildFieldControl;
        groupFields = mod.groupFields;
        renderEav = mod.renderEav;
    });

    test("buildFieldControl creates number input with min/max when field_type=number", () => {
        const field = {
            field_name: "score",
            display_label: "Score",
            field_type: "number",
            min: 0,
            max: 10,
        };
        const wrapper = buildFieldControl(field, {});
        const input = wrapper.querySelector("input");
        expect(input).toBeDefined();
        expect(input.type).toBe("number");
        expect(input.min).toBe("0");
        expect(input.max).toBe("10");
    });

    test("buildFieldControl falls back to text input for non-number types", () => {
        const field = {
            field_name: "note",
            display_label: "Note",
            field_type: "text",
        };
        const wrapper = buildFieldControl(field, { note: "hello" });
        const input = wrapper.querySelector("input");
        expect(input).toBeDefined();
        expect(input.type).toBe("text");
        expect(input.value).toBe("hello");
    });

    test("groupFields groups by field_group and renderEav creates cards", () => {
        const fields = [
            { field_name: "a", field_group: "alpha", display_label: "A" },
            { field_name: "b", field_group: "beta", display_label: "B" },
            { field_name: "c", display_label: "C" },
        ];
        const groups = groupFields(fields);
        expect(Object.keys(groups).sort()).toEqual(
            ["alpha", "beta", "general"].sort(),
        );

        // render into the section and assert cards created
        renderEav(fields, { a: "1", b: "2", c: "3" });
        const cards = document.querySelectorAll(
            "#sport-specific-section .card",
        );
        expect(cards.length).toBeGreaterThanOrEqual(2);
        // headings present
        const headings = Array.from(
            document.querySelectorAll(
                "#sport-specific-section .card-header h6",
            ),
        ).map((h) => h.textContent);
        expect(
            headings.some((h) => /Alpha|General|Beta/i.test(h)),
        ).toBeTruthy();
    });
});
