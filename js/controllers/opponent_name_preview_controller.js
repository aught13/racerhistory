import { Controller } from "@hotwired/stimulus";

function normalizeDisplayComponents(parts) {
    const components = [];
    for (const part of parts) {
        if (typeof part !== "string") {
            continue;
        }

        const component = part.trim();
        if (component !== "" && !components.includes(component)) {
            components.push(component);
        }
    }

    return components;
}

function composeDisplayName(parts) {
    const components = normalizeDisplayComponents(parts);

    return components.length > 0 ? components.join(" ") : "Unknown Opponent";
}

export function composeOpponentDisplayNames({
    opponentName = "",
    opponentShort = "",
    opponentMascot = "",
} = {}) {
    const hasShort =
        typeof opponentShort === "string" && opponentShort.trim() !== "";
    const hasMascot =
        typeof opponentMascot === "string" && opponentMascot.trim() !== "";
    const fullParts = [opponentName, opponentMascot];
    const fullName = composeDisplayName(
        normalizeDisplayComponents(fullParts).length > 0
            ? fullParts
            : [opponentShort],
    );
    const defaultName =
        hasShort && hasMascot
            ? composeDisplayName([opponentShort, opponentMascot])
            : fullName;
    const mascotName =
        normalizeDisplayComponents([opponentMascot]).length > 0
            ? composeDisplayName([opponentMascot])
            : defaultName;

    return {
        fullOpponentName: fullName,
        defaultOpponentName: defaultName,
        opponent: mascotName,
    };
}

export default class extends Controller {
    static targets = [
        "opponentName",
        "opponentShort",
        "opponentMascot",
        "fullOpponentName",
        "defaultOpponentName",
        "opponent",
    ];

    connect() {
        this.resetUpdateTimer = null;
        this.boundTurboLoad = () => this.update();
        this.boundReset = () => {
            this.resetUpdateTimer = window.setTimeout(() => {
                this.resetUpdateTimer = null;
                this.update();
            }, 0);
        };

        document.addEventListener("turbo:load", this.boundTurboLoad);
        this.element.addEventListener("reset", this.boundReset);
        this.update();
    }

    disconnect() {
        document.removeEventListener("turbo:load", this.boundTurboLoad);
        this.element.removeEventListener("reset", this.boundReset);
        if (this.resetUpdateTimer !== null) {
            window.clearTimeout(this.resetUpdateTimer);
            this.resetUpdateTimer = null;
        }
    }

    update() {
        const names = composeOpponentDisplayNames({
            opponentName: this.opponentNameTarget.value,
            opponentShort: this.opponentShortTarget.value,
            opponentMascot: this.opponentMascotTarget.value,
        });

        this.fullOpponentNameTarget.textContent = names.fullOpponentName;
        this.defaultOpponentNameTarget.textContent = names.defaultOpponentName;
        this.opponentTarget.textContent = names.opponent;
    }
}
