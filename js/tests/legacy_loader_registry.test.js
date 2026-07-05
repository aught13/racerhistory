/* global describe, expect, test */

import { resolveLegacyLoadPlan } from "../lib/legacy_loader_registry.js";

describe("legacy loader registry", () => {
    test("returns a public app load plan for public routes", () => {
        const plans = [
            resolveLegacyLoadPlan({
                pathname: "/people",
                isMobileViewport: false,
            }),
            resolveLegacyLoadPlan({
                pathname: "/seasons/1",
                isMobileViewport: false,
            }),
            resolveLegacyLoadPlan({
                pathname: "/games/series",
                isMobileViewport: false,
            }),
            resolveLegacyLoadPlan({
                pathname: "/blog",
                isMobileViewport: false,
            }),
        ];

        plans.forEach((plan) => {
            expect(plan).toEqual([{ id: "public-app", strategy: "eager" }]);
        });

        expect(
            resolveLegacyLoadPlan({
                pathname: "/people",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([{ id: "public-app", strategy: "idle" }]);
    });

    test("returns an admin app load plan for admin routes", () => {
        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/games/add",
                isMobileViewport: false,
                isLowBandwidth: false,
            }),
        ).toEqual([{ id: "admin-app", strategy: "eager" }]);

        expect(
            resolveLegacyLoadPlan({
                pathname: "/admin/games/add",
                isMobileViewport: true,
                isLowBandwidth: false,
            }),
        ).toEqual([{ id: "admin-app", strategy: "idle" }]);
    });
});
