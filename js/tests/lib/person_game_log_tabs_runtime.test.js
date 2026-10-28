/**
 * @jest-environment jsdom
 */

import {
    describe,
    test,
    expect,
    beforeEach,
    afterEach,
    jest,
} from "@jest/globals";

jest.mock("../../legacy/modules/person-game-log-tabs.mjs", () => ({
    __esModule: true,
    default: jest.fn(),
}));

import initPersonGameLogTabs from "../../legacy/modules/person-game-log-tabs.mjs";
import {
    bootPersonGameLogTabs,
    initPersonGameLogTablesRoot,
    initPersonGameLogTabsRoot,
} from "../../lib/person_game_log_tabs_runtime.js";

describe("person_game_log_tabs_runtime", () => {
    beforeEach(() => {
        jest.clearAllMocks();
        delete window.$;
    });

    afterEach(() => {
        delete window.$;
    });

    test("initializes the supplied root and defaults to the document", () => {
        const root = document.createElement("div");

        initPersonGameLogTabsRoot(root);
        initPersonGameLogTabsRoot();

        expect(initPersonGameLogTabs).toHaveBeenNthCalledWith(1, { root });
        expect(initPersonGameLogTabs).toHaveBeenNthCalledWith(2, {
            root: document,
        });
    });

    test("initializes game log tables with searching and paging options", () => {
        const dataTable = jest.fn();
        const jquery = jest.fn(() => ({ DataTable: dataTable }));
        jquery.fn = { dataTable: { isDataTable: jest.fn(() => false) } };

        const root = document.createElement("div");
        root.innerHTML = "<table data-person-game-log-table></table>";
        window.$ = jquery;

        initPersonGameLogTablesRoot(root);

        expect(dataTable).toHaveBeenCalledWith(
            expect.objectContaining({ searching: true, paging: false }),
        );
    });

    test("does not reinitialize an existing game log table", () => {
        const dataTable = jest.fn();
        const jquery = jest.fn(() => ({ DataTable: dataTable }));
        jquery.fn = { dataTable: { isDataTable: jest.fn(() => true) } };

        const root = document.createElement("div");
        root.innerHTML = "<table data-person-game-log-table></table>";
        window.$ = jquery;

        initPersonGameLogTablesRoot(root);

        expect(dataTable).not.toHaveBeenCalled();
    });

    test("skips table setup when DataTables is unavailable", () => {
        const root = document.createElement("div");
        root.innerHTML = "<table data-person-game-log-table></table>";

        expect(() => initPersonGameLogTablesRoot(root)).not.toThrow();
    });

    test("boots a frame root for turbo frame loads", () => {
        const frame = document.createElement("turbo-frame");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        bootPersonGameLogTabs(event);

        expect(initPersonGameLogTabs).toHaveBeenCalledWith({ root: frame });
    });

    test.each([
        ["custom event", new Event("custom-event")],
        [
            "invalid frame target",
            (() => {
                const event = new Event("turbo:frame-load");
                Object.defineProperty(event, "target", {
                    value: "not an element",
                });
                return event;
            })(),
        ],
    ])("uses the document for a %s", (_label, event) => {
        bootPersonGameLogTabs(event);

        expect(initPersonGameLogTabs).toHaveBeenCalledWith({ root: document });
    });
});
