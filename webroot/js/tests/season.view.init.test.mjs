/* eslint-env jest, browser */
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
        const result = initSeasonView();
        expect(result).toBeTruthy();
        expect(global.__datatableCalls.length).toBe(3);

        const blogItem = document.querySelector(".blog-list-item");
        blogItem.click();

        expect(window.Turbo.visit).toHaveBeenCalledWith("/blog/sample", {
            frame: "blog-post-view-sample",
        });
    });
});
