/**
 * roster-multi-add.mjs
 *
 * Manages the dynamic multi-row roster add form with AJAX person search.
 * - Each row has a search input that queries /admin/persons/ajax-search.
 * - "Add Another" button clones a new roster entry row.
 * - "Remove" (×) button removes a row (disabled when only one row remains).
 * - Newly created persons via the popup modal are auto-selected.
 *
 * Initialises on both DOMContentLoaded and turbo:load.
 */

const DEBOUNCE_MS = 300;
const MIN_QUERY_LENGTH = 2;

let _initialised = false;

/**
 * Escape HTML to prevent XSS in dynamic content.
 *
 * @param {string} str Raw string
 * @returns {string} Escaped string
 */
function escapeHtml(str) {
  if (str === null || str === undefined) return "";
  const div = document.createElement("div");
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}

/**
 * Initialise the multi-row roster add functionality.
 * Safe to call multiple times (idempotent via flag).
 */
export function initRosterMultiAdd() {
  const container = document.getElementById("roster-rows");
  const addBtn = document.getElementById("add-row-btn");
  if (!container || !addBtn) {
    return;
  }

  if (_initialised) {
    return;
  }
  _initialised = true;

  const searchUrl = container.dataset.personSearchUrl || "";

  // Initialise person search on the initial row
  initPersonSearch(container.querySelector(".roster-row"), searchUrl);

  // Delegate click events for remove buttons
  container.addEventListener("click", (e) => {
    const removeBtn = e.target.closest(".remove-row-btn");
    if (!removeBtn) {
      return;
    }
    const row = removeBtn.closest(".roster-row");
    if (row) {
      row.remove();
      reindexRows(container);
      updateRemoveButtons(container);
    }
  });

  addBtn.addEventListener("click", () => {
    addRow(container, searchUrl);
  });

  // Listen for person-added events from the popup_form element
  document.addEventListener("popupFormSuccess", (e) => {
    const detail = e.detail || {};
    if (detail.id && detail.label) {
      autoSelectNewPerson(container, detail.id, detail.label);
    }
  });
}

/**
 * Initialise AJAX person search on a single roster row.
 *
 * @param {HTMLElement} row The .roster-row element
 * @param {string} searchUrl The AJAX search endpoint URL
 */
function initPersonSearch(row, searchUrl) {
  if (!row || !searchUrl) return;

  const searchInput = row.querySelector(".roster-person-search");
  const hiddenInput = row.querySelector(".roster-person-id");
  const selectedDisplay = row.querySelector(".roster-person-selected");
  const resultsContainer = row.querySelector(".roster-person-results");

  if (!searchInput || !hiddenInput || !resultsContainer) return;

  // Skip if already initialised (e.g. after Turbo morph)
  if (searchInput.dataset.searchBound === "1") return;
  searchInput.dataset.searchBound = "1";

  let debounceTimer = null;
  let abortController = null;

  function setSelected(id, text) {
    hiddenInput.value = id;
    if (selectedDisplay) {
      selectedDisplay.innerHTML =
        '<span class="badge bg-primary me-1">' +
        escapeHtml(text) +
        ' <button type="button" class="btn-close btn-close-white ms-1 roster-clear-person" ' +
        'aria-label="Clear" style="font-size:.5em;vertical-align:middle"></button></span>';
      const clearBtn = selectedDisplay.querySelector(".roster-clear-person");
      if (clearBtn) {
        clearBtn.addEventListener("click", () => clearSelection());
      }
    }
    resultsContainer.innerHTML = "";
    searchInput.value = "";
  }

  function clearSelection() {
    hiddenInput.value = "";
    if (selectedDisplay) {
      selectedDisplay.innerHTML =
        '<span class="text-muted fst-italic">None selected</span>';
    }
  }

  function doSearch(query) {
    if (abortController) {
      abortController.abort();
    }
    abortController = new AbortController();

    fetch(searchUrl + "?q=" + encodeURIComponent(query), {
      signal: abortController.signal,
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data.success || !data.results) {
          resultsContainer.innerHTML =
            '<div class="text-muted small py-1">No results</div>';
          return;
        }
        buildResults(data.results, resultsContainer, setSelected);
      })
      .catch((err) => {
        if (err.name !== "AbortError") {
          resultsContainer.innerHTML =
            '<div class="text-danger small">Network error</div>';
        }
      });
  }

  searchInput.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    const q = searchInput.value.trim();
    if (q.length < MIN_QUERY_LENGTH) {
      resultsContainer.innerHTML = "";
      return;
    }
    debounceTimer = setTimeout(() => doSearch(q), DEBOUNCE_MS);
  });

  // Close results when clicking outside this row
  document.addEventListener("click", (e) => {
    if (!row.contains(e.target)) {
      resultsContainer.innerHTML = "";
    }
  });

  // Expose setSelected on the row for external use (e.g. popup form)
  row._rosterSetSelected = setSelected;
}

/**
 * Build a results dropdown from search results.
 *
 * @param {Array} results Array of { value, text }
 * @param {HTMLElement} container Results container element
 * @param {function} onSelect Callback when a result is selected
 */
function buildResults(results, container, onSelect) {
  if (!results.length) {
    container.innerHTML =
      '<div class="text-muted small py-1">No results found</div>';
    return;
  }

  let html =
    '<div class="list-group list-group-flush roster-search-results" style="position:absolute;z-index:1050;max-height:200px;overflow-y:auto;width:100%;box-shadow:0 2px 8px rgba(0,0,0,.15)">';
  for (const item of results) {
    html +=
      '<button type="button" class="list-group-item list-group-item-action py-1 small roster-search-result" ' +
      'data-id="' +
      escapeHtml(String(item.value)) +
      '" data-text="' +
      escapeHtml(String(item.text)) +
      '">' +
      escapeHtml(item.text) +
      "</button>";
  }
  html += "</div>";
  container.innerHTML = html;

  container.querySelectorAll(".roster-search-result").forEach((btn) => {
    btn.addEventListener("click", () => {
      onSelect(btn.dataset.id, btn.dataset.text);
    });
  });
}

/**
 * Add a new roster row by cloning the first row as a template.
 *
 * @param {HTMLElement} container The #roster-rows container
 * @param {string} searchUrl The AJAX person search URL
 */
function addRow(container, searchUrl) {
  const rows = container.querySelectorAll(".roster-row");
  const template = rows[0];
  if (!template) {
    return;
  }

  const newRow = template.cloneNode(true);
  const newIndex = rows.length;
  newRow.setAttribute("data-row-index", String(newIndex));

  // Reset all inputs (text + hidden)
  newRow.querySelectorAll("input").forEach((input) => {
    input.value = "";
    if (input.name) {
      input.name = input.name.replace(/rows\[\d+\]/, `rows[${newIndex}]`);
    }
    // Remove search-bound flag so initPersonSearch re-binds
    if (input.dataset.searchBound) {
      delete input.dataset.searchBound;
    }
  });

  // Reset selected display and results
  const selected = newRow.querySelector(".roster-person-selected");
  if (selected) {
    selected.innerHTML =
      '<span class="text-muted fst-italic">None selected</span>';
  }
  const results = newRow.querySelector(".roster-person-results");
  if (results) {
    results.innerHTML = "";
  }

  // Remove stale _rosterSetSelected reference
  delete newRow._rosterSetSelected;

  container.appendChild(newRow);
  updateRemoveButtons(container);

  // Init person search on the new row
  initPersonSearch(newRow, searchUrl);

  // Focus the search input in the new row
  const searchInput = newRow.querySelector(".roster-person-search");
  if (searchInput) {
    searchInput.focus();
  }
}

/**
 * Re-index row data-row-index attributes and input names after removal.
 *
 * @param {HTMLElement} container The #roster-rows container
 */
function reindexRows(container) {
  const rows = container.querySelectorAll(".roster-row");
  rows.forEach((row, idx) => {
    row.setAttribute("data-row-index", String(idx));
    row.querySelectorAll("input").forEach((field) => {
      if (field.name) {
        field.name = field.name.replace(/rows\[\d+\]/, `rows[${idx}]`);
      }
    });
  });
}

/**
 * Enable/disable remove buttons. Disabled when only one row remains.
 *
 * @param {HTMLElement} container The #roster-rows container
 */
function updateRemoveButtons(container) {
  const rows = container.querySelectorAll(".roster-row");
  const removeBtns = container.querySelectorAll(".remove-row-btn");
  const onlyOne = rows.length <= 1;
  removeBtns.forEach((btn) => {
    btn.disabled = onlyOne;
  });
}

/**
 * Auto-select a newly created person in the first row that has no selection.
 *
 * @param {HTMLElement} container The #roster-rows container
 * @param {string|number} id Person ID
 * @param {string} label Person display name
 */
function autoSelectNewPerson(container, id, label) {
  // Find first row without a selected person
  const rows = container.querySelectorAll(".roster-row");
  for (const row of rows) {
    const hidden = row.querySelector(".roster-person-id");
    if (hidden && !hidden.value && row._rosterSetSelected) {
      row._rosterSetSelected(String(id), label);
      return;
    }
  }
}

// Reset flag on turbo:before-cache so re-init works after Turbo navigation
document.addEventListener("turbo:before-cache", () => {
  _initialised = false;
});
