import { Controller } from "@hotwired/stimulus";

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

export default class extends Controller {
    static targets = ["rows", "addButton"];

    connect() {
        this.boundRowsClick = (event) => this.handleRowsClick(event);
        this.boundAddClick = () => this.addRow();
        this.boundDocumentClick = (event) => this.handleDocumentClick(event);
        this.boundPersonAdded = (data) => this.handlePersonAdded(data);

        const rowsElement = this.rowsElement();
        rowsElement.addEventListener("click", this.boundRowsClick);

        const addButton = this.addButtonElement();
        if (addButton) {
            addButton.addEventListener("click", this.boundAddClick);
        }

        document.addEventListener("click", this.boundDocumentClick);
        window.onRosterPersonAdded = this.boundPersonAdded;

        const searchUrl = rowsElement.dataset.personSearchUrl || "";
        rowsElement.querySelectorAll(".roster-row").forEach((row) => {
            this.initPersonSearch(row, searchUrl);
        });

        this.updateRemoveButtons();
    }

    disconnect() {
        const rowsElement = this.rowsElement();
        if (rowsElement) {
            rowsElement.removeEventListener("click", this.boundRowsClick);
        }

        const addButton = this.addButtonElement();
        if (addButton) {
            addButton.removeEventListener("click", this.boundAddClick);
        }

        document.removeEventListener("click", this.boundDocumentClick);

        if (window.onRosterPersonAdded === this.boundPersonAdded) {
            delete window.onRosterPersonAdded;
        }
    }

    rowsElement() {
        return this.hasRowsTarget
            ? this.rowsTarget
            : document.getElementById("roster-rows");
    }

    addButtonElement() {
        return this.hasAddButtonTarget
            ? this.addButtonTarget
            : document.getElementById("add-row-btn");
    }

    handleRowsClick(event) {
        const removeButton = event.target.closest(".remove-row-btn");
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest(".roster-row");
        if (!row) {
            return;
        }

        row.remove();
        this.reindexRows();
        this.updateRemoveButtons();
    }

    handleDocumentClick(event) {
        const target = event.target;
        this.rowsElement()
            .querySelectorAll(".roster-row")
            .forEach((row) => {
                if (!row.contains(target)) {
                    const resultsContainer = row.querySelector(
                        ".roster-person-results",
                    );
                    if (resultsContainer) {
                        resultsContainer.innerHTML = "";
                    }
                }
            });
    }

    handlePersonAdded(data) {
        const option = data?.newOption;
        if (!option?.value || !option?.text) {
            return;
        }

        this.autoSelectNewPerson(String(option.value), option.text);
    }

    initPersonSearch(row, searchUrl) {
        if (!row || !searchUrl) {
            return;
        }

        const searchInput = row.querySelector(".roster-person-search");
        const hiddenInput = row.querySelector(".roster-person-id");
        const selectedDisplay = row.querySelector(".roster-person-selected");
        const resultsContainer = row.querySelector(".roster-person-results");

        if (!searchInput || !hiddenInput || !resultsContainer) {
            return;
        }

        if (searchInput.dataset.searchBound === "1") {
            return;
        }

        searchInput.dataset.searchBound = "1";

        let debounceTimer = null;
        let abortController = null;

        const clearSelection = () => {
            hiddenInput.value = "";
            if (selectedDisplay) {
                selectedDisplay.innerHTML =
                    '<span class="text-muted fst-italic">None selected</span>';
            }
        };

        const setSelected = (id, text) => {
            hiddenInput.value = id;
            if (selectedDisplay) {
                selectedDisplay.innerHTML =
                    '<span class="badge bg-primary me-1">' +
                    this.escapeHtml(text) +
                    ' <button type="button" class="btn-close btn-close-white ms-1 roster-clear-person" ' +
                    'aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';

                const clearBtn = selectedDisplay.querySelector(
                    ".roster-clear-person",
                );
                if (clearBtn) {
                    clearBtn.addEventListener("click", clearSelection);
                }
            }

            resultsContainer.innerHTML = "";
            searchInput.value = "";
        };

        const existingClearBtn = selectedDisplay
            ? selectedDisplay.querySelector(".roster-clear-person")
            : null;
        if (existingClearBtn) {
            existingClearBtn.addEventListener("click", clearSelection);
        }

        searchInput.addEventListener("input", () => {
            window.clearTimeout(debounceTimer);
            const query = searchInput.value.trim();
            if (query.length < MIN_QUERY_LENGTH) {
                resultsContainer.innerHTML = "";
                return;
            }

            debounceTimer = window.setTimeout(() => {
                if (abortController) {
                    abortController.abort();
                }
                abortController = new window.AbortController();

                fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    signal: abortController.signal,
                    headers: { "X-Requested-With": "XMLHttpRequest" },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data.success || !data.results) {
                            resultsContainer.innerHTML =
                                '<div class="text-muted small py-1">No results</div>';
                            return;
                        }

                        this.renderResults(
                            data.results,
                            resultsContainer,
                            setSelected,
                        );
                    })
                    .catch((error) => {
                        if (error?.name !== "AbortError") {
                            resultsContainer.innerHTML =
                                '<div class="text-danger small">Network error</div>';
                        }
                    });
            }, DEBOUNCE_MS);
        });

        row._rosterSetSelected = setSelected;
    }

    renderResults(results, container, onSelect) {
        if (!results.length) {
            container.innerHTML =
                '<div class="text-muted small py-1">No results found</div>';
            return;
        }

        const html = [
            '<div class="list-group list-group-flush roster-search-results" style="position:absolute;z-index:1050;max-height:200px;overflow-y:auto;width:100%;box-shadow:0 2px 8px rgba(0,0,0,.15)">',
            ...results.map((item) => {
                const label = item.text || "";
                const escapedLabel = this.escapeHtml(label);
                return `<button type="button" class="list-group-item list-group-item-action py-1 small roster-search-result" data-id="${this.escapeHtml(String(item.value))}" data-text="${escapedLabel}">${escapedLabel}</button>`;
            }),
            "</div>",
        ].join("");

        container.innerHTML = html;
        container.querySelectorAll(".roster-search-result").forEach((button) => {
            button.addEventListener("click", () => {
                onSelect(button.dataset.id, button.dataset.text);
            });
        });
    }

    addRow() {
        const rowsElement = this.rowsElement();
        const rows = rowsElement.querySelectorAll(".roster-row");
        const template = rows[0];
        if (!template) {
            return;
        }

        const searchUrl = rowsElement.dataset.personSearchUrl || "";
        const newRow = template.cloneNode(true);
        const newIndex = rows.length;
        newRow.setAttribute("data-row-index", String(newIndex));

        newRow.querySelectorAll("input").forEach((input) => {
            if (input.name?.match(/rows\[\d+\]\[id\]$/)) {
                input.remove();
                return;
            }

            input.value = "";
            if (input.name) {
                input.name = input.name.replace(
                    /rows\[\d+\]/,
                    `rows[${newIndex}]`,
                );
            }

            if (input.dataset.searchBound) {
                delete input.dataset.searchBound;
            }
        });

        const selected = newRow.querySelector(".roster-person-selected");
        if (selected) {
            selected.innerHTML =
                '<span class="text-muted fst-italic">None selected</span>';
        }

        const results = newRow.querySelector(".roster-person-results");
        if (results) {
            results.innerHTML = "";
        }

        delete newRow._rosterSetSelected;

        rowsElement.appendChild(newRow);
        this.updateRemoveButtons();
        this.initPersonSearch(newRow, searchUrl);

        const searchInput = newRow.querySelector(".roster-person-search");
        if (searchInput) {
            searchInput.focus();
        }
    }

    reindexRows() {
        this.rowsElement().querySelectorAll(".roster-row").forEach((row, idx) => {
            row.setAttribute("data-row-index", String(idx));
            row.querySelectorAll("input").forEach((field) => {
                if (field.name) {
                    field.name = field.name.replace(/rows\[\d+\]/, `rows[${idx}]`);
                }
            });
        });
    }

    updateRemoveButtons() {
        const rowsElement = this.rowsElement();
        const onlyOne = rowsElement.querySelectorAll(".roster-row").length <= 1;
        rowsElement.querySelectorAll(".remove-row-btn").forEach((button) => {
            button.disabled = onlyOne;
        });
    }

    autoSelectNewPerson(id, label) {
        this.rowsElement().querySelectorAll(".roster-row").forEach((row) => {
            if (row._rosterSetSelected) {
                const hidden = row.querySelector(".roster-person-id");
                if (hidden && !hidden.value) {
                    row._rosterSetSelected(id, label);
                }
            }
        });
    }

    escapeHtml(value) {
        const element = document.createElement("div");
        element.appendChild(document.createTextNode(String(value ?? "")));
        return element.innerHTML;
    }
}
