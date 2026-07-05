import { getRuntimeProfile } from "./runtime_profile.js";

const IDLE_TIMEOUT_MS = 1500;

const LEGACY_MODULES = [];

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

async function loadModule(moduleDefinition) {
    if (loadedModules.has(moduleDefinition.id)) {
        return;
    }

    loadedModules.add(moduleDefinition.id);

    try {
        await moduleDefinition.load();
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
    profile = getRuntimeProfile(),
) {
    const matchingModules = LEGACY_MODULES.filter((moduleDefinition) =>
        moduleDefinition.matches(profile.pathname),
    );

    for (const moduleDefinition of matchingModules) {
        const strategy = strategyForModule(moduleDefinition, profile);
        if (strategy === "idle") {
            queueIdle(() => {
                void loadModule(moduleDefinition);
            });
            continue;
        }

        void loadModule(moduleDefinition);
    }
}

export function initializeLegacyModules() {
    loadLegacyModulesForCurrentRoute();

    if (hasTurboListener || typeof document === "undefined") {
        return;
    }

    document.addEventListener("turbo:load", () => {
        loadLegacyModulesForCurrentRoute();
    });

    hasTurboListener = true;
}

export function __resetLegacyLoaderRegistryForTests() {
    loadedModules.clear();
    hasTurboListener = false;
}
