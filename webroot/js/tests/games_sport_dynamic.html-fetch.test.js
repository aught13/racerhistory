/**
 * @jest-environment jsdom
 */

// test uses DOM APIs and module imports; no file system access needed here

// Load the module under test after setting up DOM fixtures and mocking fetch
describe("games_sport_dynamic HTML-fetch path", () => {
    let moduleExports;

    beforeEach(() => {
        // Reset modules and DOM
        jest.resetModules();
        document.body.innerHTML = `
      <select id="team-season-select" data-sport-url="/admin/games/ajax-game-eav-meta"></select>
      <div id="sport-specific-section"></div>
      <div id="sport-indicator" style="display:none"><span id="current-sport"></span><div id="sport-loading" style="display:none"></div></div>
      <input type="hidden" id="game-id-hidden" value="" />
    `;
    });

    test("injects server-rendered HTML fragment when fetch returns HTML", async () => {
        // Mock fetch to return HTML fragment
        global.fetch = jest.fn(() =>
            Promise.resolve({
                ok: true,
                text: () =>
                    Promise.resolve(
                        '<div class="card"><div class="card-body"><input name="period_1_team" /></div></div>',
                    ),
            }),
        );

        moduleExports = require("../games_sport_dynamic");

        // Call fetchMeta helper directly to trigger the HTML fetch and injection
        await moduleExports.fetchMeta("1");

        const section = document.getElementById("sport-specific-section");
        expect(section.innerHTML).toContain('name="period_1_team"');
    });
});
