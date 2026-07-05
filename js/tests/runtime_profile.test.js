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
});
