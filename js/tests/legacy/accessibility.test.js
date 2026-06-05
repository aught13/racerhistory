/** @jest-environment jsdom */

beforeAll(() => {
    if (typeof HTMLFormElement !== "undefined") {
        HTMLFormElement.prototype.submit = function () {};
        HTMLFormElement.prototype.requestSubmit = function () {};
    }
});
// Accessibility audit tests for roster management element using axe-core

import axeCore from "axe-core";

jest.setTimeout(30000);

describe("Roster Management Accessibility Audit", () => {
    async function runAxe(context, ruleIds) {
        return axeCore.run(context, {
            runOnly: {
                type: "rule",
                values: ruleIds,
            },
        });
    }

    beforeEach(() => {
        // Reset DOM
        document.body.innerHTML = "";

        // Add basic Bootstrap CSS classes mock for better styling context
        const style = document.createElement("style");
        style.textContent = `
            .card { border: 1px solid #dee2e6; }
            .card-header { background: #f8f9fa; padding: 0.75rem 1.25rem; }
            .card-body { padding: 1.25rem; }
            .btn { padding: 0.375rem 0.75rem; border: 1px solid transparent; }
            .btn-success { background: #28a745; color: white; }
            .btn-primary { background: #007bff; color: white; }
            .btn-danger { background: #dc3545; color: white; }
            .table { width: 100%; margin-bottom: 1rem; }
            .table thead th { border-bottom: 2px solid #dee2e6; }
            .table td, .table th { padding: 0.75rem; border-top: 1px solid #dee2e6; }
            .form-select { padding: 0.375rem 0.75rem; border: 1px solid #ced4da; }
        `;
        document.head.appendChild(style);
    });

    function createRosterManagementElement() {
        // Create a realistic roster management DOM structure based on the template
        const html = `
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Team Roster</h3>
                    <a href="/admin/team-season-rosters/add?team_season_id=1"
                       class="btn btn-success btn-sm">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i> Add Roster Entry
                    </a>
                </div>
                <div class="card-body">
                    <form id="bulk-action-form-rosters"
                          action="/admin/team-season-rosters/bulk"
                          method="post">
                        <div class="mb-2 d-flex align-items-center gap-2" id="rosters-bulk-action-bar">
                            <label for="bulk-action-select-rosters" class="form-label mb-0">With Selected:</label>
                            <select id="bulk-action-select-rosters" name="bulk_action"
                                    class="form-select form-select-sm w-auto"
                                    aria-describedby="bulk-action-help">
                                <option value="">Choose...</option>
                                <option value="delete">Delete</option>
                            </select>
                            <div id="bulk-action-help" class="visually-hidden">
                                Select an action to perform on selected roster entries
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"
                                    id="bulk-action-btn-rosters"
                                    disabled
                                    aria-describedby="bulk-btn-help">Go</button>
                            <div id="bulk-btn-help" class="visually-hidden">
                                Execute the selected action on checked roster entries
                            </div>
                        </div>

                        <table class="table table-striped table-bordered"
                               id="rosters-table"
                               role="table"
                               aria-label="Team roster entries">
                            <thead class="table-dark">
                                <tr>
                                    <th scope="col">
                                        <input type="checkbox" id="select-all-rosters"
                                               aria-label="Select all roster entries">
                                    </th>
                                    <th scope="col">Person</th>
                                    <th scope="col">Number</th>
                                    <th scope="col">Position</th>
                                    <th scope="col">Height</th>
                                    <th scope="col">Weight</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="team_season_roster_ids[]"
                                               value="1" class="roster-checkbox"
                                               aria-label="Select roster entry for John Doe">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/images/serve/1?variant=small"
                                                 alt="John Doe photo"
                                                 width="40" height="40"
                                                 class="me-2">
                                            <a href="/admin/persons/view/1">John Doe</a>
                                        </div>
                                    </td>
                                    <td>10</td>
                                    <td>Forward</td>
                                    <td>6'2"</td>
                                    <td>180 lbs</td>
                                    <td>
                                        <a href="/admin/team-season-rosters/edit/1"
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirm-delete-modal"
                                                data-delete-url="/admin/team-season-rosters/delete/1"
                                                aria-label="Delete roster entry for John Doe">Delete</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="team_season_roster_ids[]"
                                               value="2" class="roster-checkbox"
                                               aria-label="Select roster entry for Jane Smith">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="/images/serve/2?variant=small"
                                                 alt="Jane Smith photo"
                                                 width="40" height="40"
                                                 class="me-2">
                                            <a href="/admin/persons/view/2">Jane Smith</a>
                                        </div>
                                    </td>
                                    <td>23</td>
                                    <td>Guard</td>
                                    <td>5'8"</td>
                                    <td>145 lbs</td>
                                    <td>
                                        <a href="/admin/team-season-rosters/edit/2"
                                           class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#confirm-delete-modal"
                                                data-delete-url="/admin/team-season-rosters/delete/2"
                                                aria-label="Delete roster entry for Jane Smith">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>

            <!-- Confirm delete modal for context -->
            <div id="confirm-delete-modal" class="modal" tabindex="-1"
                 role="dialog" aria-labelledby="confirm-delete-title"
                 aria-describedby="confirm-delete-description">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirm-delete-title">Confirm Delete</h5>
                        </div>
                        <div class="modal-body">
                            <p id="confirm-delete-description">Are you sure you want to delete the selected items?</p>
                            <ul id="confirm-delete-modal-assoc"></ul>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirm-delete-modal-delete-btn">Delete</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.innerHTML = html;
        return document.querySelector(".card");
    }

    test("roster management element passes basic accessibility audit", async () => {
        const element = createRosterManagementElement();
        expect(element).toBeTruthy();

        const results = await runAxe(element, [
            "label",
            "button-name",
            "link-name",
            "th-has-data-cells",
            "td-headers-attr",
            "scope-attr-valid",
            "form-field-multiple-labels",
            "aria-valid-attr",
            "aria-required-attr",
        ]);

        // Should have no violations
        expect(results.violations).toHaveLength(0);

        // Should have some passes (confirming rules were checked)
        expect(results.passes.length).toBeGreaterThan(0);
    });

    test("roster table has proper heading structure", async () => {
        createRosterManagementElement();

        const results = await runAxe(document.body, [
            "th-has-data-cells",
            "scope-attr-valid",
        ]);

        // Check specifically for table heading violations
        const tableViolations = results.violations.filter(
            (v) => v.id === "th-has-data-cells" || v.id === "scope-attr-valid",
        );
        expect(tableViolations).toHaveLength(0);
    });

    test("form controls have proper labels", async () => {
        createRosterManagementElement();

        const results = await runAxe(document.body, [
            "label",
            "form-field-multiple-labels",
        ]);

        const labelViolations = results.violations.filter(
            (v) => v.id === "label" || v.id === "form-field-multiple-labels",
        );
        expect(labelViolations).toHaveLength(0);
    });

    test("buttons and links have accessible names", async () => {
        createRosterManagementElement();

        const results = await runAxe(document.body, [
            "button-name",
            "link-name",
        ]);

        const nameViolations = results.violations.filter(
            (v) => v.id === "button-name" || v.id === "link-name",
        );
        expect(nameViolations).toHaveLength(0);
    });

    test("images have appropriate alt text", async () => {
        createRosterManagementElement();

        const results = await runAxe(document.body, ["image-alt"]);

        const imageViolations = results.violations.filter(
            (v) => v.id === "image-alt",
        );
        expect(imageViolations).toHaveLength(0);
    });

    test("modal dialog has proper ARIA attributes", async () => {
        createRosterManagementElement();

        const results = await runAxe(
            document.getElementById("confirm-delete-modal"),
            ["aria-valid-attr", "aria-required-attr"],
        );

        const ariaViolations = results.violations.filter(
            (v) => v.id === "aria-valid-attr" || v.id === "aria-required-attr",
        );
        expect(ariaViolations).toHaveLength(0);
    });

    test("empty roster state accessibility", async () => {
        // Test empty state with just the info message
        document.body.innerHTML = `
            <main role="main">
                <div class="card mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Team Roster</h3>
                        <a href="/admin/team-season-rosters/add?team_season_id=1"
                           class="btn btn-success btn-sm">
                            <i class="bi bi-plus-circle" aria-hidden="true"></i> Add Roster Entry
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info" role="alert">
                            No roster entries have been created for this team season yet.
                            <a href="/admin/team-season-rosters/add?team_season_id=1"
                               class="alert-link">Add the first roster entry</a>.
                        </div>
                    </div>
                </div>
            </main>
        `;

        const results = await runAxe(document.body, [
            "link-name",
            "button-name",
        ]);

        expect(results.violations).toHaveLength(0);
    });

    test("keyboard navigation support indicators", async () => {
        createRosterManagementElement();

        // Check that interactive elements are focusable
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const buttons = document.querySelectorAll("button");
        const links = document.querySelectorAll("a");
        const selects = document.querySelectorAll("select");

        // All interactive elements should be focusable (not have tabindex="-1")
        [...checkboxes, ...buttons, ...links, ...selects].forEach((element) => {
            expect(element.tabIndex).not.toBe(-1);
        });

        // Run specific keyboard accessibility checks
        const results = await runAxe(document.body, ["tabindex", "skip-link"]);

        const keyboardViolations = results.violations.filter(
            (v) => v.id === "tabindex" || v.id === "skip-link",
        );
        expect(keyboardViolations).toHaveLength(0);
    });
});
