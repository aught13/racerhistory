import { getRuntimeProfile } from "./runtime_profile.js";

const IDLE_TIMEOUT_MS = 1500;
const INTERACTION_EVENTS = ["pointerdown", "keydown", "touchstart"];

function matchesAnyPrefix(pathname, prefixes) {
    return prefixes.some((prefix) => pathname.startsWith(prefix));
}

const LEGACY_MODULES = [
    {
        id: "public-core",
        matches: (pathname) => !pathname.startsWith("/admin"),
        load: async (stimulus) => {
            const module = await import("../route_modules/public_core.js");
            module.registerPublicCoreControllers(stimulus);
        },
    },
    {
        id: "public-blog",
        matches: (pathname) => pathname.startsWith("/blog"),
        mobileStrategy: "interaction",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_blog.js");
            module.registerPublicBlogControllers(stimulus);
        },
    },
    {
        id: "public-games",
        matches: (pathname) => pathname.startsWith("/games"),
        mobileStrategy: "visible",
        visibilityTarget:
            "#games-results-table, [data-controller~='games-search'], [data-controller~='game-view']",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_games.js");
            module.registerPublicGamesControllers(stimulus);
        },
    },
    {
        id: "public-people",
        matches: (pathname) =>
            matchesAnyPrefix(pathname, ["/people", "/person"]),
        mobileStrategy: "visible",
        visibilityTarget:
            "#people-table, [data-controller~='person-game-log-tabs'], [data-controller~='person-blog-popovers']",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_people.js");
            module.registerPublicPeopleControllers(stimulus);
        },
    },
    {
        id: "public-seasons",
        matches: (pathname) => pathname.startsWith("/seasons"),
        mobileStrategy: "visible",
        visibilityTarget:
            "#seasons-table, [data-controller~='seasons-page'], [data-controller~='season-view']",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_seasons.js");
            module.registerPublicSeasonsControllers(stimulus);
        },
    },
    {
        id: "public-stats",
        matches: (pathname) => pathname.startsWith("/stats"),
        mobileStrategy: "visible",
        visibilityTarget:
            "#stats-results-table, [data-controller~='stats-page']",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_stats.js");
            module.registerPublicStatsControllers(stimulus);
        },
    },
    {
        id: "admin-core",
        matches: (pathname) => pathname.startsWith("/admin"),
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_core.js");
            module.registerAdminCoreControllers(stimulus);
        },
    },
    {
        id: "admin-app",
        matches: (pathname) => pathname.startsWith("/admin"),
        mobileStrategy: "idle",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_app.js");
            module.registerAdminAppControllers(stimulus);
        },
    },
];

const loadedModules = new Set();
const visibilityWaitModules = new Set();
const interactionWaitModules = new Set();
let hasTurboListener = false;

function strategyForModule(moduleDefinition, profile) {
    const mobileOrConstrained =
        profile.isMobileViewport || profile.isLowBandwidth;

    if (!mobileOrConstrained) {
        return "eager";
    }

    if (
        moduleDefinition.mobileStrategy === "visible" ||
        moduleDefinition.mobileStrategy === "interaction"
    ) {
        return moduleDefinition.mobileStrategy;
    }

    if (moduleDefinition.mobileStrategy === "idle") {
        return "idle";
    }

    return "eager";
}

function queueIdle(callback) {
    if (typeof window !== "undefined" && "requestIdleCallback" in window) {
        window.requestIdleCallback(() => callback(), {
            timeout: IDLE_TIMEOUT_MS,
        });
        return;
    }

    window.setTimeout(() => callback(), 50);
}

async function loadModule(moduleDefinition, stimulus) {
    if (loadedModules.has(moduleDefinition.id)) {
        return;
    }

    loadedModules.add(moduleDefinition.id);

    try {
        await moduleDefinition.load(stimulus);
    } catch (error) {
        loadedModules.delete(moduleDefinition.id);
        console.warn(
            `Failed to load legacy module ${moduleDefinition.id}`,
            error,
        );
    }
}

function queueVisible(moduleDefinition, stimulus) {
    if (visibilityWaitModules.has(moduleDefinition.id)) {
        return;
    }

    const selector = moduleDefinition.visibilityTarget;
    const target =
        typeof selector === "string" ? document.querySelector(selector) : null;

    const observerCtor =
        typeof window !== "undefined" ? window.IntersectionObserver : undefined;

    if (!target || typeof observerCtor !== "function") {
        queueIdle(() => {
            void loadModule(moduleDefinition, stimulus);
        });
        return;
    }

    visibilityWaitModules.add(moduleDefinition.id);

    const observer = new observerCtor((entries) => {
        const hasVisibleEntry = entries.some((entry) => entry.isIntersecting);
        if (!hasVisibleEntry) {
            return;
        }

        observer.disconnect();
        visibilityWaitModules.delete(moduleDefinition.id);
        void loadModule(moduleDefinition, stimulus);
    });

    observer.observe(target);
}

function queueInteraction(moduleDefinition, stimulus) {
    if (interactionWaitModules.has(moduleDefinition.id)) {
        return;
    }

    interactionWaitModules.add(moduleDefinition.id);

    const onInteraction = () => {
        INTERACTION_EVENTS.forEach((eventName) => {
            document.removeEventListener(eventName, onInteraction, true);
        });
        interactionWaitModules.delete(moduleDefinition.id);
        void loadModule(moduleDefinition, stimulus);
    };

    INTERACTION_EVENTS.forEach((eventName) => {
        document.addEventListener(eventName, onInteraction, {
            once: true,
            capture: true,
        });
    });

    queueIdle(() => {
        if (!interactionWaitModules.has(moduleDefinition.id)) {
            return;
        }

        onInteraction();
    });
}

export function resolveLegacyLoadPlan(profile = getRuntimeProfile()) {
    return LEGACY_MODULES.filter((moduleDefinition) =>
        moduleDefinition.matches(profile.pathname),
    ).map((moduleDefinition) => ({
        id: moduleDefinition.id,
        strategy: strategyForModule(moduleDefinition, profile),
    }));
}

export function loadLegacyModulesForCurrentRoute(
    stimulus,
    profile = getRuntimeProfile(),
) {
    const matchingModules = LEGACY_MODULES.filter((moduleDefinition) =>
        moduleDefinition.matches(profile.pathname),
    );

    for (const moduleDefinition of matchingModules) {
        const strategy = strategyForModule(moduleDefinition, profile);
        if (strategy === "idle") {
            queueIdle(() => {
                void loadModule(moduleDefinition, stimulus);
            });
            continue;
        }

        if (strategy === "visible") {
            queueVisible(moduleDefinition, stimulus);
            continue;
        }

        if (strategy === "interaction") {
            queueInteraction(moduleDefinition, stimulus);
            continue;
        }

        void loadModule(moduleDefinition, stimulus);
    }
}

export function initializeLegacyModules(stimulus) {
    loadLegacyModulesForCurrentRoute(stimulus);

    if (hasTurboListener || typeof document === "undefined") {
        return;
    }

    document.addEventListener("turbo:load", () => {
        loadLegacyModulesForCurrentRoute(stimulus);
    });

    hasTurboListener = true;
}

export function __resetLegacyLoaderRegistryForTests() {
    loadedModules.clear();
    visibilityWaitModules.clear();
    interactionWaitModules.clear();
    hasTurboListener = false;
}
