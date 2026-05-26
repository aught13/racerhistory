import { Controller } from "@hotwired/stimulus";

const DATATABLES_RETRY_DELAY_MS = 200;
const DATATABLES_MAX_RETRIES = 30;
const SEARCH_DEBOUNCE_MS = 250;

export default class extends Controller {
    static targets = [
        "table",
        "searchInput",
        "selectAll",
        "bulkBar",
        "actionSelect",
        "bulkButton",
        "bulkForm",
    ];

    static values = {
        bulkDeleteUrl: String,
    };

    connect() {
        this.dtInstance = null;
        this.searchDebounce = null;
        this.initTimer = null;
        this.dataTablesRetryCount = 0;
        this.dataTablesRetryTimer = null;

        this.boundSearchInput = () => this.onSearchInput();
        this.boundSelectAllChange = () => this.onSelectAllChange();
        this.boundActionSelectChange = () => this.updateBulkBar();
        this.boundBulkButtonClick = () => this.onBulkButtonClick();
        this.boundRootChange = (event) => {
            if (
                event.target &&
                event.target.classList.contains("person-checkbox")
            ) {
                this.updateBulkBar();
            }
        };

        this.element.addEventListener("change", this.boundRootChange);
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.addEventListener(
                "input",
                this.boundSearchInput,
            );
        }
        if (this.hasSelectAllTarget) {
            this.selectAllTarget.addEventListener(
                "change",
                this.boundSelectAllChange,
            );
        }
        if (this.hasActionSelectTarget) {
            this.actionSelectTarget.addEventListener(
                "change",
                this.boundActionSelectChange,
            );
        }
        if (this.hasBulkButtonTarget) {
            this.bulkButtonTarget.addEventListener(
                "click",
                this.boundBulkButtonClick,
            );
        }

        this.initTimer = window.setTimeout(() => {
            this.initTimer = null;
            this.dataTablesRetryCount = 0;
            this.initDataTableWhenReady();
        }, 0);
        this.updateBulkBar();
    }

    disconnect() {
        this.element.removeEventListener("change", this.boundRootChange);
        if (this.hasSearchInputTarget) {
            this.searchInputTarget.removeEventListener(
                "input",
                this.boundSearchInput,
            );
        }
        if (this.hasSelectAllTarget) {
            this.selectAllTarget.removeEventListener(
                "change",
                this.boundSelectAllChange,
            );
        }
        if (this.hasActionSelectTarget) {
            this.actionSelectTarget.removeEventListener(
                "change",
                this.boundActionSelectChange,
            );
        }
        if (this.hasBulkButtonTarget) {
            this.bulkButtonTarget.removeEventListener(
                "click",
                this.boundBulkButtonClick,
            );
        }

        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }
        if (this.initTimer) {
            window.clearTimeout(this.initTimer);
            this.initTimer = null;
        }
        if (this.dataTablesRetryTimer) {
            window.clearTimeout(this.dataTablesRetryTimer);
            this.dataTablesRetryTimer = null;
        }

        this.destroyTable();
    }

    jQueryHandle() {
        return window.jQuery || window.$ || null;
    }

    isDataTablesAvailable() {
        const jq = this.jQueryHandle();
        if (!jq || !jq.fn || !jq.fn.DataTable) {
            return false;
        }

        return typeof jq.fn.DataTable === "function";
    }

    initDataTableWhenReady() {
        if (!this.hasTableTarget) {
            return;
        }

        if (!this.isDataTablesAvailable()) {
            if (this.dataTablesRetryCount >= DATATABLES_MAX_RETRIES) {
                return;
            }

            this.dataTablesRetryCount += 1;
            this.dataTablesRetryTimer = window.setTimeout(() => {
                this.initDataTableWhenReady();
            }, DATATABLES_RETRY_DELAY_MS);
            return;
        }

        this.initDataTable();
    }

    destroyTable() {
        if (!this.dtInstance) {
            return;
        }

        try {
            this.dtInstance.destroy(false);
        } catch {
            // no-op: stale/partially initialized instance
        }

        this.dtInstance = null;
    }

    initDataTable() {
        const jq = this.jQueryHandle();
        if (!jq || !this.hasTableTarget) {
            return;
        }

        const tableElement = this.tableTarget;
        const dataUrl = tableElement.dataset.datatablesUrl;

        if (!dataUrl) {
            return;
        }

        if (
            jq.fn.DataTable.isDataTable &&
            jq.fn.DataTable.isDataTable(tableElement)
        ) {
            this.destroyTable();
        }

        this.dtInstance = jq(tableElement).DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: dataUrl,
                type: "GET",
            },
            columns: [
                {
                    data: "id",
                    orderable: false,
                    searchable: false,
                    render: (data) => {
                        return `<input type="checkbox" class="person-checkbox" value="${data}">`;
                    },
                },
                { data: "display", name: "display" },
                { data: "first", name: "first" },
                { data: "last", name: "last", orderSequence: ["asc", "desc"] },
                {
                    data: "birth",
                    name: "birth",
                    orderable: false,
                    searchable: false,
                },
                {
                    data: "actions",
                    name: "actions",
                    orderable: false,
                    searchable: false,
                },
            ],
            order: [[3, "asc"]],
            pageLength: 50,
            lengthMenu: [25, 50, 100, 250],
            paging: true,
            pagingType: "simple_numbers",
            scrollY: "60vh",
            scrollCollapse: true,
            scroller: true,
            deferRender: true,
            language: {
                processing:
                    '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading…',
                search: "",
                searchPlaceholder: "Search…",
                zeroRecords: "No matching persons found.",
                info: "Showing _START_ to _END_ of _TOTAL_ persons",
                infoEmpty: "No persons found.",
                infoFiltered: "(filtered from _MAX_ total persons)",
            },
            dom: "rltip",
        });

        if (this.dtInstance && typeof this.dtInstance.on === "function") {
            this.dtInstance.on("draw", () => {
                this.syncSelectAllState();
                this.updateBulkBar();
            });
        }
    }

    onSearchInput() {
        if (!this.dtInstance || !this.hasSearchInputTarget) {
            return;
        }

        if (this.searchDebounce) {
            window.clearTimeout(this.searchDebounce);
            this.searchDebounce = null;
        }

        this.searchDebounce = window.setTimeout(() => {
            if (!this.dtInstance || !this.hasSearchInputTarget) {
                return;
            }

            this.dtInstance.search(this.searchInputTarget.value).draw();
        }, SEARCH_DEBOUNCE_MS);
    }

    checkedCheckboxes() {
        if (!this.hasTableTarget) {
            return [];
        }

        return Array.from(
            this.tableTarget.querySelectorAll(".person-checkbox:checked"),
        );
    }

    syncSelectAllState() {
        if (!this.hasSelectAllTarget || !this.hasTableTarget) {
            return;
        }

        const checkboxes = Array.from(
            this.tableTarget.querySelectorAll(".person-checkbox"),
        );
        if (checkboxes.length === 0) {
            this.selectAllTarget.checked = false;
            return;
        }

        this.selectAllTarget.checked = checkboxes.every(
            (checkbox) => checkbox.checked,
        );
    }

    updateBulkBar() {
        const checked = this.checkedCheckboxes();
        const hasAction =
            this.hasActionSelectTarget && this.actionSelectTarget.value !== "";

        if (this.hasBulkBarTarget) {
            this.bulkBarTarget.style.display =
                checked.length > 0 ? "flex" : "none";
        }

        if (this.hasBulkButtonTarget) {
            this.bulkButtonTarget.disabled = checked.length === 0 || !hasAction;
        }
    }

    onSelectAllChange() {
        if (!this.hasTableTarget || !this.hasSelectAllTarget) {
            return;
        }

        const shouldCheck = this.selectAllTarget.checked;
        this.tableTarget
            .querySelectorAll(".person-checkbox")
            .forEach((checkbox) => {
                checkbox.checked = shouldCheck;
            });

        this.updateBulkBar();
    }

    onBulkButtonClick() {
        const ids = this.checkedCheckboxes().map((checkbox) => checkbox.value);
        if (
            ids.length === 0 ||
            !this.hasActionSelectTarget ||
            this.actionSelectTarget.value !== "delete"
        ) {
            return;
        }

        if (typeof window.showConfirmDelete !== "function") {
            return;
        }

        const formId = this.hasBulkFormTarget
            ? this.bulkFormTarget.id
            : "delete-form-persons-bulk";

        window.showConfirmDelete({
            deleteUrl: this.bulkDeleteUrlValue,
            itemType: "persons (bulk)",
            ids: JSON.stringify(ids),
            idsName: "person_ids[]",
            formId,
            bulkAction: "delete",
        });
    }
}
