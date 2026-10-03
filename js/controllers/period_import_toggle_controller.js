import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["master", "item"];

    connect() {
        this.syncMaster();
    }

    togglePeriod() {
        this.itemTargets.forEach((item) => {
            item.checked = this.masterTarget.checked;
        });
        this.syncMaster();
    }

    syncMaster() {
        const checkedCount = this.itemTargets.filter(
            (item) => item.checked,
        ).length;
        this.masterTarget.checked =
            this.itemTargets.length > 0 &&
            checkedCount === this.itemTargets.length;
        this.masterTarget.indeterminate =
            checkedCount > 0 && checkedCount < this.itemTargets.length;
    }
}
