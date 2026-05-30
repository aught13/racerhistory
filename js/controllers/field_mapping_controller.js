import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["container", "addButton"];

    connect() {
        this.boundContainerClick = (event) => this.handleContainerClick(event);
        this.boundAddClick = () => this.addRow();

        this.containerTarget.addEventListener(
            "click",
            this.boundContainerClick,
        );
        this.addButtonTarget.addEventListener("click", this.boundAddClick);

        this.updateRemoveButtons();
    }

    disconnect() {
        if (this.hasContainerTarget) {
            this.containerTarget.removeEventListener(
                "click",
                this.boundContainerClick,
            );
        }

        if (this.hasAddButtonTarget) {
            this.addButtonTarget.removeEventListener(
                "click",
                this.boundAddClick,
            );
        }
    }

    handleContainerClick(event) {
        const removeButton = event.target.closest(".remove-field");
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest(".field-mapping-row");
        if (!row) {
            return;
        }

        row.remove();
        this.updateRemoveButtons();
    }

    addRow() {
        const rows =
            this.containerTarget.querySelectorAll(".field-mapping-row");
        const template = rows[0];
        if (!template) {
            return;
        }

        const row = template.cloneNode(true);
        row.querySelectorAll("input").forEach((input) => {
            input.value = "";
        });

        row.querySelectorAll("select").forEach((select) => {
            select.selectedIndex = 0;
        });

        const removeButton = row.querySelector(".remove-field");
        if (removeButton) {
            removeButton.disabled = false;
        }

        this.containerTarget.appendChild(row);
        this.updateRemoveButtons();
    }

    updateRemoveButtons() {
        const rows =
            this.containerTarget.querySelectorAll(".field-mapping-row");
        const onlyOne = rows.length <= 1;

        rows.forEach((row) => {
            const button = row.querySelector(".remove-field");
            if (button) {
                button.disabled = onlyOne;
            }
        });
    }
}
