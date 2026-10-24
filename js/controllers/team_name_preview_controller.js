import { Controller } from "@hotwired/stimulus";

const GENDER_PREFIXES = {
    M: "Men's",
    F: "Women's",
};
const GENDER_DESIGNATION_PATTERN =
    /\b(?:men(?:['’]s)?|male|boys?|women(?:['’]s)?|female|girls?)\b/iu;

function normalizeDisplayComponents(parts) {
    return parts.reduce((components, part) => {
        if (typeof part !== "string" && typeof part !== "number") {
            return components;
        }

        const component = String(part).trim();
        if (component !== "") {
            const existingIndex = components.indexOf(component);
            if (existingIndex !== -1) {
                components.splice(existingIndex, 1);
            }
            components.push(component);
        }

        return components;
    }, []);
}

function composeDisplayName(parts) {
    const components = normalizeDisplayComponents(parts);

    return components.length > 0 ? components.join(" ") : "Unknown Team";
}

function getGenderPrefix(gender, teamName, nameParts) {
    const genderPrefix = Object.hasOwn(GENDER_PREFIXES, gender)
        ? GENDER_PREFIXES[gender]
        : null;
    if (!genderPrefix || normalizeDisplayComponents([teamName]).length === 0) {
        return "";
    }

    return GENDER_DESIGNATION_PATTERN.test(
        normalizeDisplayComponents(nameParts).join(" "),
    )
        ? ""
        : genderPrefix;
}

export function composeTeamDisplayNames({
    teamDescription = "",
    teamNickname = "",
    teamName = "",
    gender = "",
} = {}) {
    return {
        fullTeamName: composeDisplayName([
            teamDescription,
            teamNickname,
            getGenderPrefix(gender, teamName, [
                teamDescription,
                teamNickname,
                teamName,
            ]),
            teamName,
        ]),
        teamName: composeDisplayName([
            teamNickname,
            getGenderPrefix(gender, teamName, [teamNickname, teamName]),
            teamName,
        ]),
        team: composeDisplayName([
            getGenderPrefix(gender, teamName, [teamName]),
            teamName,
        ]),
    };
}

export default class extends Controller {
    static targets = [
        "teamDescription",
        "teamNickname",
        "teamName",
        "gender",
        "fullTeamName",
        "defaultTeamName",
        "team",
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
        const names = composeTeamDisplayNames({
            teamDescription: this.teamDescriptionTarget.value,
            teamNickname: this.teamNicknameTarget.value,
            teamName: this.teamNameTarget.value,
            gender: this.genderTarget.value,
        });

        this.fullTeamNameTarget.textContent = names.fullTeamName;
        this.defaultTeamNameTarget.textContent = names.teamName;
        this.teamTarget.textContent = names.team;
    }
}
