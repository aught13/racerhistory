/** @jest-environment jsdom */

import { jest } from "@jest/globals";
const loadModule = async () => {
    await jest.resetModules();
    return await import("../modules/season-view-init.js");
};

describe("season view init module", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        // clear global jQuery/Turbo
        delete window.$;
        delete window.Turbo;
    });

    test("schedules table init when headers missing", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        root.appendChild(table);

        const mod = await loadModule();

        // use fake timers to control scheduling
        jest.useFakeTimers();

        const run = mod.default?.default || mod.default || mod;
        run({ root });
        // scheduled flag should be set
        const tableEl = root.querySelector("#season-games-table");
        expect(tableEl.dataset.seasonTableInitScheduled).toBe("true");

        // a timer should be scheduled to check headers
        expect(jest.getTimerCount()).toBeGreaterThan(0);
        jest.useRealTimers();
    });

    test("initializes DataTable when headers present and jQuery/DataTables available", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        const thead = document.createElement("thead");
        const tr = document.createElement("tr");
        const th = document.createElement("th");
        tr.appendChild(th);
        thead.appendChild(tr);
        table.appendChild(thead);
        root.appendChild(table);

        // mock jQuery + DataTables
        const dtReturn = { initialized: true };
        const $fn = {
            dataTable: {
                isDataTable: () => false,
            },
        };
        const $ = () => ({ DataTable: jest.fn(() => dtReturn) });
        $.fn = $fn;
        window.$ = $;

        const mod = await loadModule();

        const run2 = mod.default?.default || mod.default || mod;
        const out = run2({ root });
        // should return a tables array with the DataTable return
        expect(out).toHaveProperty("tables");
        expect(out.tables.length).toBeGreaterThan(0);
        expect(out.tables[0]).toBe(dtReturn);
    });

    test("does not reschedule table init when already scheduled", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.dataset.seasonTableInitScheduled = "true";
        root.appendChild(table);

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;

        jest.useFakeTimers();
        run({ root });
        expect(jest.getTimerCount()).toBe(0);
        jest.useRealTimers();
    });

    test("returns null when jQuery missing", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.innerHTML = "<thead><tr><th>H</th></tr></thead>";
        root.appendChild(table);

        delete window.$;

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        const out = run({ root });

        expect(out.tables[0]).toBeNull();
    });

    test("returns null when jQuery.fn missing", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.innerHTML = "<thead><tr><th>H</th></tr></thead>";
        root.appendChild(table);

        const $ = () => ({ DataTable: jest.fn() });
        window.$ = $;

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        const out = run({ root });

        expect(out.tables[0]).toBeNull();
    });

    test("returns null when jQuery.fn.dataTable missing", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.innerHTML = "<thead><tr><th>H</th></tr></thead>";
        root.appendChild(table);

        const $ = () => ({ DataTable: jest.fn() });
        $.fn = {};
        window.$ = $;

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        const out = run({ root });

        expect(out.tables[0]).toBeNull();
    });

    test("destroys existing DataTable before reinitializing", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.innerHTML = "<thead><tr><th>H</th></tr></thead>";
        root.appendChild(table);

        const destroy = jest.fn();
        const dtReturn = { destroy };
        const dataTable = {
            isDataTable: () => true,
        };
        const DataTable = jest.fn(() => dtReturn);
        const $ = () => ({ DataTable });
        $.fn = { dataTable };
        window.$ = $;

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        const out = run({ root });

        expect(destroy).toHaveBeenCalledTimes(1);
        expect(out.tables[0]).toBe(dtReturn);
    });

    test("ignores DataTable destroy errors", async () => {
        const root = document.createElement("div");
        const table = document.createElement("table");
        table.id = "season-games-table";
        table.innerHTML = "<thead><tr><th>H</th></tr></thead>";
        root.appendChild(table);

        const dataTable = {
            isDataTable: () => true,
        };
        const DataTable = jest
            .fn()
            .mockImplementationOnce(() => ({
                destroy: () => {
                    throw new Error("boom");
                },
            }))
            .mockImplementationOnce(() => ({ initialized: true }));
        const $ = () => ({ DataTable });
        $.fn = { dataTable };
        window.$ = $;

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        const out = run({ root });

        expect(out.tables[0]).toEqual({ initialized: true });
    });

    test("sets up blog clicks and uses Turbo.visit when available", async () => {
        const root = document.createElement("div");
        const section = document.createElement("section");
        section.setAttribute("data-season-blog", "1");

        const container = document.createElement("turbo-frame");
        const viewFrame = document.createElement("turbo-frame");
        viewFrame.setAttribute("data-view-frame", "true");
        viewFrame.id = "vf";

        const item = document.createElement("div");
        item.className = "blog-list-item";
        item.dataset.blogPost = "post-slug";

        container.appendChild(item);
        container.appendChild(viewFrame);
        section.appendChild(container);
        root.appendChild(section);

        // mock Turbo
        const visitMock = jest.fn();
        window.Turbo = { visit: visitMock };

        const mod = await loadModule();

        const run3 = mod.default?.default || mod.default || mod;
        run3({ root });

        // click the item
        item.dispatchEvent(new Event("click", { bubbles: true }));

        expect(visitMock).toHaveBeenCalled();
        const calledWith = visitMock.mock.calls[0][0];
        expect(calledWith).toContain("/blog/post-slug");
    });

    test("skips blog click setup when already bound", async () => {
        const section = document.createElement("section");
        section.setAttribute("data-season-blog", "1");
        section.dataset.blogRootBound = "true";
        document.body.appendChild(section);

        const addSpy = jest.spyOn(section, "addEventListener");
        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        run({ root: document });

        expect(addSpy).not.toHaveBeenCalled();
    });

    test("blog click handler ignores invalid targets and missing slug", async () => {
        const section = document.createElement("section");
        section.setAttribute("data-season-blog", "1");
        document.body.appendChild(section);

        let handler = null;
        section.addEventListener = jest.fn((type, cb) => {
            handler = cb;
        });

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        run({ root: document });

        const originalLocation = window.location;
        Object.defineProperty(window, "location", {
            value: { href: "http://localhost/" },
            configurable: true,
        });

        handler({ target: {} });
        const externalItem = document.createElement("div");
        externalItem.className = "blog-list-item";
        externalItem.dataset.blogPost = "outside";
        handler({ target: externalItem });
        const insideItem = document.createElement("div");
        insideItem.className = "blog-list-item";
        section.appendChild(insideItem);
        handler({ target: insideItem });

        expect(window.location.href).toBe("http://localhost/");

        Object.defineProperty(window, "location", {
            value: originalLocation,
            configurable: true,
        });
    });

    test("blog click handler falls back to location when view frame missing", async () => {
        const section = document.createElement("section");
        section.setAttribute("data-season-blog", "1");
        document.body.appendChild(section);

        let handler = null;
        section.addEventListener = jest.fn((type, cb) => {
            handler = cb;
        });

        const item = document.createElement("div");
        item.className = "blog-list-item";
        item.dataset.blogPost = "missing";
        const frame = document.createElement("turbo-frame");
        frame.appendChild(item);
        section.appendChild(frame);

        const originalLocation = window.location;
        Object.defineProperty(window, "location", {
            value: { href: "http://localhost/" },
            configurable: true,
        });

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        run({ root: document });

        handler({ target: item });
        expect(window.location.href).toContain("/blog/missing");

        Object.defineProperty(window, "location", {
            value: originalLocation,
            configurable: true,
        });
    });

    test("blog click handler falls back when Turbo missing", async () => {
        const section = document.createElement("section");
        section.setAttribute("data-season-blog", "1");
        document.body.appendChild(section);

        let handler = null;
        section.addEventListener = jest.fn((type, cb) => {
            handler = cb;
        });

        const item = document.createElement("div");
        item.className = "blog-list-item";
        item.dataset.blogPost = "alpha";
        const frame = document.createElement("turbo-frame");
        const viewFrame = document.createElement("turbo-frame");
        viewFrame.setAttribute("data-view-frame", "true");
        viewFrame.id = "view";
        frame.appendChild(item);
        frame.appendChild(viewFrame);
        section.appendChild(frame);

        delete window.Turbo;

        const originalLocation = window.location;
        Object.defineProperty(window, "location", {
            value: { href: "http://localhost/" },
            configurable: true,
        });

        const mod = await loadModule();
        const run = mod.default?.default || mod.default || mod;
        run({ root: document });

        handler({ target: item });
        expect(window.location.href).toContain("/blog/alpha");

        Object.defineProperty(window, "location", {
            value: originalLocation,
            configurable: true,
        });
    });
});
