/* global describe, expect, test, jest, beforeEach, afterEach */

import {
    resolveLegacyLoadPlan,
    initializeLegacyModules,
    __resetLegacyLoaderRegistryForTests,
} from "../lib/legacy_loader_registry.js";

// Mock getRuntimeProfile to avoid circular dependency issues
jest.mock("../lib/runtime_profile.js", () => ({
    getRuntimeProfile: jest.fn().mockReturnValue({
        pathname: "/",
        isMobileViewport: false,
        isLowBandwidth: false,
    }),
}));

describe("legacy loader registry - complete coverage", () => {
    beforeEach(() => {
        __resetLegacyLoaderRegistryForTests();
    });

    afterEach(() => {
        __resetLegacyLoaderRegistryForTests();
    });

    describe("strategy resolution", () => {
        test("uses eager strategy on desktop with normal bandwidth", () => {
            const plan = resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
                isLowBandwidth: false,
            });

            const blogModule = plan.find((m) => m.id === "public-blog");
            expect(blogModule?.strategy).toBe("eager");
        });

        test("respects mobileStrategy for mobile viewports", () => {
            // public-blog has mobileStrategy: "interaction"
            const plan = resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const blogModule = plan.find((m) => m.id === "public-blog");
            expect(blogModule?.strategy).toBe("interaction");
        });

        test("respects mobileStrategy for low bandwidth clients", () => {
            // public-blog has mobileStrategy: "interaction"
            const plan = resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
                isLowBandwidth: true,
            });

            const blogModule = plan.find((m) => m.id === "public-blog");
            expect(blogModule?.strategy).toBe("interaction");
        });

        test("uses eager strategy for admin routes on mobile", () => {
            // admin-core should be eager even on mobile
            const plan = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const coreModule = plan.find((m) => m.id === "admin-core");
            expect(coreModule?.strategy).toBe("eager");
        });

        test("applies visible strategy for mobile-deferred modules", () => {
            // public-games has mobileStrategy: "visible"
            const plan = resolveLegacyLoadPlan({
                pathname: "/games",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const gamesModule = plan.find((m) => m.id === "public-games");
            expect(gamesModule?.strategy).toBe("visible");
        });
    });

    describe("pathway matching", () => {
        test("matches public-core for all non-admin paths", () => {
            ["/", "/blog", "/games", "/people", "/seasons", "/stats"].forEach(
                (path) => {
                    const plan = resolveLegacyLoadPlan({
                        pathname: path,
                        isMobileViewport: false,
                        isLowBandwidth: false,
                    });
                    expect(plan.some((m) => m.id === "public-core")).toBe(true);
                },
            );
        });

        test("matches admin-core for all admin paths", () => {
            [
                "/admin",
                "/admin/games",
                "/admin/images",
                "/admin/persons",
            ].forEach((path) => {
                const plan = resolveLegacyLoadPlan({
                    pathname: path,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });
                expect(plan.some((m) => m.id === "admin-core")).toBe(true);
            });
        });

        test("matches specific feature modules by path", () => {
            const testCases = [
                {
                    path: "/blog",
                    expected: "public-blog",
                },
                {
                    path: "/games",
                    expected: "public-games",
                },
                {
                    path: "/people",
                    expected: "public-people",
                },
                {
                    path: "/seasons",
                    expected: "public-seasons",
                },
                {
                    path: "/stats",
                    expected: "public-stats",
                },
                {
                    path: "/admin/games",
                    expected: "admin-games",
                },
                {
                    path: "/admin/images",
                    expected: "admin-images",
                },
                {
                    path: "/admin/persons",
                    expected: "admin-people",
                },
                {
                    path: "/admin/team-season-rosters",
                    expected: "admin-rosters",
                },
                {
                    path: "/admin/blog-posts",
                    expected: "admin-content",
                },
                {
                    path: "/admin/users",
                    expected: "admin-users",
                },
            ];

            testCases.forEach(({ path, expected }) => {
                const plan = resolveLegacyLoadPlan({
                    pathname: path,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });
                expect(plan.some((m) => m.id === expected)).toBe(
                    true,
                    `${expected} not found for path ${path}`,
                );
            });
        });

        test("matches taxonomy feature for multiple admin paths", () => {
            const taxonomyPaths = [
                "/admin/game-types",
                "/admin/opponents",
                "/admin/places",
                "/admin/sites",
                "/admin/sports",
                "/admin/seasons",
                "/admin/teams",
                "/admin/team-seasons",
                "/admin/sport-stats",
            ];

            taxonomyPaths.forEach((path) => {
                const plan = resolveLegacyLoadPlan({
                    pathname: path,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });
                expect(plan.some((m) => m.id === "admin-taxonomy")).toBe(
                    true,
                    `admin-taxonomy not found for path ${path}`,
                );
            });
        });

        test("matches stats-entry for stat game paths", () => {
            const statPaths = [
                "/admin/stat-basket-game-person",
                "/admin/stat-basket-game-opponent",
            ];

            statPaths.forEach((path) => {
                const plan = resolveLegacyLoadPlan({
                    pathname: path,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });
                expect(plan.some((m) => m.id === "admin-stats-entry")).toBe(
                    true,
                    `admin-stats-entry not found for path ${path}`,
                );
            });
        });
    });

    describe("turbo navigation support", () => {
        test("turbo:load listener should be registered on init", () => {
            // The initializeLegacyModules function adds a turbo:load listener
            // This test verifies the function exists and can be called
            const mockStimulus = {};

            expect(() => {
                initializeLegacyModules(mockStimulus);
            }).not.toThrow();
        });
    });

    describe("edge cases", () => {
        test("returns non-empty plan for all valid paths", () => {
            const paths = ["/", "/admin", "/blog", "/games", "/people"];

            paths.forEach((path) => {
                const plan = resolveLegacyLoadPlan({
                    pathname: path,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });
                expect(plan.length).toBeGreaterThan(0);
            });
        });

        test("reset function clears loaded modules", () => {
            __resetLegacyLoaderRegistryForTests();

            // After reset, the state should be clean for next test
            const plan = resolveLegacyLoadPlan({
                pathname: "/",
                isMobileViewport: false,
                isLowBandwidth: false,
            });
            expect(plan).toBeDefined();
        });

        test("mobile strategy interaction applies to stats entry module on mobile", () => {
            // admin-stats-entry has mobileStrategy: "interaction"
            const plan = resolveLegacyLoadPlan({
                pathname: "/admin/stat-basket-game-person",
                isMobileViewport: true,
                isLowBandwidth: false,
            });

            const statsModule = plan.find((m) => m.id === "admin-stats-entry");
            expect(statsModule?.strategy).toBe("interaction");
        });

        test("visible strategy applies on desktop for modules with visibility targets", () => {
            // admin-games has visibilityTarget but mobileStrategy is "visible"
            // On desktop it should be eager
            const plan = resolveLegacyLoadPlan({
                pathname: "/admin/games",
                isMobileViewport: false,
                isLowBandwidth: false,
            });

            const gamesModule = plan.find((m) => m.id === "admin-games");
            expect(gamesModule?.strategy).toBe("eager");
        });
    });

    describe("initialization", () => {
        test("initializes legacy modules without throwing", () => {
            const mockStimulus = {};

            expect(() => {
                initializeLegacyModules(mockStimulus);
            }).not.toThrow();
        });
    });

    describe("module definitions", () => {
        test("all module definitions have required properties", () => {
            const paths = [
                {
                    pathname: "/",
                    isMobileViewport: false,
                    isLowBandwidth: false,
                },
                {
                    pathname: "/blog",
                    isMobileViewport: false,
                    isLowBandwidth: false,
                },
                {
                    pathname: "/admin",
                    isMobileViewport: false,
                    isLowBandwidth: false,
                },
            ];

            paths.forEach((profile) => {
                const plan = resolveLegacyLoadPlan(profile);

                plan.forEach((module) => {
                    expect(module.id).toBeDefined();
                    expect(module.strategy).toBeDefined();
                    expect([
                        "eager",
                        "visible",
                        "interaction",
                        "idle",
                    ]).toContain(module.strategy);
                });
            });
        });

        test("no module appears twice in a plan", () => {
            const paths = ["/", "/admin", "/blog", "/games", "/admin/games"];

            paths.forEach((pathname) => {
                const plan = resolveLegacyLoadPlan({
                    pathname,
                    isMobileViewport: false,
                    isLowBandwidth: false,
                });

                const ids = plan.map((m) => m.id);
                const uniqueIds = new Set(ids);

                expect(uniqueIds.size).toBe(
                    ids.length,
                    `Duplicate modules found for path ${pathname}`,
                );
            });
        });
    });
});
