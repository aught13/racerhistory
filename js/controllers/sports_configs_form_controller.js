import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["periodNamesContainer", "settingsContainer"];

    static values = {
        periodNameIndex: Number,
        settingIndex: Number,
    };

    addPeriodName() {
        if (!this.hasPeriodNamesContainerTarget) {
            return;
        }

        const row = document.createElement("div");
        row.className = "period-name-row mb-3";
        row.innerHTML = `
            <div class="row">
                <div class="col-3">
                    <input type="number" name="configs[period_name_new_${this.periodNameIndexValue}][periods]"
                        class="form-control form-control-sm" placeholder="# periods" min="1" max="20">
                </div>
                <div class="col-6">
                    <input type="text" name="configs[period_name_new_${this.periodNameIndexValue}][value]"
                        class="form-control form-control-sm" placeholder="Period name (Half, Quarter, etc.)">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                        data-action="click->sports-configs-form#removePeriodName">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-12">
                    <input type="text" name="configs[period_name_new_${this.periodNameIndexValue}][description]"
                        class="form-control form-control-sm text-muted" placeholder="Description (optional)">
                </div>
            </div>
        `;

        this.periodNamesContainerTarget.appendChild(row);
        this.periodNameIndexValue += 1;
    }

    removePeriodName(event) {
        const row = event.currentTarget.closest(".period-name-row");
        if (row) {
            row.remove();
        }
    }

    addSetting() {
        if (!this.hasSettingsContainerTarget) {
            return;
        }

        const row = document.createElement("div");
        row.className = "setting-row mb-3";
        row.innerHTML = `
            <div class="row">
                <div class="col-3">
                    <input type="text" name="configs[new_setting_${this.settingIndexValue}][key]"
                        class="form-control form-control-sm" placeholder="Setting key">
                </div>
                <div class="col-6">
                    <input type="text" name="configs[new_setting_${this.settingIndexValue}][value]"
                        class="form-control form-control-sm" placeholder="Setting value">
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger"
                        data-action="click->sports-configs-form#removeSetting">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-1">
                <div class="col-12">
                    <input type="text" name="configs[new_setting_${this.settingIndexValue}][description]"
                        class="form-control form-control-sm text-muted" placeholder="Description (optional)">
                </div>
            </div>
        `;

        this.settingsContainerTarget.appendChild(row);
        this.settingIndexValue += 1;
    }

    removeSetting(event) {
        const row = event.currentTarget.closest(".setting-row");
        if (row) {
            row.remove();
        }
    }
}
