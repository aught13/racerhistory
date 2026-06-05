/** @jest-environment jsdom */

import {
    jest,
    describe,
    test,
    expect,
    beforeEach,
    afterEach,
} from "@jest/globals";
import setupDataTablesMock from "./helpers/datatables.mock.mjs";
import initSeasons from "../../legacy/modules/seasons-init.cjs";

const attachSearchBuilderMock = () => {
    if (!global.$?.fn?.dataTable) {
        return;
    }
    global.$.fn.dataTable.SearchBuilder = jest.fn(function () {
        const container = document.createElement("div");
        container.className = "dtsb-searchBuilder";
        this.dom = { container };
        this.container = () => container;
        this.destroy = () => {
            if (container.parentNode) {
                container.parentNode.removeChild(container);
            }
        };
        return this;
    });
};

describe("Seasons frame swap regression", () => {
    let teardown;

    beforeEach(() => {
        document.body.innerHTML = `
            <turbo-frame id="seasons-table-frame">
                <div id="seasons-controls" class="d-flex"></div>
                <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
                <table id="seasons-table">
                    <thead>
                        <tr><th>#</th><th>Team</th><th>Season</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>Team</td><td>2025</td></tr>
                    </tbody>
                </table>
            </turbo-frame>
        `;
        teardown = setupDataTablesMock();
        attachSearchBuilderMock();
        if (global.$ && !window.$) {
            window.$ = global.$;
        }
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
        delete window.$;
    });

    test("renders SearchBuilder after frame swap to splits", () => {
        jest.useFakeTimers();
        try {
            initSeasons({
                tableSelector: "#seasons-table",
                controlsSelector: "#seasons-controls",
                panelSelector: "#searchbuilder-panel",
                filterButtonId: "seasons-filter-btn",
                columns: [1, 2, 3, 4],
            });

            const panel = document.getElementById("searchbuilder-panel");
            expect(panel.querySelector(".dtsb-searchBuilder")).toBeTruthy();

            const frame = document.getElementById("seasons-table-frame");
            frame.innerHTML = `
                <div id="seasons-controls" class="d-flex"></div>
                <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
                <table id="season-splits-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team</th>
                            <th>Season</th>
                            <th colspan="2">Overall Home</th>
                        </tr>
                        <tr><th>W</th><th>L</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>Team</td><td>2025</td><td>1</td><td>0</td></tr>
                    </tbody>
                </table>
            `;

            initSeasons({
                tableSelector: "#season-splits-table",
                controlsSelector: "#seasons-controls",
                panelSelector: "#searchbuilder-panel",
                filterButtonId: "seasons-filter-btn",
                columns: [1, 2, 3, 4],
            });

            const swappedPanel = document.getElementById("searchbuilder-panel");
            expect(
                swappedPanel.querySelector(".dtsb-searchBuilder"),
            ).toBeTruthy();
            expect(document.getElementById("seasons-filter-btn")).toBeTruthy();
        } finally {
            jest.useRealTimers();
        }
    });
});

describe("Seasons init loader", () => {
    let teardown;

    const flushPromises = async (times = 1) => {
        for (let i = 0; i < times; i += 1) {
            await Promise.resolve();
        }
    };

    const setupLoader = async ({ rejectLoader } = {}) => {
        // Don't reset modules - keep setup from beforeEach
        const initSeasonsMock = jest.fn((opts) => {
            return { sb: null, table: null, opts };
        });
        const ensureSearchBuilderLoaded = rejectLoader
            ? jest.fn(() => Promise.reject(new Error("nope")))
            : jest.fn(() => Promise.resolve());

        globalThis.__SEASONS_INIT_LOADER_MOCK__ = initSeasonsMock;
        window.__SEASONS_INIT_LOADER_MOCK__ = initSeasonsMock;
        globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ =
            ensureSearchBuilderLoaded;
        window.__SEASONS_SEARCHBUILDER_LOADER_MOCK__ =
            ensureSearchBuilderLoaded;

        // Import is already done in beforeEach, so mocks are ready
        return { initSeasonsMock, ensureSearchBuilderLoaded };
    };

    beforeEach(() => {
        document.body.innerHTML = "";
        teardown = setupDataTablesMock();
        if (global.$ && !window.$) {
            window.$ = global.$;
        }
        // Import the loader once and keep it
        if (typeof window.__SEASONS_INIT_LOADER_READY__ === "undefined") {
            window.__SEASONS_INIT_LOADER_READY__ =
                import("../../legacy/seasons-init-loader.mjs");
        }
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
        delete globalThis.__SEASONS_INIT_LOADER_MOCK__;
        delete window.__SEASONS_INIT_LOADER_MOCK__;
        delete globalThis.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
        delete window.__SEASONS_SEARCHBUILDER_LOADER_MOCK__;
    });

    test("ignores unrelated turbo frame loads", async () => {
        jest.useFakeTimers();
        try {
            document.body.innerHTML =
                '<turbo-frame id="other-frame"></turbo-frame>';
            const { initSeasonsMock } = await setupLoader();

            document
                .getElementById("other-frame")
                .dispatchEvent(
                    new Event("turbo:frame-load", { bubbles: true }),
                );

            jest.runOnlyPendingTimers();
            await flushPromises(2);

            expect(initSeasonsMock).not.toHaveBeenCalled();
        } finally {
            jest.useRealTimers();
        }
    });

    test("retries until splits table exists and computes columns", async () => {
        jest.useFakeTimers();
        try {
            document.body.innerHTML = `
                <turbo-frame id="seasons-table-frame" data-seasons-view="splits"></turbo-frame>
            `;
            const { initSeasonsMock } = await setupLoader();
            const frame = document.getElementById("seasons-table-frame");

            frame.dispatchEvent(
                new Event("turbo:frame-load", { bubbles: true }),
            );
            await flushPromises(2);
            jest.runOnlyPendingTimers();

            frame.innerHTML = `
                <div id="seasons-controls" class="d-flex"></div>
                <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
                <table id="season-splits-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Team</th>
                            <th>Season</th>
                            <th colspan="2">Overall Home</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `;

            jest.runOnlyPendingTimers();
            await flushPromises(2);

            expect(initSeasonsMock).toHaveBeenCalled();
            const splitCall = initSeasonsMock.mock.calls.find(
                ([opts]) => opts.tableSelector === "#season-splits-table",
            );
            expect(splitCall).toBeTruthy();
            const opts = splitCall[0];
            expect(opts.tableSelector).toBe("#season-splits-table");
            expect(opts.columns).toHaveLength(4);
        } finally {
            jest.useRealTimers();
        }
    });

    test("falls back to init when loader rejects", async () => {
        jest.useFakeTimers();
        try {
            document.body.innerHTML = `
                <turbo-frame id="seasons-table-frame" data-seasons-view="standard">
                    <div id="seasons-controls" class="d-flex"></div>
                    <div id="searchbuilder-panel" class="searchbuilder-panel"></div>
                    <table id="seasons-table">
                        <thead><tr><th>#</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </turbo-frame>
            `;
            const { initSeasonsMock } = await setupLoader({
                rejectLoader: true,
            });

            document
                .getElementById("seasons-table-frame")
                .dispatchEvent(
                    new Event("turbo:frame-load", { bubbles: true }),
                );

            await flushPromises(2);
            jest.runOnlyPendingTimers();
            await flushPromises(2);

            expect(initSeasonsMock).toHaveBeenCalled();
        } finally {
            jest.useRealTimers();
        }
    });
});
