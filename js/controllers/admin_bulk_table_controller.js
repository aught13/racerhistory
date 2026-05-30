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
        bulkDeleteUrl: String,
        itemType: String,
        idsName: String,
        formId: String,
        nameColumn: Number,
        orderColumn: Number,
        orderDirection: String,
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

    initWhenReady() {
        if (!this.hasTableTarget) {
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

    initTable() {
        const jq = this.jQueryHandle();
        if (!jq || !this.hasTableTarget) {
            return;
        }

        if (jq.fn.DataTable.isDataTable(this.tableTarget)) {
            this.dtInstance = jq(this.tableTarget).DataTable();
            return;
        }

        const options = {
            pagingType: "simple_numbers",
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

        if (this.hasOrderColumnValue) {
            options.order = [
                [
                    this.orderColumnValue,
                    this.hasOrderDirectionValue
                        ? this.orderDirectionValue
                        : "asc",
                ],
            ];
        }

        this.dtInstance = jq(this.tableTarget).DataTable(options);
    }

    destroyTable() {
        if (!this.dtInstance) {
            return;
        }

        try {
            this.dtInstance.destroy(false);
        } catch {
            // no-op: stale instance during Turbo navigation
        }

        this.dtInstance = null;
    }

    onChange(event) {
        if (
            event.target === this.selectAllTarget ||
            event.target === this.actionSelectTarget ||
            event.target.matches("[data-admin-bulk-table-role='row-checkbox']")
        ) {
            if (event.target === this.selectAllTarget) {
                this.rowCheckboxes().forEach((checkbox) => {
                    checkbox.checked = this.selectAllTarget.checked;
                });
            }

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
            typeof window.__rhStimulusShowConfirmDelete !== "function"
        ) {
            return;
        }

        const names = checkedRows.map((checkbox) =>
            this.extractRowName(checkbox),
        );
        const ids = checkedRows.map((checkbox) => checkbox.value);

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: this.bulkDeleteUrlValue,
            itemType: this.itemTypeValue,
            associated: names,
            ids,
            idsName: this.idsNameValue,
            formId: this.formIdValue,
            bulkAction: "delete",
        });
    }

    updateBulkButtonState() {
        if (!this.hasActionButtonTarget || !this.hasActionSelectTarget) {
            return;
        }

        const checked = this.rowCheckboxes().filter(
            (checkbox) => checkbox.checked,
        ).length;
        this.actionButtonTarget.disabled =
            checked === 0 || !this.actionSelectTarget.value;
    }

    rowCheckboxes() {
        return Array.from(
            this.element.querySelectorAll(
                "[data-admin-bulk-table-role='row-checkbox']",
            ),
        );
    }

    extractRowName(checkbox) {
        const row = checkbox.closest("tr");
        if (!row) {
            return "";
        }

        const columnIndex = this.hasNameColumnValue ? this.nameColumnValue : 2;
        const selector = `td:nth-child(${columnIndex})`;
        const cell = row.querySelector(selector);
        if (!cell) {
            return "";
        }

        return cell.textContent.trim();
    }
}
