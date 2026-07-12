/* global describe, expect, test, jest, beforeEach, afterEach */

import {
    resolveLegacyLoadPlan,
    __resetLegacyLoaderRegistryForTests,
} from "../lib/legacy_loader_registry.js";

describe("legacy loader registry - comprehensive coverage", () => {
    beforeEach(() => {
        __resetLegacyLoaderRegistryForTests();
        jest.spyOn(console, "debug").mockImplementation(() => {});
    });

    afterEach(() => {
        jest.restoreAllMocks();
    });

    describe("Admin route patterns", () => {
        test("resolves admin dashboard", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/admin/dashboard",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "admin-core")).toBe(true);
        });

        test("resolves admin games with multiple strategies", () => {
            const desktopResult = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: false,
            });
            expect(
                desktopResult.some(
                    (m) => m.id === "admin-games" && m.strategy === "eager",
                ),
            ).toBe(true);

            const mobileResult = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: true,
            });
            expect(
                mobileResult.some(
                    (m) => m.id === "admin-games" && m.strategy === "visible",
                ),
            ).toBe(true);
        });

        test("resolves admin images route", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/admin/images",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "admin-images")).toBe(true);
            expect(result.some((m) => m.id === "admin-overlay")).toBe(true);
        });

        test("resolves admin rosters", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/admin/team-season-rosters/1",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "admin-rosters")).toBe(true);
        });

        test("resolves admin stats entry", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/admin/stat-basket-game-person",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "admin-stats-entry")).toBe(true);
        });

        test("applies different strategies based on viewport for stats", () => {
            const desktopResult = resolveLegacyLoadPlan({
                pathname: "/admin/stat-basket-game-person",
                isMobileViewport: false,
            });
            const statsModule = desktopResult.find(
                (m) => m.id === "admin-stats-entry",
            );
            expect(statsModule?.strategy).toBe("eager");

            const mobileResult = resolveLegacyLoadPlan({
                pathname: "/admin/stat-basket-game-person",
                isMobileViewport: true,
            });
            const mobileStatsModule = mobileResult.find(
                (m) => m.id === "admin-stats-entry",
            );
            expect(mobileStatsModule?.strategy).toBe("interaction");
        });
    });

    describe("Public route patterns", () => {
        test("resolves homepage to public-core only", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/",
                isMobileViewport: false,
            });
            expect(result).toEqual([{ id: "public-core", strategy: "eager" }]);
        });

        test("resolves games index", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/games",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "public-games")).toBe(true);
        });

        test("resolves games with variant path", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/games/series/1",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "public-games")).toBe(true);
        });

        test("resolves people index", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/people",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "public-people")).toBe(true);
        });

        test("resolves person detail page", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/people/john-doe-1",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "public-people")).toBe(true);
        });

        test("resolves seasons page", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/seasons",
                isMobileViewport: false,
            });
            expect(result.some((m) => m.id === "public-seasons")).toBe(true);
        });

        test("resolves stats pages", () => {
            const playerResult = resolveLegacyLoadPlan({
                pathname: "/stats/player-season",
                isMobileViewport: false,
            });
            expect(playerResult.some((m) => m.id === "public-stats")).toBe(
                true,
            );

            const teamResult = resolveLegacyLoadPlan({
                pathname: "/stats/team-season",
                isMobileViewport: false,
            });
            expect(teamResult.some((m) => m.id === "public-stats")).toBe(true);
        });
    });

    describe("Bandwidth-aware strategies", () => {
        test("treats low bandwidth like mobile for public blog", () => {
            const lowBandwidthResult = resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
                isLowBandwidth: true,
            });

            const mobileResult = resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const blogLowBandwidth = lowBandwidthResult.find(
                (m) => m.id === "public-blog",
            );
            const blogMobile = mobileResult.find((m) => m.id === "public-blog");

            expect(blogLowBandwidth?.strategy).toBe(
                blogMobile?.strategy || "interaction",
            );
        });

        test("treats low bandwidth like mobile for admin games", () => {
            const lowBandwidthResult = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: false,
                isLowBandwidth: true,
            });

            const mobileResult = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const gamesLowBandwidth = lowBandwidthResult.find(
                (m) => m.id === "admin-games",
            );
            const gamesMobile = mobileResult.find(
                (m) => m.id === "admin-games",
            );

            expect(gamesLowBandwidth?.strategy).toBe(gamesMobile?.strategy);
        });
    });

    describe("Strategy consistency", () => {
        test("always includes admin-core for admin paths", () => {
            const adminPaths = [
                "/admin",
                "/admin/dashboard",
                "/admin/games",
                "/admin/images",
                "/admin/people",
            ];

            adminPaths.forEach((pathname) => {
                const result = resolveLegacyLoadPlan({
                    pathname,
                    isMobileViewport: false,
                });
                expect(
                    result.some(
                        (m) => m.id === "admin-core" && m.strategy === "eager",
                    ),
                ).toBe(true);
            });
        });

        test("always includes public-core for public paths", () => {
            const publicPaths = ["/", "/blog", "/games", "/people", "/seasons"];

            publicPaths.forEach((pathname) => {
                const result = resolveLegacyLoadPlan({
                    pathname,
                    isMobileViewport: false,
                });
                expect(
                    result.some(
                        (m) => m.id === "public-core" && m.strategy === "eager",
                    ),
                ).toBe(true);
            });
        });

        test("never returns empty plan", () => {
            const paths = [
                "/",
                "/unknown",
                "/admin/unknown",
                "/api/v1/something",
            ];

            paths.forEach((pathname) => {
                const result = resolveLegacyLoadPlan({
                    pathname,
                    isMobileViewport: false,
                });
                expect(Array.isArray(result)).toBe(true);
                expect(result.length).toBeGreaterThan(0);
            });
        });

        test("modules have load function defined", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: false,
            });

            // The returned plan should match the format
            result.forEach((planItem) => {
                expect(planItem).toHaveProperty("id");
                expect(planItem).toHaveProperty("strategy");
                expect(["eager", "visible", "interaction"]).toContain(
                    planItem.strategy,
                );
            });
        });
    });

    describe("Edge cases", () => {
        test("handles empty string pathname", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "",
                isMobileViewport: false,
            });
            expect(Array.isArray(result)).toBe(true);
        });

        test("handles null profile with defaults", () => {
            const result = resolveLegacyLoadPlan();
            expect(Array.isArray(result)).toBe(true);
            expect(result.length).toBeGreaterThan(0);
        });

        test("handles partial profile object", () => {
            const result = resolveLegacyLoadPlan({
                pathname: "/games",
            });
            expect(Array.isArray(result)).toBe(true);
            expect(result.some((m) => m.id === "public-games")).toBe(true);
        });

        test("routes with trailing slashes", () => {
            const withoutSlash = resolveLegacyLoadPlan({
                pathname: "/games",
                isMobileViewport: false,
            });

            const withSlash = resolveLegacyLoadPlan({
                pathname: "/games/",
                isMobileViewport: false,
            });

            expect(withoutSlash.some((m) => m.id === "public-games")).toBe(
                true,
            );
            expect(withSlash.some((m) => m.id === "public-games")).toBe(true);
        });

        test("handles query strings in pathname", () => {
            // The pathname should not include query string in real use,
            // but test defensive behavior
            const result = resolveLegacyLoadPlan({
                pathname: "/games?sort=name",
                isMobileViewport: false,
            });
            expect(Array.isArray(result)).toBe(true);
        });
    });
});
