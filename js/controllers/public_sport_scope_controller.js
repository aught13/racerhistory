import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["sport", "team"];

    static values = {
        sportKey: String,
        teamId: String,
    };

    connect() {
        this.handleTurboFrameLoad = this.handleTurboFrameLoad.bind(this);
        document.addEventListener(
            "turbo:frame-load",
            this.handleTurboFrameLoad,
        );

        const url = new URL(window.location.href);
        const sport = this.hasSportTarget
            ? this.sportTarget.value
            : this.sportKeyValue || "all";
        const team = this.hasTeamTarget
            ? this.teamTarget.value
            : this.teamIdValue || "all";

        if (
            url.searchParams.get("sport") !== sport ||
            url.searchParams.get("team") !== team
        ) {
            url.searchParams.set("sport", sport);
            url.searchParams.set("team", team);
            window.history.replaceState(window.history.state, "", url);
        }

        this.preserveScopeInLinks();
    }

    disconnect() {
        if (this.handleTurboFrameLoad) {
            document.removeEventListener(
                "turbo:frame-load",
                this.handleTurboFrameLoad,
            );
        }
    }

    handleTurboFrameLoad(event) {
        this.preserveScopeInLinks(event?.target);
    }

    preserveScopeInLinks(root = document) {
        const sport = this.hasSportTarget
            ? this.sportTarget.value
            : this.sportKeyValue || "all";
        const team = this.hasTeamTarget
            ? this.teamTarget.value
            : this.teamIdValue || "all";

        root.querySelectorAll("a[href]").forEach((link) => {
            let url;
            try {
                url = new URL(link.href, window.location.origin);
            } catch {
                return;
            }

            if (
                url.origin !== window.location.origin ||
                url.pathname.startsWith("/admin")
            ) {
                return;
            }

            if (!url.searchParams.has("sport")) {
                url.searchParams.set("sport", sport);
            }
            if (!url.searchParams.has("team")) {
                url.searchParams.set("team", team);
            }

            link.href = url.toString();
        });
    }

    changeSport() {
        if (this.hasTeamTarget) {
            this.teamTarget.value = "all";
        }

        this.element.requestSubmit();
    }

    changeTeam() {
        const selectedOption = this.teamTarget.selectedOptions[0];
        const sportKey = selectedOption?.dataset.sportKey;
        if (sportKey && sportKey !== "all") {
            this.sportTarget.value = sportKey;
        }

        this.element.requestSubmit();
    }

    submit() {
        if (!this.hasTeamTarget || this.teamTarget.value === "all") {
            return;
        }

        const sportKey = this.teamTarget.selectedOptions[0]?.dataset.sportKey;
        if (sportKey && sportKey !== "all") {
            this.sportTarget.value = sportKey;
        }
    }
}
