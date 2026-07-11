/* global describe, expect, test, jest, beforeEach, afterEach */

import { Application } from "@hotwired/stimulus";

import { registerAdminCoreControllers } from "../route_modules/admin_core.js";
import { registerAdminContentControllers } from "../route_modules/admin_content.js";
import { registerAdminGamesControllers } from "../route_modules/admin_games.js";
import { registerAdminImagesControllers } from "../route_modules/admin_images.js";
import { registerAdminOverlayControllers } from "../route_modules/admin_overlay.js";
import { registerAdminPeopleControllers } from "../route_modules/admin_people.js";
import { registerAdminRostersControllers } from "../route_modules/admin_rosters.js";
import { registerAdminStatsEntryControllers } from "../route_modules/admin_stats_entry.js";
import { registerAdminTaxonomyControllers } from "../route_modules/admin_taxonomy.js";
import { registerAdminUsersControllers } from "../route_modules/admin_users.js";
import { registerPublicAppControllers } from "../route_modules/public_app.js";
import { registerPublicBlogControllers } from "../route_modules/public_blog.js";
import { registerPublicCoreControllers } from "../route_modules/public_core.js";
import { registerPublicGamesControllers } from "../route_modules/public_games.js";
import { registerPublicPeopleControllers } from "../route_modules/public_people.js";
import { registerPublicSeasonsControllers } from "../route_modules/public_seasons.js";
import { registerPublicStatsControllers } from "../route_modules/public_stats.js";

describe("Route Module Controller Registration", () => {
    let application;
    let registeredControllers;

    beforeEach(() => {
        // Set up DOM for modules that check for elements
        document.body.innerHTML = '<div id="stat-rows"></div>';

        application = Application.start();
        registeredControllers = new Set();

        // Mock the register method to track registrations
        const originalRegister = application.register.bind(application);
        application.register = (name, controller) => {
            registeredControllers.add(name);
            return originalRegister(name, controller);
        };

        // Mock getControllerForElementAndIdentifier for modules that check it
        application.getControllerForElementAndIdentifier = jest
            .fn()
            .mockReturnValue(null);

        // Suppress console output from module diagnostics
        jest.spyOn(console, "debug").mockImplementation(() => {});
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }
        registeredControllers.clear();
        document.body.innerHTML = "";
        jest.restoreAllMocks();
    });

    describe("Admin modules", () => {
        test("registerAdminCoreControllers registers layout and navigation controllers", () => {
            registerAdminCoreControllers(application);
            expect(registeredControllers.has("admin-layout")).toBe(true);
            expect(registeredControllers.has("admin-dashboard")).toBe(true);
            expect(registeredControllers.has("nav-accordion")).toBe(true);
        });

        test("registerAdminContentControllers registers content form controllers", () => {
            registerAdminContentControllers(application);
            expect(registeredControllers.has("blog-post-form")).toBe(true);
            expect(registeredControllers.has("image-selector")).toBe(true);
        });

        test("registerAdminGamesControllers registers game controllers", () => {
            registerAdminGamesControllers(application);
            expect(registeredControllers.has("admin-games-index")).toBe(true);
            expect(registeredControllers.has("admin-game-form")).toBe(true);
            expect(registeredControllers.has("game-view")).toBe(true);
            expect(registeredControllers.has("game-box-totals-toggle")).toBe(true);
        });

        test("registerAdminImagesControllers registers image manipulation controllers", () => {
            registerAdminImagesControllers(application);
            expect(registeredControllers.has("admin-index-table")).toBe(true);
            expect(registeredControllers.has("admin-image-crop-thumb")).toBe(true);
            expect(registeredControllers.has("admin-image-manipulate")).toBe(true);
            expect(registeredControllers.has("image-selector")).toBe(true);
            expect(registeredControllers.has("image-upload")).toBe(true);
        });

        test("registerAdminOverlayControllers registers overlay dialog controller", () => {
            registerAdminOverlayControllers(application);
            expect(registeredControllers.has("admin-confirm-delete")).toBe(true);
        });

        test("registerAdminPeopleControllers registers people management controllers", () => {
            registerAdminPeopleControllers(application);
            expect(registeredControllers.has("person-form")).toBe(true);
            expect(registeredControllers.has("persons-index")).toBe(true);
        });

        test("registerAdminRostersControllers registers roster controllers", () => {
            registerAdminRostersControllers(application);
            expect(registeredControllers.has("roster-edit-person")).toBe(true);
            expect(registeredControllers.has("roster-multi-add")).toBe(true);
        });

        test("registerAdminStatsEntryControllers registers stats entry controller and runs legacy init", () => {
            registerAdminStatsEntryControllers(application);
            expect(registeredControllers.has("stat-multi-add")).toBe(true);
        });

        test("registerAdminTaxonomyControllers registers taxonomy form controllers", () => {
            registerAdminTaxonomyControllers(application);
            expect(registeredControllers.has("sports-form")).toBe(true);
            expect(registeredControllers.has("sports-configs-form")).toBe(true);
            expect(registeredControllers.has("season-form")).toBe(true);
            expect(registeredControllers.has("team-season-form")).toBe(true);
        });

        test("registerAdminUsersControllers registers user management controllers", () => {
            registerAdminUsersControllers(application);
            expect(registeredControllers.has("admin-users-index")).toBe(true);
        });
    });

    describe("Public modules", () => {
        test("registerPublicCoreControllers registers public shell and navigation", () => {
            registerPublicCoreControllers(application);
            expect(registeredControllers.has("public-shell")).toBe(true);
            expect(registeredControllers.has("nav-accordion")).toBe(true);
            expect(registeredControllers.has("theme-toggle")).toBe(true);
        });

        test("registerPublicAppControllers registers integrated public app controllers", () => {
            registerPublicAppControllers(application);
            expect(registeredControllers.has("public-shell")).toBe(true);
            expect(registeredControllers.has("blog-interactions")).toBe(true);
            expect(registeredControllers.has("game-view")).toBe(true);
            expect(registeredControllers.has("games-search")).toBe(true);
            expect(registeredControllers.has("people-index")).toBe(true);
            expect(registeredControllers.has("season-view")).toBe(true);
            expect(registeredControllers.has("stats-page")).toBe(true);
        });

        test("registerPublicBlogControllers registers blog interaction controller", () => {
            registerPublicBlogControllers(application);
            expect(registeredControllers.has("blog-interactions")).toBe(true);
        });

        test("registerPublicGamesControllers registers game view controllers", () => {
            registerPublicGamesControllers(application);
            expect(registeredControllers.has("game-view")).toBe(true);
            expect(registeredControllers.has("games-search")).toBe(true);
            expect(registeredControllers.has("series-opponents")).toBe(true);
        });

        test("registerPublicPeopleControllers registers people viewing controllers", () => {
            registerPublicPeopleControllers(application);
            expect(registeredControllers.has("people-index")).toBe(true);
            expect(registeredControllers.has("person-game-log-tabs")).toBe(true);
            expect(registeredControllers.has("person-blog-popovers")).toBe(true);
        });

        test("registerPublicSeasonsControllers registers season view controllers", () => {
            registerPublicSeasonsControllers(application);
            expect(registeredControllers.has("seasons-page")).toBe(true);
            expect(registeredControllers.has("season-view")).toBe(true);
        });

        test("registerPublicStatsControllers registers stats page controller", () => {
            registerPublicStatsControllers(application);
            expect(registeredControllers.has("stats-page")).toBe(true);
        });
    });
});
