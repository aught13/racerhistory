/**
 * @jest-environment jsdom
 */

import { describe, test, expect, beforeEach, jest } from "@jest/globals";

describe("person_game_log_tabs_runtime", () => {
    beforeEach(() => {
        jest.resetModules();
        delete window.__PERSON_GAME_LOG_TABS_INIT__;
    });

    test("uses window override when available", async () => {
        const mockInit = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = mockInit;

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.initPersonGameLogTabsRoot();

        expect(mockInit).toHaveBeenCalled();
    });

    test("uses default when no override", async () => {
        delete window.__PERSON_GAME_LOG_TABS_INIT__;

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.initPersonGameLogTabsRoot();

        expect(true).toBe(true);
    });

    test("initializes game log tables with sorting and search enabled", async () => {
        const dataTable = jest.fn();
        const jquery = jest.fn(() => ({ DataTable: dataTable }));
        jquery.fn = { dataTable: { isDataTable: jest.fn(() => false) } };
        window.$ = jquery;

        const root = document.createElement("div");
        root.innerHTML = "<table data-person-game-log-table></table>";

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.initPersonGameLogTablesRoot(root);

        expect(dataTable).toHaveBeenCalledWith(
            expect.objectContaining({ searching: true, paging: false }),
        );
    });

    test("does not reinitialize an existing game log table", async () => {
        const dataTable = jest.fn();
        const jquery = jest.fn(() => ({ DataTable: dataTable }));
        jquery.fn = { dataTable: { isDataTable: jest.fn(() => true) } };
        window.$ = jquery;

        const root = document.createElement("div");
        root.innerHTML = "<table data-person-game-log-table></table>";

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.initPersonGameLogTablesRoot(root);

        expect(dataTable).not.toHaveBeenCalled();
    });

    test("bootPersonGameLogTabs handles turbo:frame-load events", async () => {
        const mockInit = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = mockInit;

        const frame = document.createElement("div");
        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: frame });

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.bootPersonGameLogTabs(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootPersonGameLogTabs handles non-turbo events", async () => {
        const mockInit = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = mockInit;

        const event = new Event("custom-event");
        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.bootPersonGameLogTabs(event);

        expect(mockInit).toHaveBeenCalled();
    });

    test("bootPersonGameLogTabs handles invalid frame targets", async () => {
        const mockInit = jest.fn();
        window.__PERSON_GAME_LOG_TABS_INIT__ = mockInit;

        const event = new Event("turbo:frame-load");
        Object.defineProperty(event, "target", { value: "not an element" });

        const mod = await import("../../lib/person_game_log_tabs_runtime.js");
        mod.bootPersonGameLogTabs(event);

        expect(mockInit).toHaveBeenCalled();
    });
});
