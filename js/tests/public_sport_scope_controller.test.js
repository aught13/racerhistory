/* global afterEach, beforeEach, describe, expect, test, jest */

import { Application } from "@hotwired/stimulus";

import PublicSportScopeController from "../controllers/public_sport_scope_controller.js";

describe("public-sport-scope controller", () => {
    let application;

    beforeEach(() => {
        document.body.innerHTML = `
            <form data-controller="public-sport-scope"
                                    data-public-sport-scope-sport-key-value="all"
                  data-public-sport-scope-team-id-value="all"
                  data-action="submit->public-sport-scope#submit">
                <select name="sport" data-public-sport-scope-target="sport"
                        data-action="change->public-sport-scope#changeSport">
                                        <option value="all" selected>All sports</option>
                                        <option value="basketball">Basketball</option>
                    <option value="football">Football</option>
                </select>
                <select name="team" data-public-sport-scope-target="team"
                        data-action="change->public-sport-scope#changeTeam">
                    <option value="all" data-sport-key="all" selected>All teams</option>
                    <option value="3" data-sport-key="football">Giants (Football)</option>
                </select>
                <a id="shared-link" href="/blog/story">Story</a>
                <a id="explicit-link" href="/people/1?sport=football&team=3&show_all_history=1">Profile</a>
                <a id="admin-link" href="/admin/teams">Admin</a>
            </form>
        `;

        window.history.pushState({}, "", "/seasons");
        application = Application.start();
        application.register("public-sport-scope", PublicSportScopeController);
    });

    afterEach(() => {
        application?.stop();
        application = null;
        document.body.innerHTML = "";
        window.history.pushState({}, "", "/");
    });

    test("canonicalizes missing scope to all sports and teams", () => {
        expect(window.location.search).toBe("?sport=all&team=all");
        expect(document.getElementById("shared-link").search).toBe(
            "?sport=all&team=all",
        );
        expect(document.getElementById("explicit-link").search).toContain(
            "sport=football",
        );
        expect(document.getElementById("admin-link").search).toBe("");
    });

    test("changing sport resets the team and submits the form", () => {
        const form = document.querySelector("form");
        const team = document.querySelector('[name="team"]');
        const sport = document.querySelector('[name="sport"]');
        form.requestSubmit = jest.fn();

        sport.value = "football";
        sport.dispatchEvent(new Event("change", { bubbles: true }));

        expect(team.value).toBe("all");
        expect(form.requestSubmit).toHaveBeenCalledTimes(1);
    });

    test("changing team selects its sport and submits the form", () => {
        const form = document.querySelector("form");
        const team = document.querySelector('[name="team"]');
        const sport = document.querySelector('[name="sport"]');
        form.requestSubmit = jest.fn();

        team.value = "3";
        team.dispatchEvent(new Event("change", { bubbles: true }));

        expect(sport.value).toBe("football");
        expect(form.requestSubmit).toHaveBeenCalledTimes(1);
    });

    test("changing team updates a hidden sport input when only one sport is shown", async () => {
        const form = document.querySelector("form");
        const sport = document.querySelector('[name="sport"]');
        form.requestSubmit = jest.fn();
        document
            .querySelectorAll('[data-public-sport-scope-target="sport"]')
            .forEach((target) =>
                target.removeAttribute("data-public-sport-scope-target"),
            );
        const hiddenSport = document.createElement("input");
        hiddenSport.type = "hidden";
        hiddenSport.name = "sport";
        hiddenSport.value = "all";
        hiddenSport.dataset.publicSportScopeTarget = "sport";
        sport.replaceWith(hiddenSport);

        application.stop();
        application = Application.start();
        application.register("public-sport-scope", PublicSportScopeController);
        await Promise.resolve();

        const refreshedTeam = document.querySelector('[name="team"]');
        refreshedTeam.value = "3";
        refreshedTeam.dispatchEvent(new Event("change", { bubbles: true }));

        expect(hiddenSport.value).toBe("football");
        expect(form.requestSubmit).toHaveBeenCalledTimes(1);
    });

    test("preserves shared links when the current URL already has scope", async () => {
        window.history.pushState({}, "", "/seasons?sport=football&team=3");
        application.stop();
        application = Application.start();
        application.register("public-sport-scope", PublicSportScopeController);
        await Promise.resolve();

        expect(document.getElementById("shared-link").search).toBe(
            "?sport=all&team=all",
        );
    });
});
