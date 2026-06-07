/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import NavAccordionController from "../controllers/nav_accordion_controller.js";

describe("nav-accordion controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <button
                    id="games-toggle"
                    type="button"
                    data-nav-accordion-target="toggle"
                    data-nav-accordion-prefix="/games"
                    data-action="click->nav-accordion#toggle"
                    aria-controls="games-panel"
                    aria-expanded="false"
                >
                    Games
                </button>
                <div id="games-panel" data-nav-accordion-target="panel" hidden class="d-none">
                    <button
                        id="streaks-toggle"
                        type="button"
                        data-nav-accordion-target="toggle"
                        data-nav-accordion-prefix="/games/streaks?result=W"
                        data-action="click->nav-accordion#toggle"
                        aria-controls="streaks-panel"
                        aria-expanded="false"
                    >
                        Winning
                    </button>
                    <div id="streaks-panel" data-nav-accordion-target="panel" hidden class="d-none">
                        <a href="/games/streaks?result=W">Overall</a>
                    </div>
                </div>
            </nav>
        `;

        window.history.pushState({}, "", "/games/streaks?result=W&filter=home");

        application = Application.start();
        application.register("nav-accordion", NavAccordionController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
        window.history.pushState({}, "", "/");
    });

    test("expands matching sections on connect", () => {
        const gamesToggle = document.getElementById("games-toggle");
        const gamesPanel = document.getElementById("games-panel");
        const streaksToggle = document.getElementById("streaks-toggle");
        const streaksPanel = document.getElementById("streaks-panel");

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(gamesPanel.classList.contains("d-none")).toBe(false);
        expect(streaksToggle.getAttribute("aria-expanded")).toBe("true");
        expect(streaksPanel.hidden).toBe(false);
    });

    test("toggles a panel when clicked", async () => {
        window.history.pushState({}, "", "/seasons");
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <button
                    id="games-toggle"
                    type="button"
                    data-nav-accordion-target="toggle"
                    data-nav-accordion-prefix="/games"
                    data-action="click->nav-accordion#toggle"
                    aria-controls="games-panel"
                    aria-expanded="false"
                >
                    Games
                </button>
                <div id="games-panel" data-nav-accordion-target="panel" hidden class="d-none">
                    <button
                        id="streaks-toggle"
                        type="button"
                        data-nav-accordion-target="toggle"
                        data-nav-accordion-prefix="/games/streaks?result=W"
                        data-action="click->nav-accordion#toggle"
                        aria-controls="streaks-panel"
                        aria-expanded="false"
                    >
                        Winning
                    </button>
                    <div id="streaks-panel" data-nav-accordion-target="panel" hidden class="d-none">
                        <a href="/games/streaks?result=W">Overall</a>
                    </div>
                </div>
            </nav>
        `;

        application.stop();
        application = Application.start();
        application.register("nav-accordion", NavAccordionController);
        await Promise.resolve();

        const gamesToggle = document.getElementById("games-toggle");
        const gamesPanel = document.getElementById("games-panel");

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("false");
        expect(gamesPanel.hidden).toBe(true);

        gamesToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(gamesPanel.classList.contains("d-none")).toBe(false);

        gamesToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("false");
        expect(gamesPanel.hidden).toBe(true);
        expect(gamesPanel.classList.contains("d-none")).toBe(true);
    });
});
