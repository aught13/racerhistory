/**
 * Sport-Aware Games Form JavaScript
 * Handles dynamic form updates based on selected team season's sport
 */

class SportAwareGameForm {
    constructor() {
        this.teamSeasonSelect = document.getElementById("team-season-select");
        this.sportIndicator = document.getElementById("sport-indicator");
        this.currentSportSpan = document.getElementById("current-sport");
        this.sportLoading = document.getElementById("sport-loading");
        this.sportSection = document.getElementById("sport-specific-section");

        if (this.teamSeasonSelect) {
            this.init();
        }
    }

    init() {
        // Show initial sport if team season is pre-selected
        if (this.teamSeasonSelect.value) {
            this.updateSportFields(this.teamSeasonSelect.value);
        }

        // Listen for team season changes
        this.teamSeasonSelect.addEventListener("change", (e) => {
            const teamSeasonId = e.target.value;
            if (teamSeasonId) {
                this.updateSportFields(teamSeasonId);
            } else {
                this.hideSportIndicator();
                this.showFallbackFields();
            }
        });
    }

    async updateSportFields(teamSeasonId) {
        this.showLoading();

        try {
            // Always use game_id if present (edit mode), only use team_season_id for new
            let url = this.teamSeasonSelect.dataset.sportUrl;
            const gameIdEl = document.getElementById("game-id-hidden");
            const params = new URLSearchParams();
            if (gameIdEl && gameIdEl.value) {
                params.append("game_id", gameIdEl.value);
            } else if (teamSeasonId) {
                params.append("team_season_id", teamSeasonId);
            }
            url += "?" + params.toString();
            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "application/json",
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateSportIndicator(data.sportName);
                this.renderSportFields(data);

                // Map EAV period keys to legacy form field names (for visible form fields)
                if (data.values) {
                    const periodKeyMap = {
                        period_1_team: "period_1_mur",
                        period_1_opponent: "period_1_opp",
                        period_2_team: "period_2_mur",
                        period_2_opponent: "period_2_opp",
                        period_3_team: "period_3_mur",
                        period_3_opponent: "period_3_opp",
                        period_4_team: "period_4_mur",
                        period_4_opponent: "period_4_opp",
                        // Add more if needed
                    };
                    Object.entries(periodKeyMap).forEach(
                        ([eavKey, legacyKey]) => {
                            if (data.values[eavKey] !== undefined) {
                                const input =
                                    document.getElementsByName(legacyKey)[0];
                                if (input) {
                                    input.value = data.values[eavKey];
                                }
                            }
                        },
                    );
                }
            } else {
                this.showError(data.error || "Failed to load sport data");
                this.showFallbackFields();
            }
        } catch (error) {
            console.error("Error fetching sport data:", error);
            this.showError("Failed to load sport-specific fields");
            this.showFallbackFields();
        } finally {
            this.hideLoading();
        }
    }

    showLoading() {
        this.sportLoading.style.display = "inline-block";
        this.currentSportSpan.textContent = "Loading...";
        this.sportIndicator.style.display = "block";
    }

    hideLoading() {
        this.sportLoading.style.display = "none";
    }

    updateSportIndicator(sportName) {
        this.currentSportSpan.textContent = sportName || "Unknown Sport";
        this.sportIndicator.style.display = "block";
        this.sportIndicator.querySelector(".alert").className =
            "alert alert-info";
    }

    hideSportIndicator() {
        this.sportIndicator.style.display = "none";
    }

    showError(message) {
        this.currentSportSpan.textContent = message;
        this.sportIndicator.style.display = "block";
        this.sportIndicator.querySelector(".alert").className =
            "alert alert-warning";
    }

    renderSportFields(data) {
        if (!data.eavTemplate || data.eavTemplate.length === 0) {
            this.showFallbackFields();
            return;
        }

        // Group fields by their group (support associative array/object)
        const fieldsByGroup = {};
        Object.values(data.eavTemplate).forEach((field) => {
            const group = field.field_group || "general";
            if (!fieldsByGroup[group]) {
                fieldsByGroup[group] = [];
            }
            fieldsByGroup[group].push(field);
        });

        // Generate HTML for sport-specific fields
        let html = `<div><h5>Sport-Specific Details (${this.escapeHtml(data.sportName)})</h5>`;

        Object.entries(fieldsByGroup).forEach(([groupName, fields]) => {
            html += `
                <div class="card mt-2">
                    <div class="card-header">
                        <h6 class="mb-0">${this.escapeHtml(this.capitalizeFirst(groupName))}</h6>
                    </div>
                    <div class="card-body">
            `;

            // Create rows of fields (4 per row)
            const fieldsPerRow = 4;
            for (let i = 0; i < fields.length; i += fieldsPerRow) {
                const fieldChunk = fields.slice(i, i + fieldsPerRow);
                const colSize = 12 / Math.min(fieldChunk.length, fieldsPerRow);

                html += '<div class="row g-3 mb-2">';
                fieldChunk.forEach((field) => {
                    html += `
                        <div class="col-md-${colSize}">
                            ${this.renderField(field)}
                        </div>
                    `;
                });
                html += "</div>";
            }

            html += "</div></div>";
        });

        html += "</div>";
        this.sportSection.innerHTML = html;
    }

    renderField(field) {
        const fieldType = field.field_type || "text";
        const currentValue =
            this.getExistingFieldValue(field.field_name) ||
            field.default_value ||
            "";

        let inputHtml = "";
        const commonAttrs = `
            name="${this.escapeHtml(field.field_name)}"
            id="${this.escapeHtml(field.field_name)}"
            class="form-control"
            value="${this.escapeHtml(currentValue)}"
        `;

        if (fieldType === "number") {
            const minAttr = field.min !== undefined ? `min="${field.min}"` : "";
            const maxAttr = field.max !== undefined ? `max="${field.max}"` : "";
            inputHtml = `<input type="number" ${commonAttrs} ${minAttr} ${maxAttr}>`;
        } else {
            inputHtml = `<input type="text" ${commonAttrs}>`;
        }

        return `
            <label for="${this.escapeHtml(field.field_name)}" class="form-label">
                ${this.escapeHtml(field.display_label)}
            </label>
            ${inputHtml}
        `;
    }

    showFallbackFields() {
        // Show traditional period/official fields when sport data isn't available
        const fallbackHtml = `
            <div>
                <h5>Game Details</h5>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label for="periods" class="form-label">Periods</label>
                        <input type="number" name="periods" id="periods" class="form-control" min="0" max="10" value="${this.getExistingFieldValue("periods") || ""}">
                    </div>
                    <div class="col-md-3">
                        <label for="official-1" class="form-label">Official 1</label>
                        <input type="text" name="official_1" id="official-1" class="form-control" value="${this.getExistingFieldValue("official_1") || ""}">
                    </div>
                    <div class="col-md-3">
                        <label for="official-2" class="form-label">Official 2</label>
                        <input type="text" name="official_2" id="official-2" class="form-control" value="${this.getExistingFieldValue("official_2") || ""}">
                    </div>
                    <div class="col-md-3">
                        <label for="official-3" class="form-label">Official 3</label>
                        <input type="text" name="official_3" id="official-3" class="form-control" value="${this.getExistingFieldValue("official_3") || ""}">
                    </div>
                </div>
            </div>
        `;
        this.sportSection.innerHTML = fallbackHtml;
    }

    getExistingFieldValue(fieldName) {
        // Look for existing form fields to preserve values during dynamic updates
        const existingField = document.querySelector(`[name="${fieldName}"]`);
        return existingField ? existingField.value : "";
    }

    escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Initialize when DOM is ready in browser global usage; export class for Node/CommonJS tests
if (typeof module !== "undefined" && module && module.exports) {
    module.exports = SportAwareGameForm;
} else {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            new SportAwareGameForm();
        });
    } else {
        new SportAwareGameForm();
    }
}
