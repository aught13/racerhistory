/* global afterEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import OpponentNamePreviewController, {
    composeOpponentDisplayNames,
} from "../controllers/opponent_name_preview_controller.js";

describe("opponent-name-preview controller", () => {
    let application;

    function flush() {
        return new Promise((resolve) => {
            window.setTimeout(resolve, 0);
        });
    }

    function mountController() {
        document.body.innerHTML = `
            <div data-controller="opponent-name-preview">
                <input
                    data-opponent-name-preview-target="opponentName"
                    data-action="input->opponent-name-preview#update change->opponent-name-preview#update"
                    value="Belmont University"
                />
                <input
                    data-opponent-name-preview-target="opponentShort"
                    data-action="input->opponent-name-preview#update change->opponent-name-preview#update"
                    value="Belmont"
                />
                <input
                    data-opponent-name-preview-target="opponentMascot"
                    data-action="input->opponent-name-preview#update change->opponent-name-preview#update"
                    value="Bruins"
                />
                <span data-opponent-name-preview-target="fullOpponentName"></span>
                <span data-opponent-name-preview-target="defaultOpponentName"></span>
                <span data-opponent-name-preview-target="opponent"></span>
            </div>
        `;

        application = Application.start();
        application.register("opponent-name-preview", OpponentNamePreviewController);
    }

    afterEach(() => {
        application?.stop();
        application = null;
        document.body.innerHTML = "";
    });

    test("composes full, default, and mascot-only opponent names", () => {
        expect(
            composeOpponentDisplayNames({
                opponentName: "Belmont University",
                opponentShort: "Belmont",
                opponentMascot: "Bruins",
            }),
        ).toEqual({
            fullOpponentName: "Belmont University Bruins",
            defaultOpponentName: "Belmont Bruins",
            opponent: "Bruins",
        });
    });

    test("falls back to the official name when the mascot is missing", () => {
        expect(
            composeOpponentDisplayNames({
                opponentName: "Belmont",
                opponentShort: "BEL",
            }),
        ).toEqual({
            fullOpponentName: "Belmont",
            defaultOpponentName: "Belmont",
            opponent: "Belmont",
        });
    });

    test("uses the short name when both official name and mascot are missing", () => {
        expect(
            composeOpponentDisplayNames({
                opponentShort: "BEL",
            }).fullOpponentName,
        ).toBe("BEL");
    });

    test("uses a stable fallback when all name fields are empty", () => {
        expect(composeOpponentDisplayNames()).toEqual({
            fullOpponentName: "Unknown Opponent",
            defaultOpponentName: "Unknown Opponent",
            opponent: "Unknown Opponent",
        });
    });

    test("removes exact duplicate components", () => {
        expect(
            composeOpponentDisplayNames({
                opponentName: "Racers",
                opponentShort: "Racers",
                opponentMascot: "Racers",
            }),
        ).toEqual({
            fullOpponentName: "Racers",
            defaultOpponentName: "Racers",
            opponent: "Racers",
        });
    });

    test("updates all previews when the fields change", async () => {
        mountController();
        await flush();

        document.querySelector(
            '[data-opponent-name-preview-target="opponentName"]',
        ).value = "Murray State University";
        document.querySelector(
            '[data-opponent-name-preview-target="opponentShort"]',
        ).value = "Murray State";
        document.querySelector(
            '[data-opponent-name-preview-target="opponentMascot"]',
        ).value = "Racers";
        document
            .querySelector('[data-opponent-name-preview-target="opponentMascot"]')
            .dispatchEvent(new Event("input", { bubbles: true }));

        expect(
            document.querySelector(
                '[data-opponent-name-preview-target="fullOpponentName"]',
            ).textContent,
        ).toBe("Murray State University Racers");
        expect(
            document.querySelector(
                '[data-opponent-name-preview-target="defaultOpponentName"]',
            ).textContent,
        ).toBe("Murray State Racers");
        expect(
            document.querySelector(
                '[data-opponent-name-preview-target="opponent"]',
            ).textContent,
        ).toBe("Racers");
    });

    test("refreshes on turbo:load and removes the listener on disconnect", async () => {
        mountController();
        await flush();

        const controllerElement = document.querySelector(
            '[data-controller="opponent-name-preview"]',
        );
        const controller = application.getControllerForElementAndIdentifier(
            controllerElement,
            "opponent-name-preview",
        );
        const shortField = document.querySelector(
            '[data-opponent-name-preview-target="opponentShort"]',
        );
        const preview = document.querySelector(
            '[data-opponent-name-preview-target="defaultOpponentName"]',
        );

        shortField.value = "Belmont College";
        document.dispatchEvent(new Event("turbo:load"));
        expect(preview.textContent).toBe("Belmont College Bruins");

        controller.disconnect();
        shortField.value = "Belmont";
        document.dispatchEvent(new Event("turbo:load"));
        expect(preview.textContent).toBe("Belmont College Bruins");
    });

    test("refreshes previews when the form resets", async () => {
        mountController();
        await flush();

        const controllerElement = document.querySelector(
            '[data-controller="opponent-name-preview"]',
        );
        const preview = document.querySelector(
            '[data-opponent-name-preview-target="defaultOpponentName"]',
        );
        document.querySelector(
            '[data-opponent-name-preview-target="opponentName"]',
        ).value = "";
        document.querySelector(
            '[data-opponent-name-preview-target="opponentShort"]',
        ).value = "";
        document.querySelector(
            '[data-opponent-name-preview-target="opponentMascot"]',
        ).value = "";
        controllerElement.dispatchEvent(new Event("reset", { bubbles: true }));
        await flush();

        expect(preview.textContent).toBe("Unknown Opponent");
    });
});
