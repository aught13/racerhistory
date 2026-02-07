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
import initSeasonView from "../modules/season-view-init.mjs";

describe("Season view init", () => {
    let teardown;

    beforeEach(() => {
        const advancedPayload = {
            players: [
                {
                    name: "Guard",
                    GP: 12,
                    FGM: 56,
                    FGA: 120,
                    TPM: 10,
                    TPA: 30,
                    FTM: 40,
                    FTA: 50,
                    PTS: 180,
                },
            ],
            teamTotals: {
                name: "Team Totals",
                GP: 30,
                FGM: 120,
                FGA: 260,
                TPM: 35,
                TPA: 80,
                FTM: 50,
                FTA: 70,
                PTS: 500,
            },
        };
        const advancedJson = JSON.stringify(advancedPayload);
        document.body.innerHTML = `
            <table id="season-games-table">
                <thead>
                    <tr><th>Game</th></tr>
                </thead>
            </table>
            <table id="season-roster-table">
                <thead>
                    <tr><th>Player</th></tr>
                </thead>
            </table>
            <div data-season-stats-tabs>
                <div class="nav nav-tabs" role="tablist">
                    <button class="nav-link active" type="button" data-season-stats-tab="general" aria-selected="true">General Stats</button>
                    <button class="nav-link" type="button" data-season-stats-tab="advanced" aria-selected="false">Advanced Shooting</button>
                </div>
                <div class="tab-content">
                    <div class="tab-pane active" data-season-stats-panel="general">
                        <table id="season-stats-table">
                            <thead>
                                <tr><th>#</th></tr>
                            </thead>
                        </table>
                    </div>
                    <div class="tab-pane d-none" data-season-stats-panel="advanced" data-season-advanced-stats='${advancedJson}'>
                        <div data-season-advanced-table-container>
                            <p class="text-muted mb-0" data-season-advanced-placeholder>Loading advanced stats...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div data-season-blog>
                <turbo-frame id="blog-post-sample">
                    <div class="blog-list-item" data-blog-post="sample"></div>
                    <turbo-frame id="blog-post-view-sample" data-view-frame></turbo-frame>
                </turbo-frame>
            </div>
        `;
        teardown = setupDataTablesMock();
        window.Turbo = { visit: jest.fn() };
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
        delete window.Turbo;
    });

    test("initializes DataTables, binds blog clicks, and renders advanced shooting tab", () => {
        initSeasonView();
        // initSeasonView may return undefined; we only exercise side effects
        expect((global.__datatableCalls || []).length).toBe(3);

        const blogItem = document.querySelector(".blog-list-item");
        // ensure the click event bubbles so delegated handlers are invoked in jsdom
        blogItem.dispatchEvent(
            new MouseEvent("click", { bubbles: true, cancelable: true }),
        );

        expect(window.Turbo.visit).toHaveBeenCalled();
        const call = window.Turbo.visit.mock.calls[0];
        expect(call[0]).toBe("/blog/sample");
        const frameArg = call[1] && call[1].frame;
        expect(frameArg).toBeTruthy();
        if (typeof frameArg === "string") {
            expect(frameArg).toBe("blog-post-view-sample");
        } else {
            expect(frameArg.id).toBe("blog-post-view-sample");
        }

        const advancedTab = document.querySelector(
            "[data-season-stats-tab='advanced']",
        );
        advancedTab?.dispatchEvent(
            new MouseEvent("click", { bubbles: true, cancelable: true }),
        );
        expect((global.__datatableCalls || []).length).toBe(4);
        const advancedContainer = document.querySelector(
            "[data-season-advanced-table-container]",
        );
        expect(advancedContainer?.querySelector("table")).toBeTruthy();
    });

    test("defers stats table initialization until headers exist", () => {
        jest.useFakeTimers();
        try {
            const statsTable = document.querySelector("#season-stats-table");
            if (!statsTable) {
                throw new Error("stats table missing in fixture");
            }
            statsTable.innerHTML = "<tbody></tbody>";

            initSeasonView();
            expect((global.__datatableCalls || []).length).toBe(2);

            statsTable.innerHTML = `
                <thead>
                    <tr><th>#</th><th>Player</th></tr>
                </thead>
                <tbody></tbody>
            `;
            jest.runOnlyPendingTimers();

            expect((global.__datatableCalls || []).length).toBe(3);
        } finally {
            jest.useRealTimers();
        }
    });
});
