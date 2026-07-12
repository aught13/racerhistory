/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PublicShellController from "../controllers/public_shell_controller.js";

describe("public shell controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <div class="rh-page" data-controller="public-shell">
                <header class="rh-head" style="height: 120px"></header>
                <div class="rh-nav-wrap" style="height: 40px"></div>
                <button type="button" class="rh-scroll-top" data-action="click->public-shell#scrollToTop"></button>
            </div>
        `;

        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            value: 1440,
        });
        Object.defineProperty(window, "innerHeight", {
            configurable: true,
            value: 800,
        });
        Object.defineProperty(window, "scrollY", {
            configurable: true,
            value: 0,
        });
        window.scrollTo = jest.fn();

        application = Application.start();
        application.register("public-shell", PublicShellController);
    });

    afterEach(() => {
        application?.stop();
        document.body.innerHTML = "";
    });

    test("applies viewport variant classes and sticky chrome state", () => {
        const shell = document.querySelector(".rh-page");

        expect(shell.dataset.layoutVariant).toBe("desktop");
        expect(shell.classList.contains("rh-layout--desktop")).toBe(true);

        Object.defineProperty(window, "innerWidth", {
            configurable: true,
            value: 1800,
        });
        window.dispatchEvent(new Event("resize"));

        expect(shell.dataset.layoutVariant).toBe("ultrawide");
        expect(shell.classList.contains("rh-layout--ultrawide")).toBe(true);

        Object.defineProperty(window, "scrollY", {
            configurable: true,
            value: 1001,
        });
        window.dispatchEvent(new Event("scroll"));

        expect(document.body.classList.contains("rh-nav-stuck")).toBe(true);
        expect(
            document
                .querySelector(".rh-scroll-top")
                .classList.contains("is-visible"),
        ).toBe(true);
    });

    test("scrolls to the top on click", () => {
        document.querySelector(".rh-scroll-top").click();

        expect(window.scrollTo).toHaveBeenCalledWith({
            top: 0,
            behavior: "smooth",
        });
    });
});
