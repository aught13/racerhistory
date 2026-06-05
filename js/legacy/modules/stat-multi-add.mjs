/**
 * stat-multi-add.mjs
 *
 * Manages the dynamic multi-row stat add forms for both player (person) and
 * opponent stat entry. Supports:
 * - "Add Another" button clones a new stat entry row.
 * - "Remove" (×) button removes a row (disabled when only one row remains).
 * - Re-indexing of row names after add/remove.
 * - Row label numbering (Player #1, Player #2, …).
 *
 * Works for both /admin/stat-basket-game-person/add/:gameId
 * and /admin/stat-basket-game-opponent/add/:gameId
 *
 * Initialises on both DOMContentLoaded and turbo:load.
 */

let _initialised = false;

/**
 * Initialise the multi-row stat add functionality.
 * Safe to call multiple times (idempotent via flag).
 */
export function initStatMultiAdd() {
    const container = document.getElementById("stat-rows");
    const addBtn = document.getElementById("add-row-btn");
    if (!container || !addBtn) {
        return;
    }

    if (_initialised) {
        return;
    }
    _initialised = true;

    // Update remove button state based on initial row count
    updateRemoveButtons(container);

    // Delegate click events for remove buttons
    container.addEventListener("click", (e) => {
        const removeBtn = e.target.closest(".remove-row-btn");
        if (!removeBtn) {
            return;
        }
        const row = removeBtn.closest(".stat-row");
        if (row) {
            row.remove();
            reindexRows(container);
            updateRemoveButtons(container);
        }
    });

    addBtn.addEventListener("click", () => {
        addRow(container);
    });
}

/**
 * Add a new stat row by cloning the first row as a template.
 *
 * @param {HTMLElement} container The #stat-rows container
 */
function addRow(container) {
    const rows = container.querySelectorAll(".stat-row");
    const template = rows[0];
    if (!template) {
        return;
    }

    const newRow = template.cloneNode(true);
    const newIndex = rows.length;
    newRow.setAttribute("data-row-index", String(newIndex));

    // Reset all inputs (text, hidden, select, checkbox)
    newRow.querySelectorAll("input").forEach((input) => {
        if (input.type === "checkbox") {
            input.checked = false;
        } else if (input.type === "hidden") {
            // Preserve GP default value
            if (input.name && input.name.match(/\[GP\]$/)) {
                // keep value="1"
            } else {
                input.value = "";
            }
        } else {
            // Preserve period default
            if (input.name && input.name.match(/\[period\]$/)) {
                input.value = "Z";
            } else {
                input.value = "";
            }
        }
        if (input.name) {
            input.name = input.name.replace(/rows\[\d+\]/, `rows[${newIndex}]`);
        }
    });

    newRow.querySelectorAll("select").forEach((sel) => {
        sel.selectedIndex = 0;
        if (sel.name) {
            sel.name = sel.name.replace(/rows\[\d+\]/, `rows[${newIndex}]`);
        }
    });

    // Update row label
    const label = newRow.querySelector(".stat-row-label");
    if (label) {
        label.textContent = `Player #${newIndex + 1}`;
    }

    container.appendChild(newRow);
    updateRemoveButtons(container);

    // Focus the first meaningful input in the new row
    const firstInput =
        newRow.querySelector(".stat-player-select") ||
        newRow.querySelector(".stat-opp-name") ||
        newRow.querySelector('input[type="text"]');
    if (firstInput) {
        firstInput.focus();
    }
}

/**
 * Re-index row data-row-index attributes, input/select names,
 * and row labels after removal.
 *
 * @param {HTMLElement} container The #stat-rows container
 */
function reindexRows(container) {
    const rows = container.querySelectorAll(".stat-row");
    rows.forEach((row, idx) => {
        row.setAttribute("data-row-index", String(idx));
        row.querySelectorAll("input, select").forEach((field) => {
            if (field.name) {
                field.name = field.name.replace(/rows\[\d+\]/, `rows[${idx}]`);
            }
        });
        const label = row.querySelector(".stat-row-label");
        if (label) {
            label.textContent = `Player #${idx + 1}`;
        }
    });
}

/**
 * Enable/disable remove buttons. Disabled when only one row remains.
 *
 * @param {HTMLElement} container The #stat-rows container
 */
function updateRemoveButtons(container) {
    const rows = container.querySelectorAll(".stat-row");
    const removeBtns = container.querySelectorAll(".remove-row-btn");
    const onlyOne = rows.length <= 1;
    removeBtns.forEach((btn) => {
        btn.disabled = onlyOne;
    });
}

// Reset flag on turbo:before-cache so re-init works after Turbo navigation
document.addEventListener("turbo:before-cache", () => {
    _initialised = false;
});
