/* global describe, expect, test */

import {
    getNetworkProfile,
    getRuntimeProfile,
} from "../lib/runtime_profile.js";

describe("runtime profile", () => {
    test("marks low bandwidth when saveData is enabled", () => {
        const profile = getNetworkProfile({
            effectiveType: "4g",
            saveData: true,
        });

        expect(profile.isLowBandwidth).toBe(true);
        expect(profile.saveData).toBe(true);
        expect(profile.effectiveType).toBe("4g");
    });

    test("derives route and viewport hints from explicit options", () => {
        const profile = getRuntimeProfile({
            pathname: "/stats/player-season",
            viewportWidth: 390,
            connection: {
                effectiveType: "3g",
                saveData: false,
            },
        });

        expect(profile.pathname).toBe("/stats/player-season");
        expect(profile.isAdminPath).toBe(false);
        expect(profile.isMobileViewport).toBe(true);
        expect(profile.isLowBandwidth).toBe(true);
    });

    test("detects admin path from pathname", () => {
        const profile = getRuntimeProfile({
            pathname: "/admin/games",
            viewportWidth: 1024,
        });

        expect(profile.isAdminPath).toBe(true);
    });

    test("handles missing connection gracefully", () => {
        const profile = getRuntimeProfile({
            pathname: "/games",
            viewportWidth: 1024,
            connection: undefined,
        });

        expect(profile.pathname).toBe("/games");
        expect(profile.isLowBandwidth).toBe(false);
    });

    test("getNetworkProfile handles null connection", () => {
        const profile = getNetworkProfile(null);

        expect(profile.effectiveType).toBe("");
        expect(profile.saveData).toBe(false);
        expect(profile.isLowBandwidth).toBe(false);
    });

    test("getNetworkProfile handles undefined connection", () => {
        const profile = getNetworkProfile(undefined);

        expect(profile.effectiveType).toBe("");
        expect(profile.saveData).toBe(false);
        expect(profile.isLowBandwidth).toBe(false);
    });

    test("detects low bandwidth for 2g", () => {
        const profile = getNetworkProfile({
            effectiveType: "2g",
            saveData: false,
        });

        expect(profile.isLowBandwidth).toBe(true);
    });

    test("detects low bandwidth for slow-2g", () => {
        const profile = getNetworkProfile({
            effectiveType: "slow-2g",
            saveData: false,
        });

        expect(profile.isLowBandwidth).toBe(true);
    });

    test("getRuntimeProfile handles desktop viewport", () => {
        const profile = getRuntimeProfile({
            pathname: "/games",
            viewportWidth: 1200,
        });

        expect(profile.isMobileViewport).toBe(false);
    });

    test("getRuntimeProfile respects mobile UA even with large viewport", () => {
        // Mock navigator.userAgent for this test
        const originalUserAgent = navigator.userAgent;
        Object.defineProperty(navigator, "userAgent", {
            value: "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)",
            configurable: true,
        });

        const profile = getRuntimeProfile({
            pathname: "/games",
            viewportWidth: 1200, // Desktop width but mobile UA
        });

        expect(profile.isMobileViewport).toBe(true);

        Object.defineProperty(navigator, "userAgent", {
            value: originalUserAgent,
            configurable: true,
        });
    });
});
