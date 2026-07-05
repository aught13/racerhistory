/* global describe, expect, test */

import { resolveLegacyLoadPlan } from "../lib/legacy_loader_registry.js";

describe("legacy loader registry", () => {
    test("returns no route-scoped legacy modules for public routes", () => {
        const plans = [
            resolveLegacyLoadPlan({ pathname: "/people", isMobileViewport: false }),
            resolveLegacyLoadPlan({ pathname: "/seasons/1", isMobileViewport: false }),
            resolveLegacyLoadPlan({ pathname: "/games/series", isMobileViewport: false }),
            resolveLegacyLoadPlan({ pathname: "/blog", isMobileViewport: false }),
        ];

        plans.forEach((plan) => {
            expect(plan).toEqual([]);
        });
    });

    test("returns no route-scoped legacy modules for admin routes", () => {
        const plan = resolveLegacyLoadPlan({
            pathname: "/admin/games/add",
            isMobileViewport: false,
            isLowBandwidth: false,
        });

        expect(plan).toEqual([]);
    });
});
