/* global afterEach, beforeEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import NavAccordionController from "../controllers/nav_accordion_controller.js";

describe("nav-accordion controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <ul class="nav sidebar-menu flex-column">
                    <li id="games-item" class="nav-item">
                        <button
                            id="games-toggle"
                            type="button"
                            class="nav-link"
                            data-nav-accordion-target="toggle"
                            data-nav-accordion-prefix="/games"
                            data-action="click->nav-accordion#toggle"
                            aria-controls="games-panel"
                            aria-expanded="false"
                        >
                            Games
                        </button>
                        <ul id="games-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                            <li id="streaks-item" class="nav-item">
                                <button
                                    id="streaks-toggle"
                                    type="button"
                                    class="nav-link"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/streaks?result=W"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="streaks-panel"
                                    aria-expanded="false"
                                >
                                    Winning
                                </button>
                                <ul id="streaks-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                                    <li class="nav-item"><a href="/games/streaks?result=W">Overall</a></li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                </ul>
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
        const gamesItem = document.getElementById("games-item");
        const streaksItem = document.getElementById("streaks-item");

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(gamesItem.classList.contains("menu-open")).toBe(true);
        expect(streaksToggle.getAttribute("aria-expanded")).toBe("true");
        expect(streaksPanel.hidden).toBe(false);
        expect(streaksItem.classList.contains("menu-open")).toBe(true);
    });

    test("toggles a panel when clicked", async () => {
        window.history.pushState({}, "", "/seasons");
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <ul class="nav sidebar-menu flex-column">
                    <li id="games-item" class="nav-item">
                        <button
                            id="games-toggle"
                            type="button"
                            class="nav-link"
                            data-nav-accordion-target="toggle"
                            data-nav-accordion-prefix="/games"
                            data-action="click->nav-accordion#toggle"
                            aria-controls="games-panel"
                            aria-expanded="false"
                        >
                            Games
                        </button>
                        <ul id="games-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                            <li class="nav-item"><a href="/games">Index</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        `;

        application.stop();
        application = Application.start();
        application.register("nav-accordion", NavAccordionController);
        await Promise.resolve();

        const gamesToggle = document.getElementById("games-toggle");
        const gamesPanel = document.getElementById("games-panel");
        const gamesItem = document.getElementById("games-item");

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("false");
        expect(gamesPanel.hidden).toBe(true);
        expect(gamesPanel.classList.contains("d-none")).toBe(true);
        expect(gamesItem.classList.contains("menu-open")).toBe(false);

        gamesToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(gamesPanel.classList.contains("d-none")).toBe(false);
        expect(gamesItem.classList.contains("menu-open")).toBe(true);

        gamesToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("false");
        expect(gamesPanel.hidden).toBe(true);
        expect(gamesPanel.classList.contains("d-none")).toBe(true);
        expect(gamesItem.classList.contains("menu-open")).toBe(false);
    });

    test("expands desktop mini sidebar before opening clicked group", async () => {
        window.history.pushState({}, "", "/seasons");
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <ul class="nav sidebar-menu flex-column">
                    <li id="games-item" class="nav-item">
                        <button
                            id="games-toggle"
                            type="button"
                            class="nav-link"
                            data-nav-accordion-target="toggle"
                            data-nav-accordion-prefix="/games"
                            data-action="click->nav-accordion#toggle"
                            aria-controls="games-panel"
                            aria-expanded="false"
                        >
                            Games
                        </button>
                        <ul id="games-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                            <li class="nav-item"><a href="/games">Index</a></li>
                        </ul>
                    </li>
                    <li id="content-item" class="nav-item">
                        <button
                            id="content-toggle"
                            type="button"
                            class="nav-link"
                            data-nav-accordion-target="toggle"
                            data-nav-accordion-prefix="/blog"
                            data-action="click->nav-accordion#toggle"
                            aria-controls="content-panel"
                            aria-expanded="false"
                        >
                            Content
                        </button>
                        <ul id="content-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                            <li class="nav-item"><a href="/blog">Blog</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        `;

        document.body.classList.add("sidebar-collapse");
        localStorage.setItem("rh_admin_sidebar_collapsed", "1");

        const originalInnerWidth = window.innerWidth;
        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            writable: true,
            value: 1200,
        });

        application.stop();
        application = Application.start();
        application.register("nav-accordion", NavAccordionController);
        await Promise.resolve();

        const gamesToggle = document.getElementById("games-toggle");
        const gamesPanel = document.getElementById("games-panel");
        const gamesItem = document.getElementById("games-item");
        const contentToggle = document.getElementById("content-toggle");
        const contentItem = document.getElementById("content-item");

        gamesToggle.click();

        expect(document.body.classList.contains("sidebar-collapse")).toBe(
            false,
        );
        expect(localStorage.getItem("rh_admin_sidebar_collapsed")).toBe("0");
        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(gamesItem.classList.contains("menu-open")).toBe(true);
        expect(contentToggle.getAttribute("aria-expanded")).toBe("false");
        expect(contentItem.classList.contains("menu-open")).toBe(false);

        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            writable: true,
            value: originalInnerWidth,
        });
    });

    test("opens nested submenu without collapsing parent and closes same-level siblings", async () => {
        window.history.pushState({}, "", "/seasons");
        document.body.innerHTML = `
            <nav data-controller="nav-accordion">
                <ul class="nav sidebar-menu flex-column">
                    <li id="games-item" class="nav-item">
                        <button
                            id="games-toggle"
                            type="button"
                            class="nav-link"
                            data-nav-accordion-target="toggle"
                            data-nav-accordion-prefix="/games"
                            data-action="click->nav-accordion#toggle"
                            aria-controls="games-panel"
                            aria-expanded="false"
                        >
                            Games
                        </button>
                        <div id="games-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                            <div class="rh-nav-subgroup" id="ranked-group">
                                <button
                                    id="ranked-toggle"
                                    type="button"
                                    class="nav-link"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/ranked"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="ranked-panel"
                                    aria-expanded="false"
                                >
                                    Ranked Games
                                </button>
                                <div id="ranked-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                                    <a href="/games/ranked/:filter?filter=all">All Ranked</a>
                                </div>
                            </div>
                            <div class="rh-nav-subgroup" id="hundred-group">
                                <button
                                    id="hundred-toggle"
                                    type="button"
                                    class="nav-link"
                                    data-nav-accordion-target="toggle"
                                    data-nav-accordion-prefix="/games/hundred-point"
                                    data-action="click->nav-accordion#toggle"
                                    aria-controls="hundred-panel"
                                    aria-expanded="false"
                                >
                                    100 Point Games
                                </button>
                                <div id="hundred-panel" class="nav nav-treeview" data-nav-accordion-target="panel" hidden>
                                    <a href="/games/hundred-point/">All 100+ Games</a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        `;

        application.stop();
        application = Application.start();
        application.register("nav-accordion", NavAccordionController);
        await Promise.resolve();

        const gamesToggle = document.getElementById("games-toggle");
        const gamesPanel = document.getElementById("games-panel");
        const rankedToggle = document.getElementById("ranked-toggle");
        const rankedPanel = document.getElementById("ranked-panel");
        const hundredToggle = document.getElementById("hundred-toggle");
        const hundredPanel = document.getElementById("hundred-panel");

        gamesToggle.click();
        rankedToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(rankedToggle.getAttribute("aria-expanded")).toBe("true");
        expect(rankedPanel.hidden).toBe(false);
        expect(hundredToggle.getAttribute("aria-expanded")).toBe("false");
        expect(hundredPanel.hidden).toBe(true);

        hundredToggle.click();

        expect(gamesToggle.getAttribute("aria-expanded")).toBe("true");
        expect(gamesPanel.hidden).toBe(false);
        expect(rankedToggle.getAttribute("aria-expanded")).toBe("false");
        expect(rankedPanel.hidden).toBe(true);
        expect(hundredToggle.getAttribute("aria-expanded")).toBe("true");
        expect(hundredPanel.hidden).toBe(false);
    });
});
