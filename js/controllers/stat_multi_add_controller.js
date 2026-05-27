import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["rows", "addButton"];

    connect() {
        this.boundRowsClick = (event) => this.handleRowsClick(event);
        this.boundAddClick = () => this.addRow();

        this.rowsTarget.addEventListener("click", this.boundRowsClick);
        this.addButtonTarget.addEventListener("click", this.boundAddClick);

        this.updateRemoveButtons();
    }

    disconnect() {
        if (this.hasRowsTarget) {
            this.rowsTarget.removeEventListener("click", this.boundRowsClick);
        }

        if (this.hasAddButtonTarget) {
            this.addButtonTarget.removeEventListener(
                "click",
                this.boundAddClick,
            );
        }
    }

    handleRowsClick(event) {
        const removeButton = event.target.closest(".remove-row-btn");
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest(".stat-row");
        if (!row) {
            return;
        }

        row.remove();
        this.reindexRows();
        this.updateRemoveButtons();
    }

    addRow() {
        const rows = this.rowsTarget.querySelectorAll(".stat-row");
        const template = rows[0];
        if (!template) {
            return;
        }

        const newRow = template.cloneNode(true);
        const newIndex = rows.length;
        newRow.setAttribute("data-row-index", String(newIndex));

        newRow.querySelectorAll("input").forEach((input) => {
            if (input.type === "checkbox") {
                input.checked = false;
            } else if (input.type === "hidden") {
                if (!input.name?.match(/\[GP\]$/)) {
                    input.value = "";
                }
            } else if (input.name?.match(/\[period\]$/)) {
                input.value = "Z";
            } else {
                input.value = "";
            }

            if (input.name) {
                input.name = input.name.replace(
                    /rows\[\d+\]/,
                    `rows[${newIndex}]`,
                );
            }
        });

        newRow.querySelectorAll("select").forEach((select) => {
            select.selectedIndex = 0;
            if (select.name) {
                select.name = select.name.replace(
                    /rows\[\d+\]/,
                    `rows[${newIndex}]`,
                );
            }
        });

        const label = newRow.querySelector(".stat-row-label");
        if (label) {
            label.textContent = `Player #${newIndex + 1}`;
        }

        this.rowsTarget.appendChild(newRow);
        this.updateRemoveButtons();

        const firstInput =
            newRow.querySelector(".stat-player-select") ||
            newRow.querySelector(".stat-opp-name") ||
            newRow.querySelector('input[type="text"]');
        if (firstInput) {
            firstInput.focus();
        }
    }

    reindexRows() {
        this.rowsTarget.querySelectorAll(".stat-row").forEach((row, idx) => {
            row.setAttribute("data-row-index", String(idx));
            row.querySelectorAll("input, select").forEach((field) => {
                if (field.name) {
                    field.name = field.name.replace(
                        /rows\[\d+\]/,
                        `rows[${idx}]`,
                    );
                }
            });

            const label = row.querySelector(".stat-row-label");
            if (label) {
                label.textContent = `Player #${idx + 1}`;
            }
        });
    }

    updateRemoveButtons() {
        const rows = this.rowsTarget.querySelectorAll(".stat-row");
        const onlyOne = rows.length <= 1;

        this.rowsTarget
            .querySelectorAll(".remove-row-btn")
            .forEach((button) => {
                button.disabled = onlyOne;
            });
    }
}
