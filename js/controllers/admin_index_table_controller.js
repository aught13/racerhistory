import { Controller } from "@hotwired/stimulus";

const SEARCH_DEBOUNCE_MS = 250;
const RETRY_DELAY_MS = 200;
const MAX_RETRIES = 60;

const PAGE_CONFIGS = {
    "opponents-table": {
        labelPlural: "opponents",
        searchPlaceholder: "Name, short, abbr, or place...",
        serverSide: true,
        order: [[0, "asc"]],
        pageLength: 50,
        lengthMenu: [25, 50, 100, 250],
        scrollY: "60vh",
        scrollCollapse: true,
        scroller: true,
        deferRender: true,
        columns: [
            { data: "name", name: "name" },
            { data: "short", name: "short" },
            { data: "abbr", name: "abbr" },
            { data: "place", name: "place" },
            {
                data: "actions",
                name: "actions",
                orderable: false,
                searchable: false,
            },
        ],
    },
    "places-table": {
        labelPlural: "places",
        searchPlaceholder: "Country, city, or state...",
        serverSide: true,
        order: [[0, "asc"]],
        pageLength: 50,
        lengthMenu: [25, 50, 100, 250],
        scrollY: "60vh",
        scrollCollapse: true,
        scroller: true,
        deferRender: true,
        columns: [
            { data: "country", name: "country" },
            { data: "city", name: "city" },
            { data: "state", name: "state" },
            {
                data: "actions",
                name: "actions",
                orderable: false,
                searchable: false,
            },
        ],
    },
    "sites-table": {
        labelPlural: "sites",
        searchPlaceholder: "Name or place...",
        serverSide: true,
        order: [[0, "asc"]],
        pageLength: 50,
        lengthMenu: [25, 50, 100, 250],
        scrollY: "60vh",
        scrollCollapse: true,
        scroller: true,
        deferRender: true,
        columns: [
            { data: "name", name: "name" },
            { data: "place", name: "place" },
            { data: "capacity", name: "capacity" },
            {
                data: "actions",
                name: "actions",
                orderable: false,
                searchable: false,
            },
        ],
    },
    "game-types-table": {
        labelPlural: "game types",
        searchPlaceholder: "Name or abbreviation...",
        serverSide: false,
        order: [[0, "asc"]],
        pageLength: 25,
        lengthMenu: [25, 50, 100],
        scrollCollapse: true,
        deferRender: true,
    },
    "images-table": {
        labelPlural: "images",
        searchPlaceholder: "Name, mime, status, id...",
        serverSide: true,
        order: [[0, "desc"]],
        pageLength: 15,
        lengthMenu: [15, 30, 45],
        scrollY: "60vh",
        scrollX: true,
        scrollCollapse: true,
        deferRender: true,
        columns: [
            { data: "id", name: "id" },
            {
                data: "preview",
                name: "preview",
                orderable: false,
                searchable: false,
            },
            { data: "original_name", name: "original_name" },
            { data: "mime", name: "mime" },
            { data: "size", name: "size" },
            { data: "dimensions", name: "dimensions" },
            { data: "status", name: "status" },
            {
                data: "actions",
                name: "actions",
                orderable: false,
                searchable: false,
            },
        ],
    },
    "team-seasons-table": {
        labelPlural: "team seasons",
        searchPlaceholder: "Team, season, semester, league...",
        serverSide: false,
        order: [[1, "desc"]],
        pageLength: 15,
        lengthMenu: [15, 30, 45],
        scrollX: true,
        scrollCollapse: true,
        deferRender: true,
    },
};

export default class extends Controller {
    static targets = ["table", "searchInput"];

    connect() {
        this.dtInstance = null;
        this.searchDebounce = null;
        this.initTimer = null;
        this.retryCount = 0;
        this.retryTimer = null;
        this.boundSearchInput = () => this.handleSearchInput();

        if (this.hasSearchInputTarget && this.pageConfig) {
            this.searchInputTarget.addEventListener(
                "input",
                this.boundSearchInput,
            );
            this.searchInputTarget.placeholder =
                this.pageConfig.searchPlaceholder;
        }

        this.initTimer = window.setTimeout(() => {
            this.initTimer = null;
            this.retryCount = 0;
            this.initWhenReady();
        }, 0);
    }

    disconnect() {
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.removeEventListener(
                "input",
                this.boundSearchInput,
            );
        }

        if (this.initTimer) {
            window.clearTimeout(this.initTimer);
            this.initTimer = null;
        }

        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }

        if (this.retryTimer) {
            window.clearTimeout(this.retryTimer);
            this.retryTimer = null;
        }

        this.destroyTable();
    }

    get pageConfig() {
        if (!this.hasTableTarget) {
            return null;
        }

        return PAGE_CONFIGS[this.tableTarget.id] || null;
    }

    jQueryHandle() {
        return window.jQuery || window.$ || null;
    }

    isDataTablesAvailable() {
        const jq = this.jQueryHandle();
        return Boolean(jq && jq.fn && typeof jq.fn.DataTable === "function");
    }

    hasRequiredExtensions(config) {
        if (!config?.scroller) {
            return true;
        }

        const jq = this.jQueryHandle();
        const namespace = jq?.fn?.dataTable;
        return Boolean(namespace?.Scroller);
    }

    initWhenReady() {
        const config = this.pageConfig;
        if (!this.hasTableTarget || !config) {
            return;
        }

        if (!this.isDataTablesAvailable()) {
            if (this.retryCount >= MAX_RETRIES) {
                return;
            }

            this.retryCount += 1;
            this.retryTimer = window.setTimeout(
                () => this.initWhenReady(),
                RETRY_DELAY_MS,
            );
            return;
        }

        this.initTable();
    }

    destroyTable() {
        if (!this.dtInstance) {
            return;
        }

        try {
            const settings = this.dtInstance.settings?.()[0];
            const request = settings?.jqXHR;
            if (request && request.readyState !== 4) {
                request.abort();
            }
        } catch {
            // no-op: request may already be finalized
        }

        try {
            this.dtInstance.destroy(false);
        } catch {
            // no-op: stale instance during Turbo navigation
        }

        this.dtInstance = null;
    }

    initTable() {
        const jq = this.jQueryHandle();
        const config = this.pageConfig;

        if (
            !jq ||
            typeof jq !== "function" ||
            !this.hasTableTarget ||
            !config
        ) {
            return;
        }

        const isDataTableFn = jq.fn?.DataTable?.isDataTable;
        if (
            typeof isDataTableFn === "function" &&
            isDataTableFn(this.tableTarget)
        ) {
            this.dtInstance = jq(this.tableTarget).DataTable();
            return;
        }

        const supportsServerSide = config.serverSide !== false;
        const options = {
            order: config.order,
            pageLength: config.pageLength ?? 50,
            lengthMenu: config.lengthMenu ?? [25, 50, 100, 250],
            paging: true,
            pagingType: "simple_numbers",
            scrollCollapse: config.scrollCollapse ?? true,
            deferRender: config.deferRender ?? true,
            dom: config.dom ?? "rltip",
            drawCallback: function () {
                const api = this.api();
                const pages = api?.page?.info?.()?.pages;
                const wrapper = jq(api.table().container());
                const pagination = wrapper.find(".dataTables_paginate");

                if (!Number.isFinite(pages) || pages <= 1) {
                    pagination.hide();
                } else {
                    pagination.show();
                }
            },
        };

        if (config.scrollY) {
            options.scrollY = config.scrollY;
        }
        if (config.scrollX) {
            options.scrollX = true;
        }
        if (config.scroller) {
            if (this.hasRequiredExtensions(config)) {
                options.scroller = config.scroller;
            }
        }

        if (supportsServerSide) {
            const dataUrl = this.tableTarget.dataset.datatablesUrl;
            if (!dataUrl) {
                return;
            }

            options.serverSide = true;
            options.processing = true;
            options.ajax = {
                url: dataUrl,
                type: "GET",
            };
            options.columns = config.columns;
            options.language = {
                processing:
                    '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...',
                search: "",
                zeroRecords: `No matching ${config.labelPlural} found.`,
                info: `Showing _START_ to _END_ of _TOTAL_ ${config.labelPlural}`,
                infoEmpty: `No ${config.labelPlural} found.`,
                infoFiltered: `(filtered from _MAX_ total ${config.labelPlural})`,
            };
        }

        this.dtInstance = jq(this.tableTarget).DataTable(options);
    }

    handleSearchInput() {
        if (!this.dtInstance || !this.hasSearchInputTarget) {
            return;
        }

        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
        }

        this.searchDebounce = window.setTimeout(() => {
            if (this.dtInstance) {
                this.dtInstance.search(this.searchInputTarget.value).draw();
            }
        }, SEARCH_DEBOUNCE_MS);
    }
}
