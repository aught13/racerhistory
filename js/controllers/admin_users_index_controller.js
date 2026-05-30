import { Controller } from "@hotwired/stimulus";

const RETRY_DELAY_MS = 200;
const MAX_RETRIES = 60;

export default class extends Controller {
    static targets = [
        "pendingTable",
        "searchTable",
        "bulkForm",
        "actionSelect",
        "actionButton",
        "selectAll",
    ];

    static values = {
        bulkActivateUrl: String,
        bulkDeleteUrl: String,
        deleteFormId: String,
    };

    connect() {
        this.pendingDt = null;
        this.searchDt = null;
        this.initTimer = null;
        this.retryTimer = null;
        this.retryCount = 0;

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

        this.destroyTables();
    }

    jQueryHandle() {
        return window.jQuery || window.$ || null;
    }

    isDataTablesAvailable() {
        const jq = this.jQueryHandle();
        return Boolean(jq && jq.fn && typeof jq.fn.DataTable === "function");
    }

    initWhenReady() {
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

        this.initTables();
    }

    initTables() {
        const jq = this.jQueryHandle();
        if (!jq) {
            return;
        }

        if (this.hasPendingTableTarget) {
            this.pendingDt = this.initTable(this.pendingTableTarget, jq);
        }

        if (this.hasSearchTableTarget) {
            this.searchDt = this.initTable(this.searchTableTarget, jq);
        }
    }

    initTable(table, jq) {
        if (jq.fn.DataTable.isDataTable(table)) {
            return jq(table).DataTable();
        }

        return jq(table).DataTable({
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
        });
    }

    destroyTables() {
        [this.pendingDt, this.searchDt].forEach((table) => {
            if (!table) {
                return;
            }
            try {
                table.destroy(false);
            } catch {
                // no-op: stale instance during Turbo navigation
            }
        });

        this.pendingDt = null;
        this.searchDt = null;
    }

    onChange(event) {
        if (!this.hasBulkFormTarget) {
            return;
        }

        if (this.hasSelectAllTarget && event.target === this.selectAllTarget) {
            this.rowCheckboxes().forEach((checkbox) => {
                checkbox.checked = this.selectAllTarget.checked;
            });
        }

        if (
            (this.hasSelectAllTarget &&
                event.target === this.selectAllTarget) ||
            (this.hasActionSelectTarget &&
                event.target === this.actionSelectTarget) ||
            event.target.matches("[data-admin-users-index-role='row-checkbox']")
        ) {
            this.updateBulkButtonState();
        }
    }

    onSubmit(event) {
        event.preventDefault();

        if (!this.hasActionSelectTarget || !this.hasBulkFormTarget) {
            return;
        }

        const action = this.actionSelectTarget.value;
        if (!action) {
            return;
        }

        if (action === "approve") {
            this.bulkFormTarget.action = this.bulkActivateUrlValue;
            if (typeof this.bulkFormTarget.requestSubmit === "function") {
                this.bulkFormTarget.requestSubmit();
            } else {
                this.bulkFormTarget.submit();
            }
            return;
        }

        if (action !== "delete") {
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

        const ids = checkedRows.map((checkbox) => checkbox.value);
        const names = checkedRows.map((checkbox) => {
            const row = checkbox.closest("tr");
            const cell = row ? row.querySelector("td:nth-child(2)") : null;
            return cell ? cell.textContent.trim() : "";
        });

        window.__rhStimulusShowConfirmDelete({
            deleteUrl: this.bulkDeleteUrlValue,
            itemType: "users (bulk)",
            associated: names,
            ids,
            idsName: "user_ids[]",
            formId: this.deleteFormIdValue,
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
                "[data-admin-users-index-role='row-checkbox']",
            ),
        );
    }
}
