/** @jest-environment jsdom */

import { jest } from "@jest/globals";
import setupDataTablesMock from "./helpers/datatables.mock.mjs";
import initSeasonView from "../modules/season-view-init.mjs";

describe("Season view init", () => {
    let teardown;

    beforeEach(() => {
        document.body.innerHTML = `
      <table id="season-games-table"></table>
      <table id="season-roster-table"></table>
      <table id="season-stats-table"></table>
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

    test("initializes DataTables and binds blog clicks", () => {
      initSeasonView();
      // initSeasonView may return undefined; we only exercise side effects
        expect((global.__datatableCalls || []).length).toBe(3);

        const blogItem = document.querySelector(".blog-list-item");
        // ensure the click event bubbles so delegated handlers are invoked in jsdom
        blogItem.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true }));

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
    });
});
