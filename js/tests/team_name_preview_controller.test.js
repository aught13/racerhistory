/* global afterEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import TeamNamePreviewController, {
    composeTeamDisplayNames,
} from "../controllers/team_name_preview_controller.js";

describe("team-name-preview controller", () => {
    let application;

    function flush() {
        return new Promise((resolve) => {
            window.setTimeout(resolve, 0);
        });
    }

    function mountController() {
        document.body.innerHTML = `
            <div data-controller="team-name-preview">
                <input data-team-name-preview-target="teamDescription" value="Murray State" />
                <input data-team-name-preview-target="teamNickname" value="Racers" />
                <input
                    data-team-name-preview-target="teamName"
                    data-action="input->team-name-preview#update change->team-name-preview#update"
                    value="Basketball"
                />
                <select
                    data-team-name-preview-target="gender"
                    data-action="input->team-name-preview#update change->team-name-preview#update"
                >
                    <option value="M" selected>Male</option>
                    <option value="F">Female</option>
                    <option value="C">Co-ed</option>
                </select>
                <span data-team-name-preview-target="fullTeamName"></span>
                <span data-team-name-preview-target="defaultTeamName"></span>
                <span data-team-name-preview-target="team"></span>
            </div>
        `;

        application = Application.start();
        application.register("team-name-preview", TeamNamePreviewController);
    }

    afterEach(() => {
        application?.stop();
        application = null;
        document.body.innerHTML = "";
    });

    test("composes all three display variants in order", () => {
        expect(
            composeTeamDisplayNames({
                teamDescription: "Murray State",
                teamNickname: "Racers",
                teamName: "Basketball",
                gender: "M",
            }),
        ).toEqual({
            fullTeamName: "Murray State Racers Men's Basketball",
            teamName: "Racers Men's Basketball",
            team: "Men's Basketball",
        });
    });

    test("omits co-ed and suppresses gender already included in a name", () => {
        expect(
            composeTeamDisplayNames({
                teamNickname: "Racers",
                teamName: "Women’s Basketball",
                gender: "F",
            }).teamName,
        ).toBe("Racers Women’s Basketball");

        expect(
            composeTeamDisplayNames({
                teamNickname: "Racers",
                teamName: "Bowling",
                gender: "C",
            }).teamName,
        ).toBe("Racers Bowling");
    });

    test("does not add a gender prefix when the base team name is empty", () => {
        expect(
            composeTeamDisplayNames({
                teamNickname: "Racers",
                gender: "M",
            }).teamName,
        ).toBe("Racers");
    });

    test("does not add a conflicting gender to an already gendered name", () => {
        expect(
            composeTeamDisplayNames({
                teamNickname: "Racers",
                teamName: "Women's Basketball",
                gender: "M",
            }).teamName,
        ).toBe("Racers Women's Basketball");
    });

    test("removes repeated exact name components", () => {
        expect(
            composeTeamDisplayNames({
                teamNickname: "Racers",
                teamName: "Racers",
                gender: "M",
            }).teamName,
        ).toBe("Men's Racers");
    });

    test("updates previews as fields change", async () => {
        mountController();
        await flush();

        const teamNameField = document.querySelector(
            '[data-team-name-preview-target="teamName"]',
        );
        const genderField = document.querySelector(
            '[data-team-name-preview-target="gender"]',
        );

        expect(
            document.querySelector(
                '[data-team-name-preview-target="fullTeamName"]',
            ).textContent,
        ).toBe("Murray State Racers Men's Basketball");

        teamNameField.value = "Soccer";
        genderField.value = "F";
        teamNameField.dispatchEvent(new Event("input", { bubbles: true }));
        genderField.dispatchEvent(new Event("change", { bubbles: true }));

        expect(
            document.querySelector(
                '[data-team-name-preview-target="defaultTeamName"]',
            ).textContent,
        ).toBe("Racers Women's Soccer");
        expect(
            document.querySelector('[data-team-name-preview-target="team"]')
                .textContent,
        ).toBe("Women's Soccer");
    });

    test("refreshes previews on turbo:load", async () => {
        mountController();
        await flush();

        const teamNameField = document.querySelector(
            '[data-team-name-preview-target="teamName"]',
        );
        teamNameField.value = "Baseball";
        document.dispatchEvent(new Event("turbo:load"));

        expect(
            document.querySelector(
                '[data-team-name-preview-target="defaultTeamName"]',
            ).textContent,
        ).toBe("Racers Men's Baseball");
    });

    test("removes the turbo:load listener on disconnect", async () => {
        mountController();
        await flush();

        const preview = document.querySelector(
            '[data-team-name-preview-target="defaultTeamName"]',
        );
        const controllerElement = document.querySelector(
            '[data-controller="team-name-preview"]',
        );
        application
            .getControllerForElementAndIdentifier(
                controllerElement,
                "team-name-preview",
            )
            .disconnect();

        document.querySelector(
            '[data-team-name-preview-target="teamName"]',
        ).value = "Baseball";
        document.dispatchEvent(new Event("turbo:load"));

        expect(preview.textContent).toBe("Racers Men's Basketball");
    });

    test("refreshes previews after the form is reset", async () => {
        mountController();
        await flush();

        const teamNameField = document.querySelector(
            '[data-team-name-preview-target="teamName"]',
        );
        const nicknameField = document.querySelector(
            '[data-team-name-preview-target="teamNickname"]',
        );
        const descriptionField = document.querySelector(
            '[data-team-name-preview-target="teamDescription"]',
        );
        const genderField = document.querySelector(
            '[data-team-name-preview-target="gender"]',
        );
        const preview = document.querySelector(
            '[data-team-name-preview-target="defaultTeamName"]',
        );
        const previewControllerElement = document.querySelector(
            '[data-controller="team-name-preview"]',
        );

        teamNameField.value = "";
        nicknameField.value = "";
        descriptionField.value = "";
        genderField.value = "";
        previewControllerElement.dispatchEvent(
            new Event("reset", { bubbles: true }),
        );
        await flush();

        expect(preview.textContent).toBe("Unknown Team");
    });

    test("uses Unknown Team when all name components are empty", () => {
        expect(composeTeamDisplayNames().fullTeamName).toBe("Unknown Team");
    });

    test("omits invalid gender values from previews", () => {
        expect(
            composeTeamDisplayNames({
                teamName: "Basketball",
                gender: "invalid",
            }).team,
        ).toBe("Basketball");
    });
});
