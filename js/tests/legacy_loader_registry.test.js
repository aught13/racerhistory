/* global describe, expect, test */

import { resolveLegacyLoadPlan } from "../lib/legacy_loader_registry.js";

describe("legacy loader registry", () => {
    test("returns feature-scoped public plans by route", () => {
        expect(
            resolveLegacyLoadPlan({ pathname: "/", isMobileViewport: false }),
        ).toEqual([{ id: "public-core", strategy: "eager" }]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-blog", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/games/series",
                isMobileViewport: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-games", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/people",
                isMobileViewport: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-people", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/seasons/1",
                isMobileViewport: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-seasons", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/stats/player-season",
                isMobileViewport: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-stats", strategy: "eager" },
        ]);
    });

    test("applies mobile-aware strategies for constrained clients", () => {
        expect(
            resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-blog", strategy: "interaction" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/people",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-people", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/games/add",
                isMobileViewport: false,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "eager" },
            { id: "admin-games", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/games/add",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-games", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/users",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-users", strategy: "visible" },
        ]);
    });

    test("covers the remaining admin route buckets", () => {
        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/stat-basket-game-person/add/1",
                isMobileViewport: false,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "eager" },
            { id: "admin-stats-entry", strategy: "eager" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/images",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-images", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/persons/edit/1",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-people", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/team-season-rosters/add",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-rosters", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/sport-stats/1",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-taxonomy", strategy: "visible" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/blog-posts/add",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-content", strategy: "interaction" },
        ]);
    });

    test("treats low-bandwidth clients like constrained clients", () => {
        expect(
            resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
                isLowBandwidth: true,
            }),
        ).toEqual([
            { id: "public-core", strategy: "eager" },
            { id: "public-blog", strategy: "interaction" },
        ]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/images",
                isMobileViewport: false,
                isLowBandwidth: true,
            }),
        ).toEqual([
            { id: "admin-core", strategy: "eager" },
            { id: "admin-overlay", strategy: "interaction" },
            { id: "admin-images", strategy: "visible" },
        ]);
    });
});
