import { test, expect } from "@playwright/test";

import { loginToAdmin } from "./support/auth.js";

function viewportWidth(page) {
    return page.viewportSize()?.width ?? 1280;
}

function expectedLayoutVariant(width) {
    if (width >= 1600) {
        return "ultrawide";
    }

    if (width < 992) {
        return "mobile";
    }

    return "desktop";
}

function expectedGamesStrategy(width) {
    return width < 992 ? "visible" : "eager";
}

function expectedBlogStrategy(width) {
    return width < 992 ? "interaction" : "eager";
}

function expectedStatsEntryStrategy(width) {
    return width < 992 ? "interaction" : "eager";
}

async function getLoaderDebug(page) {
    return page.evaluate(() => window.__RH_LOADER_DEBUG__ ?? null);
}

async function waitForLoaderPlan(page, pathFragment) {
    await page.waitForFunction(
        (fragment) =>
            Boolean(
                window.__RH_LOADER_DEBUG__?.lastPlan?.pathname?.includes(
                    fragment,
                ),
            ),
        pathFragment,
        { timeout: 5000 },
    );

    return getLoaderDebug(page);
}

async function isAdminAccessBlocked(page) {
    return (
        page.url().includes("/login") ||
        (await page
            .locator("text=You must be logged in to access the admin area.")
            .count()) > 0
    );
}

test.describe("loader strategy - public routes", () => {
    test("games route plan follows viewport strategy", async ({ page }) => {
        await page.goto("/games/all", { waitUntil: "domcontentloaded" });
        const debug = await waitForLoaderPlan(page, "/games/all");

        const width = viewportWidth(page);
        const expectedVariant = expectedLayoutVariant(width);
        const expectedStrategy = expectedGamesStrategy(width);

        expect(debug).toBeTruthy();
        expect(debug.lastPlan.pathname).toContain("/games/all");

        const gamesModule = debug.lastPlan.modules.find(
            (entry) => entry.id === "public-games",
        );
        expect(gamesModule).toBeTruthy();
        expect(gamesModule.strategy).toBe(expectedStrategy);

        await page.waitForFunction(
            () =>
                Boolean(
                    document.body?.dataset?.layoutVariant ||
                        document.querySelector("[data-controller~='public-shell']")
                            ?.dataset?.layoutVariant,
                ),
            undefined,
            { timeout: 5000 },
        );

        const variant = await page.evaluate(() => {
            const shellVariant = document.querySelector(
                "[data-controller~='public-shell']",
            )?.dataset?.layoutVariant;
            return document.body?.dataset?.layoutVariant || shellVariant || "";
        });
        expect(variant).toBe(expectedVariant);
    });

    test("blog interaction module is deferred on mobile until interaction", async ({
        page,
    }) => {
        await page.goto("/blog", { waitUntil: "domcontentloaded" });

        const width = viewportWidth(page);
        const expectedStrategy = expectedBlogStrategy(width);

        const debug = await waitForLoaderPlan(page, "/blog");
        expect(debug).toBeTruthy();

        const blogModule = debug.lastPlan.modules.find(
            (entry) => entry.id === "public-blog",
        );
        expect(blogModule).toBeTruthy();
        expect(blogModule.strategy).toBe(expectedStrategy);

        if (expectedStrategy !== "interaction") {
            await page.waitForFunction(
                () =>
                    Array.isArray(window.__RH_LOADER_DEBUG__?.loadedModules) &&
                    window.__RH_LOADER_DEBUG__.loadedModules.includes(
                        "public-blog",
                    ),
                undefined,
                { timeout: 5000 },
            );
            return;
        }

        await page.waitForTimeout(400);
        const beforeInteractionLoaded = await page.evaluate(() =>
            window.__RH_LOADER_DEBUG__?.loadedModules?.includes("public-blog"),
        );
        expect(beforeInteractionLoaded).toBe(false);

        await page.locator("body").click({ position: { x: 30, y: 30 } });

        await page.waitForFunction(
            () =>
                Array.isArray(window.__RH_LOADER_DEBUG__?.loadedModules) &&
                window.__RH_LOADER_DEBUG__.loadedModules.includes("public-blog"),
            undefined,
            { timeout: 2000 },
        );
    });
});

test.describe("loader strategy - admin routes", () => {
    test.beforeEach(async ({ page }) => {
        const loggedIn = await loginToAdmin(page, {
            waitUntil: "domcontentloaded",
            timeout: 10000,
        });

        const blockedFromAdmin =
            !loggedIn ||
            page.url().includes("/login") ||
            (await page
                .locator("text=You must be logged in to access the admin area.")
                .count()) > 0;

        test.skip(blockedFromAdmin, "Could not log in to the e2e admin account");
    });

    test("admin users route uses viewport-aware admin module strategy", async ({
        page,
    }) => {
        await page.goto("/admin/users", { waitUntil: "domcontentloaded" });

        if (await isAdminAccessBlocked(page)) {
            const reloggedIn = await loginToAdmin(page, {
                waitUntil: "domcontentloaded",
                timeout: 10000,
            });

            if (reloggedIn) {
                await page.goto("/admin/users", {
                    waitUntil: "domcontentloaded",
                });
            }
        }

        test.skip(
            await isAdminAccessBlocked(page),
            "Could not reach /admin/users with the e2e admin account",
        );

        const debug = await waitForLoaderPlan(page, "/admin/users");

        const width = viewportWidth(page);
        const expectedUsersStrategy = width < 992 ? "visible" : "eager";

        expect(debug).toBeTruthy();

        const core = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-core",
        );
        const users = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-users",
        );
        const overlay = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-overlay",
        );

        expect(core).toBeTruthy();
        expect(core.strategy).toBe("eager");

        expect(overlay).toBeTruthy();
        expect(overlay.strategy).toBe(width < 992 ? "interaction" : "eager");

        expect(users).toBeTruthy();
        expect(users.strategy).toBe(expectedUsersStrategy);

        await page.waitForFunction(
            () =>
                Array.isArray(window.__RH_LOADER_DEBUG__?.loadedModules) &&
                window.__RH_LOADER_DEBUG__.loadedModules.includes("admin-core"),
            undefined,
            { timeout: 5000 },
        );
    });

    test("admin stats-entry route uses interaction deferral on constrained clients", async ({
        page,
    }) => {
        await page.goto("/admin/stat-basket-game-person/add/1", {
            waitUntil: "domcontentloaded",
        });

        if (await isAdminAccessBlocked(page)) {
            const reloggedIn = await loginToAdmin(page, {
                waitUntil: "domcontentloaded",
                timeout: 10000,
            });

            if (reloggedIn) {
                await page.goto("/admin/stat-basket-game-person/add/1", {
                    waitUntil: "domcontentloaded",
                });
            }
        }

        test.skip(
            await isAdminAccessBlocked(page),
            "Could not reach /admin/stat-basket-game-person/add/1 with the e2e admin account",
        );

        const debug = await waitForLoaderPlan(
            page,
            "/admin/stat-basket-game-person/add/1",
        );

        const width = viewportWidth(page);
        const expectedStrategy = expectedStatsEntryStrategy(width);

        expect(debug).toBeTruthy();

        const core = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-core",
        );
        const overlay = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-overlay",
        );
        const statsEntry = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-stats-entry",
        );
        const games = debug.lastPlan.modules.find(
            (entry) => entry.id === "admin-games",
        );

        expect(core).toBeTruthy();
        expect(core.strategy).toBe("eager");

        expect(overlay).toBeTruthy();
        expect(overlay.strategy).toBe(width < 992 ? "interaction" : "eager");

        expect(statsEntry).toBeTruthy();
        expect(statsEntry.strategy).toBe(expectedStrategy);

        expect(games).toBeUndefined();

        if (expectedStrategy !== "interaction") {
            await page.waitForFunction(
                () =>
                    Array.isArray(window.__RH_LOADER_DEBUG__?.loadedModules) &&
                    window.__RH_LOADER_DEBUG__.loadedModules.includes(
                        "admin-stats-entry",
                    ),
                undefined,
                { timeout: 5000 },
            );
            return;
        }

        await page.waitForTimeout(400);

        const beforeInteractionLoaded = await page.evaluate(() =>
            window.__RH_LOADER_DEBUG__?.loadedModules?.includes(
                "admin-stats-entry",
            ),
        );
        expect(beforeInteractionLoaded).toBe(false);

        await page.locator("body").click({ position: { x: 30, y: 30 } });

        await page.waitForFunction(
            () =>
                Array.isArray(window.__RH_LOADER_DEBUG__?.loadedModules) &&
                window.__RH_LOADER_DEBUG__.loadedModules.includes(
                    "admin-stats-entry",
                ),
            undefined,
            { timeout: 5000 },
        );
    });
});
