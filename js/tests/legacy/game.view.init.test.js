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
import initGameView from "../../legacy/modules/game-view-init.mjs";

describe("Game view init", () => {
    let teardown;

    beforeEach(() => {
        document.body.innerHTML = `
            <table id="game-team-stats-table">
                <thead><tr><th>Player</th></tr></thead>
            </table>
            <table id="game-opponent-stats-table">
                <thead><tr><th>Player</th></tr></thead>
            </table>
            <table id="game-team-summary-table">
                <thead><tr><th>Summary</th></tr></thead>
            </table>
            <div data-game-blog>
                <turbo-frame id="blog-post-sample">
                    <div class="blog-list-item" data-blog-post="sample"></div>
                    <turbo-frame id="blog-post-view-sample" data-view-frame></turbo-frame>
                </turbo-frame>
            </div>
            <div data-game-image-gallery>
                <img class="game-photo-thumb-img" data-image-url="/img/storage/2026/05/sample.jpg" data-image-filename="sample.jpg" />
            </div>
            <div data-game-image-modal>
                <button data-modal-close></button>
                <source data-modal-image-webp />
                <img data-modal-image-fallback />
            </div>
        `;
        teardown = setupDataTablesMock();
        window.Turbo = { visit: jest.fn() };
    });

    afterEach(() => {
        if (typeof teardown === "function") teardown();
        delete window.Turbo;
    });

    test("initializes DataTables and binds blog clicks", () => {
        initGameView();
        expect((global.__datatableCalls || []).length).toBe(2);

        const blogItem = document.querySelector(".blog-list-item");
        blogItem.dispatchEvent(
            new MouseEvent("click", { bubbles: true, cancelable: true }),
        );

        expect(window.Turbo.visit).toHaveBeenCalled();
        const call = window.Turbo.visit.mock.calls[0];
        expect(call[0]).toBe("/blog/sample");
        const frameArg = call[1] && call[1].frame;
        if (typeof frameArg === "string") {
            expect(frameArg).toBe("blog-post-view-sample");
        } else {
            expect(frameArg.id).toBe("blog-post-view-sample");
        }
    });

    test("initially sorts game box score tables by starts and points", () => {
        ["game-team-stats-table", "game-opponent-stats-table"].forEach((id) => {
            document.getElementById(id).innerHTML = `
                <thead><tr><th>Name</th><th>GS</th><th>PTS</th></tr></thead>`;
        });

        initGameView();

        expect(global.__datatableCalls[0].opts.order).toEqual([
            [1, "desc"],
            [2, "desc"],
        ]);
        expect(global.__datatableCalls[1].opts.order).toEqual([
            [1, "desc"],
            [2, "desc"],
        ]);
    });

    test("initializes advanced box score tables when the tab is shown", () => {
        document.body.insertAdjacentHTML(
            "beforeend",
            `
                <div data-game-box-score-tabs>
                    <button class="active" data-game-stats-tab="basic" aria-selected="true"></button>
                    <button data-game-stats-tab="advanced" aria-selected="false"></button>
                    <div class="tab-pane show active" data-game-stats-panel="basic"></div>
                    <div class="tab-pane d-none" data-game-stats-panel="advanced">
                        <table class="js-datatable" id="game-team-advanced-stats-table">
                            <thead><tr><th>Name</th><th>GS</th><th>PProd</th></tr></thead>
                        </table>
                        <table class="js-datatable" id="game-opponent-advanced-stats-table">
                            <thead><tr><th>Name</th><th>GS</th><th>PProd</th></tr></thead>
                        </table>
                    </div>
                </div>
            `,
        );

        initGameView();
        expect((global.__datatableCalls || []).length).toBe(2);

        const advancedTab = document.querySelector(
            '[data-game-stats-tab="advanced"]',
        );
        advancedTab.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect((global.__datatableCalls || []).length).toBe(4);
        expect(global.__datatableCalls[2].opts.order).toEqual([
            [1, "desc"],
            [2, "desc"],
        ]);
        expect(global.__datatableCalls[3].opts.order).toEqual([
            [1, "desc"],
            [2, "desc"],
        ]);
        expect(advancedTab.getAttribute("aria-selected")).toBe("true");
        expect(
            document
                .querySelector('[data-game-stats-panel="advanced"]')
                .classList.contains("active"),
        ).toBe(true);

        advancedTab.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect((global.__datatableCalls || []).length).toBe(4);
    });

    test("opens image modal when gallery is clicked", () => {
        initGameView();
        const img = document.querySelector(".game-photo-thumb-img");
        img.dispatchEvent(
            new MouseEvent("click", { bubbles: true, cancelable: true }),
        );

        expect(
            document.querySelector("[data-modal-image-fallback]")?.src,
        ).toContain("/img/storage/2026/05/sample.jpg");
    });

    test("defers table initialization until headers exist", () => {
        jest.useFakeTimers();
        try {
            const statsTable = document.querySelector("#game-team-stats-table");
            statsTable.innerHTML = "<tbody></tbody>";

            initGameView();
            expect((global.__datatableCalls || []).length).toBe(1);

            statsTable.innerHTML = "<thead><tr><th>Player</th></tr></thead>";
            jest.runOnlyPendingTimers();

            expect((global.__datatableCalls || []).length).toBe(2);
        } finally {
            jest.useRealTimers();
        }
    });
});
