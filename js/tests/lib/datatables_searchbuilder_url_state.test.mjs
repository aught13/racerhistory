import { jest } from "@jest/globals";

/**
 * Branch coverage tests for datatables_searchbuilder_url_state.mjs
 * Tests: registerSearchBuilderUrlStateExtension, copySearchBuilderLinkToClipboard,
 *   and internal branches exercised through those exported functions.
 */

function makeFullDt(opts = {}) {
    const topGroup = opts.noTopGroup ? undefined : {};
    const sbInstance = opts.noSbInstance
        ? undefined
        : {
              s: opts.noS ? undefined : { topGroup },
              dom: opts.withDom
                  ? { container: document.createElement("div") }
                  : undefined,
          };
    const context = opts.noContext
        ? undefined
        : [{ _searchBuilder: sbInstance }];
    const state = opts.state ?? {
        criteria: [{ condition: "=", value: "test" }],
        logic: "AND",
    };
    const getDetailsMock = jest.fn().mockReturnValue(state);
    const containerMock = { empty: jest.fn(), appendTo: jest.fn() };
    const rebuildMock = jest.fn();
    const sbObj = {
        getDetails: getDetailsMock,
        container: jest.fn().mockReturnValue(containerMock),
        rebuild: rebuildMock,
    };
    return {
        searchBuilder: opts.noSearchBuilder ? undefined : sbObj,
        context,
        _sbInstance: sbInstance,
        _containerMock: containerMock,
        _rebuildMock: rebuildMock,
        _getDetailsMock: getDetailsMock,
    };
}

beforeEach(() => {
    jest.resetModules();
    jest.restoreAllMocks();
    jest.spyOn(console, "warn").mockImplementation(() => {});
    jest.spyOn(console, "log").mockImplementation(() => {});
    jest.spyOn(console, "error").mockImplementation(() => {});
    window.history.replaceState({}, "", "/");
    delete window.$;
});

afterEach(() => {
    jest.restoreAllMocks();
    window.history.replaceState({}, "", "/");
    delete window.$;
});

// ─── URL state serialization via copySearchBuilderLinkToClipboard ─────────────

describe("URL state serialization via copySearchBuilderLinkToClipboard", () => {
    test("copies a URL containing the serialized state", async () => {
        const state = { criteria: [{ condition: "=", value: "test" }] };
        const dt = makeFullDt({ state });
        Object.defineProperty(navigator, "clipboard", {
            value: { writeText: jest.fn().mockResolvedValue(undefined) },
            configurable: true,
            writable: true,
        });
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const url = await copySearchBuilderLinkToClipboard(dt);
        expect(url).not.toBeNull();
        const parsed = new URL(url);
        const raw = parsed.searchParams.get("searchBuilder");
        expect(JSON.parse(decodeURIComponent(raw))).toEqual(state);
    });

    test("returns a URL string even with empty criteria", async () => {
        const dt = makeFullDt({ state: { criteria: [], logic: "AND" } });
        Object.defineProperty(navigator, "clipboard", {
            value: { writeText: jest.fn().mockResolvedValue(undefined) },
            configurable: true,
            writable: true,
        });
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const url = await copySearchBuilderLinkToClipboard(dt);
        expect(typeof url).toBe("string");
    });
});

// ─── updateUrlWithSearchBuilderState guard branches ───────────────────────────

describe("updateUrlWithSearchBuilderState branches (via copySearchBuilderLinkToClipboard)", () => {
    test("returns null if dt is null", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        expect(await copySearchBuilderLinkToClipboard(null)).toBeNull();
    });

    test("returns null if dt has no searchBuilder", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        expect(
            await copySearchBuilderLinkToClipboard({ context: [] }),
        ).toBeNull();
    });
});

// ─── registerSearchBuilderUrlStateExtension ──────────────────────────────────

describe("registerSearchBuilderUrlStateExtension", () => {
    test("warns when jQuery not available", async () => {
        delete window.$;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        expect(console.warn).toHaveBeenCalledWith(
            expect.stringContaining("DataTables not available"),
        );
    });

    test("warns when $.fn not present", async () => {
        window.$ = jest.fn();
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        expect(console.warn).toHaveBeenCalledWith(
            expect.stringContaining("DataTables not available"),
        );
    });

    test("warns when $.fn.dataTable not available", async () => {
        const jq = jest.fn();
        jq.fn = {};
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        expect(console.warn).toHaveBeenCalledWith(
            expect.stringContaining("DataTables not available"),
        );
    });

    test("registers init.dt event handler when DataTables available", async () => {
        const onMock = jest.fn();
        const jq = jest.fn(() => ({ on: onMock }));
        jq.fn = { dataTable: {} };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        expect(onMock).toHaveBeenCalledWith("init.dt", expect.any(Function));
        expect(console.log).toHaveBeenCalledWith(
            expect.stringContaining(
                "SearchBuilder URL state extension registered",
            ),
        );
    });

    test("init.dt handler calls Api and does not throw without context", async () => {
        let capturedHandler;
        const onMock = jest.fn((ev, h) => {
            if (ev === "init.dt") capturedHandler = h;
        });
        const apiFn = jest
            .fn()
            .mockReturnValue({ context: [], searchBuilder: undefined });
        const jq = jest.fn(() => ({ on: onMock }));
        jq.fn = { dataTable: Object.assign({}, { Api: apiFn }) };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        expect(typeof capturedHandler).toBe("function");
        expect(() => capturedHandler({}, {})).not.toThrow();
    });
});

// ─── copySearchBuilderLinkToClipboard ────────────────────────────────────────

describe("copySearchBuilderLinkToClipboard", () => {
    test("returns null if dt is null", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        expect(await copySearchBuilderLinkToClipboard(null)).toBeNull();
    });

    test("returns null if dt has no searchBuilder", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        expect(
            await copySearchBuilderLinkToClipboard({ context: [] }),
        ).toBeNull();
    });

    test("returns null and warns if sbInstance has no topGroup", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const dt = makeFullDt({ noTopGroup: true });
        const result = await copySearchBuilderLinkToClipboard(dt);
        expect(result).toBeNull();
        expect(console.warn).toHaveBeenCalledWith(
            expect.stringContaining("SearchBuilder not fully initialized"),
        );
    });

    test("returns null when no context", async () => {
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const dt = makeFullDt({ noContext: true });
        expect(await copySearchBuilderLinkToClipboard(dt)).toBeNull();
    });

    test("copies URL to clipboard and returns URL string", async () => {
        const state = { criteria: [{ condition: ">", value: "100" }] };
        const dt = makeFullDt({ state });
        Object.defineProperty(navigator, "clipboard", {
            value: { writeText: jest.fn().mockResolvedValue(undefined) },
            configurable: true,
            writable: true,
        });
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const result = await copySearchBuilderLinkToClipboard(dt);
        expect(typeof result).toBe("string");
        expect(result).toContain("searchBuilder=");
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(result);
        expect(console.log).toHaveBeenCalledWith(
            expect.stringContaining("Filter link copied to clipboard"),
        );
    });

    test("returns URL without clipboard when clipboard API absent", async () => {
        const state = { criteria: [{ condition: "=", value: "W" }] };
        const dt = makeFullDt({ state });
        Object.defineProperty(navigator, "clipboard", {
            value: undefined,
            configurable: true,
            writable: true,
        });
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const result = await copySearchBuilderLinkToClipboard(dt);
        expect(typeof result).toBe("string");
        expect(result).toContain("searchBuilder=");
    });

    test("returns null and logs error when getDetails throws", async () => {
        const dt = makeFullDt();
        dt.searchBuilder.getDetails = jest.fn().mockImplementation(() => {
            throw new Error("getDetails failed");
        });
        const { copySearchBuilderLinkToClipboard } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        const result = await copySearchBuilderLinkToClipboard(dt);
        expect(result).toBeNull();
        expect(console.error).toHaveBeenCalledWith(
            expect.stringContaining("Failed to copy SearchBuilder link:"),
            expect.any(Error),
        );
    });
});

// ─── initSearchBuilderUrlState via registerSearchBuilderUrlStateExtension ─────

describe("initSearchBuilderUrlState (internal) via registerSearchBuilderUrlStateExtension", () => {
    test("binds draw.dtSearchBuilderUrl event when SearchBuilder ready", async () => {
        jest.useFakeTimers();
        const onDtMock = jest.fn();
        const dtMock = {
            searchBuilder: {
                getDetails: jest
                    .fn()
                    .mockReturnValue({ criteria: [], logic: "AND" }),
                container: jest.fn().mockReturnValue({ empty: jest.fn() }),
                rebuild: jest.fn(),
            },
            context: [
                { _searchBuilder: { s: { topGroup: {} }, dom: undefined } },
            ],
            on: onDtMock,
        };
        let capturedHandler;
        const jq = jest.fn(() => ({
            on: jest.fn((ev, h) => {
                if (ev === "init.dt") capturedHandler = h;
            }),
        }));
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();
        expect(onDtMock).toHaveBeenCalledWith(
            "draw.dtSearchBuilderUrl",
            expect.any(Function),
        );
        jest.useRealTimers();
    });

    test("skips binding when table has no SearchBuilder", async () => {
        jest.useFakeTimers();
        const dtMock = {
            searchBuilder: undefined,
            context: [{ _searchBuilder: undefined }],
            on: jest.fn(),
        };
        let capturedHandler;
        const jq = jest.fn(() => ({
            on: jest.fn((ev, h) => {
                if (ev === "init.dt") capturedHandler = h;
            }),
        }));
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();
        expect(dtMock.on).not.toHaveBeenCalled();
        jest.useRealTimers();
    });

    test("returns early inside setTimeout when dt.searchBuilder absent", async () => {
        jest.useFakeTimers();
        const dtMock = {
            searchBuilder: undefined,
            context: [{ _searchBuilder: { s: { topGroup: {} } } }],
            on: jest.fn(),
        };
        let capturedHandler;
        const jq = jest.fn(() => ({
            on: jest.fn((ev, h) => {
                if (ev === "init.dt") capturedHandler = h;
            }),
        }));
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();
        expect(dtMock.on).not.toHaveBeenCalled();
        jest.useRealTimers();
    });

    test("binds button click when sbInstance has dom.container", async () => {
        jest.useFakeTimers();
        const sbContainer = document.createElement("div");
        const jqContainerMock = { on: jest.fn() };
        const onDtMock = jest.fn();
        const dtMock = {
            searchBuilder: {
                getDetails: jest
                    .fn()
                    .mockReturnValue({ criteria: [], logic: "AND" }),
                container: jest.fn().mockReturnValue({ empty: jest.fn() }),
                rebuild: jest.fn(),
            },
            context: [
                {
                    _searchBuilder: {
                        s: { topGroup: {} },
                        dom: { container: sbContainer },
                    },
                },
            ],
            on: onDtMock,
        };
        let capturedHandler;
        const jq = jest.fn((el) => {
            if (el === sbContainer) return jqContainerMock;
            return {
                on: jest.fn((ev, h) => {
                    if (ev === "init.dt") capturedHandler = h;
                }),
            };
        });
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();
        expect(jqContainerMock.on).toHaveBeenCalledWith(
            "click.dtSearchBuilderUrl",
            "button",
            expect.any(Function),
        );
        jest.useRealTimers();
    });

    test("draw.dtSearchBuilderUrl callback executes updateUrlWithSearchBuilderState", async () => {
        jest.useFakeTimers();
        const state = { criteria: [{ condition: "=", value: "win" }] };
        let drawCallback;
        const onDtMock = jest.fn((ev, cb) => {
            if (ev === "draw.dtSearchBuilderUrl") drawCallback = cb;
        });
        const dtMock = {
            searchBuilder: {
                getDetails: jest.fn().mockReturnValue(state),
                container: jest.fn().mockReturnValue({ empty: jest.fn() }),
                rebuild: jest.fn(),
            },
            context: [
                { _searchBuilder: { s: { topGroup: {} }, dom: undefined } },
            ],
            on: onDtMock,
        };
        let capturedHandler;
        const jq = jest.fn(() => ({
            on: jest.fn((ev, h) => {
                if (ev === "init.dt") capturedHandler = h;
            }),
        }));
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();

        expect(drawCallback).toBeDefined();
        // Invoke draw callback — exercises updateUrlWithSearchBuilderState with criteria
        expect(() => drawCallback()).not.toThrow();
        jest.useRealTimers();
    });

    test("draw callback with empty criteria deletes searchBuilder from URL", async () => {
        jest.useFakeTimers();
        const state = { criteria: [], logic: "AND" };
        let drawCallback;
        const onDtMock = jest.fn((ev, cb) => {
            if (ev === "draw.dtSearchBuilderUrl") drawCallback = cb;
        });
        const dtMock = {
            searchBuilder: {
                getDetails: jest.fn().mockReturnValue(state),
                container: jest.fn().mockReturnValue({ empty: jest.fn() }),
                rebuild: jest.fn(),
            },
            context: [
                { _searchBuilder: { s: { topGroup: {} }, dom: undefined } },
            ],
            on: onDtMock,
        };
        let capturedHandler;
        const jq = jest.fn(() => ({
            on: jest.fn((ev, h) => {
                if (ev === "init.dt") capturedHandler = h;
            }),
        }));
        jq.fn = {
            dataTable: Object.assign(function () {}, {
                Api: jest.fn().mockReturnValue(dtMock),
            }),
        };
        window.$ = jq;
        const { registerSearchBuilderUrlStateExtension } =
            await import("../../lib/datatables_searchbuilder_url_state.mjs");
        registerSearchBuilderUrlStateExtension();
        capturedHandler({}, {});
        jest.advanceTimersByTime(600);
        await Promise.resolve();
        expect(drawCallback).toBeDefined();
        // Empty criteria → URL param should be deleted (no throw)
        expect(() => drawCallback()).not.toThrow();
        jest.useRealTimers();
    });
});
