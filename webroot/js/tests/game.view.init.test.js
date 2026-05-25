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
import initGameView from "../modules/game-view-init.mjs";

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

    test("opens image modal when gallery is clicked", () => {
        initGameView();
        const img = document.querySelector(".game-photo-thumb-img");
        img.dispatchEvent(
            new MouseEvent("click", { bubbles: true, cancelable: true }),
        );

        const modal = document.querySelector("[data-game-image-modal]");
        expect(modal?.getAttribute("data-modal-open")).toBe("true");
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
