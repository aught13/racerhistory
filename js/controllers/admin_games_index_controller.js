import { Controller } from "@hotwired/stimulus";

const RETRY_DELAY_MS = 200;
const MAX_RETRIES = 60;

export default class extends Controller {
    static targets = [
        "table",
        "bulkForm",
        "selectAll",
        "actionSelect",
        "actionButton",
    ];

    static values = {
        ajaxUrl: String,
        bulkDeleteUrl: String,
        deleteFormId: String,
    };

    connect() {
        this.dtInstance = null;
        this.initTimer = null;
        this.retryCount = 0;
        this.retryTimer = null;

        this.boundChange = (event) => this.onChange(event);
        this.boundSubmit = (event) => this.onSubmit(event);

        this.element.addEventListener("change", this.boundChange);
        if (this.hasBulkFormTarget) {
            this.bulkFormTarget.addEventListener("submit", this.boundSubmit);
        }

        this.initTimer = window.setTimeout(() => {
            this.initTimer = null;
            this.retryCount = 0;
            this.initWhenReady();
        }, 0);
        this.updateBulkButtonState();
    }

    disconnect() {
        this.element.removeEventListener("change", this.boundChange);
        if (this.hasBulkFormTarget) {
            this.bulkFormTarget.removeEventListener("submit", this.boundSubmit);
        }

        if (this.initTimer) {
            window.clearTimeout(this.initTimer);
            this.initTimer = null;
        }

        if (this.retryTimer) {
            window.clearTimeout(this.retryTimer);
            this.retryTimer = null;
        }

        this.destroyTable();
    }

    jQueryHandle() {
        return window.jQuery || window.$ || null;
    }

    isDataTablesAvailable() {
        const jq = this.jQueryHandle();
        return Boolean(jq && jq.fn && typeof jq.fn.DataTable === "function");
    }

    hasRequiredExtensions() {
        const jq = this.jQueryHandle();
        const namespace = jq?.fn?.dataTable;
        return Boolean(namespace && namespace.SearchBuilder);
    }

    initWhenReady() {
        if (
            !this.hasTableTarget ||
            !this.isDataTablesAvailable() ||
            !this.hasRequiredExtensions()
        ) {
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

    initTable() {
        const jq = this.jQueryHandle();
        if (!jq || !this.hasTableTarget) {
            return;
        }

        if (jq.fn.DataTable.isDataTable(this.tableTarget)) {
            this.dtInstance = jq(this.tableTarget).DataTable();
            return;
        }

        this.dtInstance = jq(this.tableTarget).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: this.ajaxUrlValue,
                type: "GET",
            },
            columns: [
                {
                    data: "checkbox",
                    name: "checkbox",
                    title: "",
                    orderable: false,
                    searchable: false,
                },
                {
                    data: "game_date",
                    name: "game_date",
                    title: "Date",
                    type: "date",
                },
                {
                    data: "team_season",
                    name: "team_season",
                    title: "Team Season",
                    type: "string",
                },
                { data: "hrn", name: "hrn", title: "H/R/N", type: "string" },
                {
                    data: "opponent",
                    name: "opponent",
                    title: "Opponent",
                    type: "string",
                },
                {
                    data: "game_type",
                    name: "game_type",
                    title: "Type",
                    type: "string",
                },
                {
                    data: "place",
                    name: "place",
                    title: "Place",
                    type: "string",
                },
                {
                    data: "score",
                    name: "score",
                    title: "Score",
                    orderable: false,
                    searchable: false,
                },
                {
                    data: "place_state",
                    name: "place_state",
                    title: "State",
                    type: "string",
                    visible: false,
                },
                {
                    data: "mur_pts",
                    name: "mur_pts",
                    title: "Team Points",
                    type: "num",
                    visible: false,
                },
                {
                    data: "opp_pts",
                    name: "opp_pts",
                    title: "Opponent Points",
                    type: "num",
                    visible: false,
                },
                {
                    data: "mur_rk",
                    name: "mur_rk",
                    title: "Team Rank",
                    type: "num",
                    visible: false,
                },
                {
                    data: "opp_rk",
                    name: "opp_rk",
                    title: "Opponent Rank",
                    type: "num",
                    visible: false,
                },
                {
                    data: "result",
                    name: "result",
                    title: "Result (W/L/T)",
                    type: "string",
                    visible: false,
                },
                {
                    data: "conf",
                    name: "conf",
                    title: "Conference Game",
                    type: "num",
                    visible: false,
                },
                {
                    data: "post",
                    name: "post",
                    title: "Postseason",
                    type: "num",
                    visible: false,
                },
            ],
            order: [[1, "desc"]],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "All"],
            ],
            language: {
                processing: "Loading games...",
            },
            dom: "Qlfrtip",
            searchBuilder: {
                columns: [1, 2, 3, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15],
                depthLimit: 2,
            },
        });
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

    onChange(event) {
        if (this.hasSelectAllTarget && event.target === this.selectAllTarget) {
            this.rowCheckboxes().forEach((checkbox) => {
                checkbox.checked = this.selectAllTarget.checked;
            });
            this.updateBulkButtonState();
            return;
        }

        if (
            event.target.matches(".game-checkbox") ||
            (this.hasActionSelectTarget &&
                event.target === this.actionSelectTarget)
        ) {
            this.updateBulkButtonState();
        }
    }

    onSubmit(event) {
        event.preventDefault();

        if (
            !this.hasActionSelectTarget ||
            this.actionSelectTarget.value !== "delete"
        ) {
            return;
        }

        const checkedRows = this.rowCheckboxes().filter(
            (checkbox) => checkbox.checked,
        );
        if (
            checkedRows.length === 0 ||
            typeof window.showConfirmDelete !== "function"
        ) {
            return;
        }

        const ids = checkedRows.map((checkbox) => checkbox.value);
        const associated = checkedRows.map((checkbox) => {
            const row = checkbox.closest("tr");
            if (!row) {
                return "";
            }
            const date =
                row.querySelector("td:nth-child(2)")?.textContent.trim() || "";
            const opponent =
                row.querySelector("td:nth-child(5)")?.textContent.trim() || "";
            return `${date} vs ${opponent}`;
        });

        window.showConfirmDelete({
            deleteUrl: this.bulkDeleteUrlValue,
            itemType: "games (bulk)",
            associated,
            ids,
            idsName: "game_ids[]",
            formId: this.deleteFormIdValue,
            bulkAction: "delete",
        });
    }

    updateBulkButtonState() {
        if (!this.hasActionButtonTarget || !this.hasActionSelectTarget) {
            return;
        }

        const checkedCount = this.rowCheckboxes().filter(
            (checkbox) => checkbox.checked,
        ).length;
        this.actionButtonTarget.disabled =
            checkedCount === 0 || !this.actionSelectTarget.value;
    }

    rowCheckboxes() {
        return Array.from(this.element.querySelectorAll(".game-checkbox"));
    }
}
