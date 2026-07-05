import { getRuntimeProfile } from "./runtime_profile.js";

const IDLE_TIMEOUT_MS = 1500;

const LEGACY_MODULES = [
    {
        id: "public-app",
        matches: (pathname) => !pathname.startsWith("/admin"),
        mobileStrategy: "idle",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_app.js");
            module.registerPublicAppControllers(stimulus);
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
let hasTurboListener = false;

function strategyForModule(moduleDefinition, profile) {
    if (
        moduleDefinition.mobileStrategy === "idle" &&
        (profile.isMobileViewport || profile.isLowBandwidth)
    ) {
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
    hasTurboListener = false;
}
