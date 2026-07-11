import { getRuntimeProfile } from "./runtime_profile.js";

const IDLE_TIMEOUT_MS = 1500;
// Include "click" so programmatic clicks in tests reliably trigger
// interaction-based module loading (Playwright dispatches click events).
const INTERACTION_EVENTS = ["pointerdown", "click", "keydown", "touchstart"];

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
        // Defer loading public games until the relevant section becomes
        // visible on small viewports to save network and CPU.
        mobileStrategy: "visible",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_games.js");
            module.registerPublicGamesControllers(stimulus);
        },
    },
    {
        id: "public-people",
        matches: (pathname) => pathname.startsWith("/people"),
        mobileStrategy: "visible",
        visibilityTarget: "[data-controller~='people-index']",
        load: async (stimulus) => {
            const module = await import("../route_modules/public_people.js");
            module.registerPublicPeopleControllers(stimulus);
        },
    },
    {
        id: "public-seasons",
        matches: (pathname) => pathname.startsWith("/seasons"),
        load: async (stimulus) => {
            const module = await import("../route_modules/public_seasons.js");
            module.registerPublicSeasonsControllers(stimulus);
        },
    },
    {
        id: "public-stats",
        matches: (pathname) => pathname.startsWith("/stats"),
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
        id: "admin-overlay",
        matches: (pathname) => pathname.startsWith("/admin"),
        mobileStrategy: "interaction",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_overlay.js");
            module.registerAdminOverlayControllers(stimulus);
        },
    },
    {
        id: "admin-games",
        matches: (pathname) => pathname.startsWith("/admin/games"),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='admin-games-index'], [data-controller~='admin-game-form'], [data-controller~='game-view'], [data-controller~='game-box-totals-toggle']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_games.js");
            module.registerAdminGamesControllers(stimulus);
        },
    },
    {
        id: "admin-stats-entry",
        matches: (pathname) =>
            matchesAnyPrefix(pathname, [
                "/admin/stat-basket-game-person",
                "/admin/stat-basket-game-opponent",
            ]),
        // Prefer interaction-based deferral on constrained/mobile clients
        // so the editor doesn't eagerly load heavier admin logic until the
        // user interacts with the form.
        mobileStrategy: "interaction",
        visibilityTarget:
            "#stat-rows, [data-controller~='stat-multi-add'], #add-row-btn",
        load: async (stimulus) => {
            const module =
                await import("../route_modules/admin_stats_entry.js");
            module.registerAdminStatsEntryControllers(stimulus);
        },
    },
    {
        id: "admin-images",
        matches: (pathname) => pathname.startsWith("/admin/images"),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='admin-index-table'], [data-controller~='admin-image-bulk-upload'], [data-controller~='admin-image-crop-thumb'], [data-controller~='admin-image-manipulate'], [data-controller~='hero-crop'], [data-controller~='image-upload']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_images.js");
            module.registerAdminImagesControllers(stimulus);
        },
    },
    {
        id: "admin-people",
        matches: (pathname) => pathname.startsWith("/admin/persons"),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='persons-index'], [data-controller~='person-form'], [data-controller~='place-search'], [data-controller~='back-navigation']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_people.js");
            module.registerAdminPeopleControllers(stimulus);
        },
    },
    {
        id: "admin-rosters",
        matches: (pathname) =>
            pathname.startsWith("/admin/team-season-rosters"),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='roster-edit-person'], [data-controller~='roster-multi-add'], [data-controller~='place-search']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_rosters.js");
            module.registerAdminRostersControllers(stimulus);
        },
    },
    {
        id: "admin-taxonomy",
        matches: (pathname) =>
            matchesAnyPrefix(pathname, [
                "/admin/game-types",
                "/admin/opponents",
                "/admin/places",
                "/admin/sites",
                "/admin/sports",
                "/admin/seasons",
                "/admin/teams",
                "/admin/team-seasons",
                "/admin/sport-stats",
            ]),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='admin-index-table'], [data-controller~='admin-bulk-table'], [data-controller~='sports-form'], [data-controller~='sports-configs-form'], [data-controller~='season-form'], [data-controller~='team-season-form'], [data-controller~='team-season-image'], [data-controller~='field-mapping']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_taxonomy.js");
            module.registerAdminTaxonomyControllers(stimulus);
        },
    },
    {
        id: "admin-content",
        matches: (pathname) => pathname.startsWith("/admin/blog-posts"),
        mobileStrategy: "interaction",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_content.js");
            module.registerAdminContentControllers(stimulus);
        },
    },
    {
        id: "admin-users",
        matches: (pathname) => pathname.startsWith("/admin/users"),
        mobileStrategy: "visible",
        visibilityTarget:
            "[data-controller~='admin-users-index'], [data-controller~='password-toggle']",
        load: async (stimulus) => {
            const module = await import("../route_modules/admin_users.js");
            module.registerAdminUsersControllers(stimulus);
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

function getLoaderDebug() {
    if (typeof window === "undefined") {
        return null;
    }

    if (!window.__RH_LOADER_DEBUG__) {
        window.__RH_LOADER_DEBUG__ = {
            lastPlan: null,
            plans: [],
            scheduled: [],
            loadedModules: [],
        };
    }

    return window.__RH_LOADER_DEBUG__;
}

async function loadModule(moduleDefinition, stimulus) {
    if (loadedModules.has(moduleDefinition.id)) {
        return;
    }

    loadedModules.add(moduleDefinition.id);

    try {
        await moduleDefinition.load(stimulus);

        const debug = getLoaderDebug();
        if (debug && !debug.loadedModules.includes(moduleDefinition.id)) {
            debug.loadedModules.push(moduleDefinition.id);
        }
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

    // Capture the original event so we can re-dispatch a click after the
    // module has finished loading. This allows a single user interaction to
    // both trigger loading and activate the intended control (e.g. an
    // "Add Another" button) once the module registers its handlers.
    const onInteraction = (originalEvent) => {
        INTERACTION_EVENTS.forEach((eventName) => {
            document.removeEventListener(eventName, onInteraction, true);
        });
        interactionWaitModules.delete(moduleDefinition.id);

        // Snapshot relevant DOM state at the moment of interaction so we can
        // avoid re-handling the same user action after modules load. This is
        // crucial to avoid duplicate `addRow()` invocations caused by multiple
        // deferred modules finishing and each attempting to re-dispatch.
        try {
            if (originalEvent) {
                const tgt = originalEvent.target;
                const isAddBtn = !!(
                    (tgt && tgt.id === "add-row-btn") ||
                    (tgt &&
                        typeof tgt.closest === "function" &&
                        tgt.closest &&
                        tgt.closest("#add-row-btn"))
                );
                if (isAddBtn) {
                    try {
                        const container = document.querySelector("#stat-rows");
                        if (container) {
                            const rows =
                                container.querySelectorAll(".stat-row").length;
                            try {
                                originalEvent.__rh_loader_rowsAtCapture = rows;
                            } catch {
                                void 0;
                            }
                        }
                    } catch {
                        void 0;
                    }
                }
            }
        } catch {
            void 0;
        }

        // Debugging: log the interaction event and module id so test output
        // shows whether the capture ran and what target the re-dispatch will
        // use. Useful for Playwright traces.
        try {
            const evtType = originalEvent && originalEvent.type;
            const tgt = originalEvent && originalEvent.target;
            const tgtDescr = tgt
                ? tgt.id
                    ? `#${tgt.id}`
                    : tgt.className || tgt.nodeName
                : "<none>";
            console.debug(
                `legacy_loader_registry: interaction '${evtType}' captured for module ${moduleDefinition.id} on target ${tgtDescr}`,
            );
        } catch {
            void 0;
        }

        void loadModule(moduleDefinition, stimulus).then(() => {
            const debug = getLoaderDebug();
            if (debug) {
                console.debug(
                    `legacy_loader_registry: module ${moduleDefinition.id} loaded; debug.loadedModules=${JSON.stringify(debug.loadedModules)}`,
                );
            }

            try {
                // If another module already handled this interaction, bail.
                if (originalEvent && originalEvent.__rh_loader_rehandled) {
                    return;
                }

                // If we have a captured row count, and the DOM already shows
                // more rows than at capture time, assume the interaction was
                // handled earlier and skip re-invoking handlers.
                if (originalEvent) {
                    const captured = originalEvent.__rh_loader_rowsAtCapture;
                    if (typeof captured === "number") {
                        try {
                            const container =
                                document.querySelector("#stat-rows");
                            const now = container
                                ? container.querySelectorAll(".stat-row").length
                                : null;
                            if (now !== null && now > captured) {
                                console.debug(
                                    "legacy_loader_registry: skipping re-handle; rows changed since capture",
                                    { captured, now },
                                );
                                try {
                                    originalEvent.__rh_loader_rehandled = true;
                                } catch {
                                    void 0;
                                }
                                return;
                            }
                        } catch {
                            void 0;
                        }
                    }
                }

                if (originalEvent) {
                    try {
                        originalEvent.__rh_loader_rehandled = true;
                    } catch {
                        // ignore write failures
                        void 0;
                    }
                }

                if (originalEvent) {
                    // Prefer the current element at the original pointer location
                    // (handles DOM replacements) and fall back to the original
                    // event target or a best-effort element lookup by id.
                    let target = null;
                    try {
                        if (
                            typeof originalEvent.clientX === "number" &&
                            typeof originalEvent.clientY === "number"
                        ) {
                            target = document.elementFromPoint(
                                originalEvent.clientX,
                                originalEvent.clientY,
                            );
                        }
                    } catch {
                        void 0;
                    }

                    if (!target && originalEvent.target) {
                        const orig = originalEvent.target;
                        if (orig.id) {
                            target = document.getElementById(orig.id) || orig;
                        } else {
                            target = orig;
                        }
                    }

                    if (target) {
                        // If Stimulus has attached a controller for this element,
                        // prefer invoking the controller action directly to avoid
                        // duplicating behavior (both legacy and Stimulus handlers)
                        // in cases where both are present.
                        let invoked = false;
                        try {
                            const controllerEl =
                                target.closest(
                                    "[data-controller~='stat-multi-add']",
                                ) ||
                                document.querySelector(
                                    "[data-controller~='stat-multi-add']",
                                );

                            if (
                                controllerEl &&
                                stimulus &&
                                typeof stimulus.getControllerForElementAndIdentifier ===
                                    "function"
                            ) {
                                const ctrl =
                                    stimulus.getControllerForElementAndIdentifier(
                                        controllerEl,
                                        "stat-multi-add",
                                    );
                                if (ctrl && typeof ctrl.addRow === "function") {
                                    console.debug(
                                        `legacy_loader_registry: invoking Stimulus addRow on controller for ${controllerEl.id || controllerEl.className || controllerEl.nodeName}`,
                                    );
                                    // Allow a short delay for Stimulus to finish connecting
                                    window.setTimeout(() => {
                                        try {
                                            ctrl.addRow();
                                        } catch {
                                            void 0;
                                        }
                                    }, 25);
                                    invoked = true;
                                }
                            }
                        } catch {
                            void 0;
                        }

                        if (
                            !invoked &&
                            typeof target.dispatchEvent === "function"
                        ) {
                            const clickEvent = new MouseEvent("click", {
                                bubbles: true,
                                cancelable: true,
                                view: window,
                            });
                            console.debug(
                                `legacy_loader_registry: re-dispatching click to ${target.id || target.className || target.nodeName}`,
                            );
                            // Allow a short delay for Stimulus to attach controllers
                            // to the DOM before re-dispatching the activation click.
                            window.setTimeout(() => {
                                try {
                                    target.dispatchEvent(clickEvent);
                                } catch {
                                    void 0;
                                }
                            }, 50);
                        }
                    }
                }
            } catch {
                // Swallow errors; re-dispatch is best-effort in tests/dev.
                void 0;
            }
        });
    };

    INTERACTION_EVENTS.forEach((eventName) => {
        document.addEventListener(eventName, onInteraction, {
            once: true,
            capture: true,
        });
    });

    window.setTimeout(() => {
        if (!interactionWaitModules.has(moduleDefinition.id)) {
            return;
        }

        // No interaction within the timeout; proactively load the module.
        onInteraction();
    }, IDLE_TIMEOUT_MS);
}

export function resolveLegacyLoadPlan(profile = getRuntimeProfile()) {
    return LEGACY_MODULES.filter((moduleDefinition) =>
        moduleDefinition.matches(profile.pathname),
    ).map((moduleDefinition) => ({
        id: moduleDefinition.id,
        strategy: strategyForModule(moduleDefinition, profile),
    }));
}

export async function loadLegacyModulesForCurrentRoute(
    stimulus,
    profile = getRuntimeProfile(),
) {
    const debug = getLoaderDebug();
    const plan = resolveLegacyLoadPlan(profile);
    if (debug) {
        debug.lastPlan = {
            pathname: profile.pathname,
            modules: plan,
        };
        debug.plans.push(debug.lastPlan);
        if (debug.plans.length > 30) {
            debug.plans.shift();
        }
    }

    const matchingModules = LEGACY_MODULES.filter((moduleDefinition) =>
        moduleDefinition.matches(profile.pathname),
    );

    // Heuristic: if the stat multi-add markup is present on the page, we may
    // proactively load the admin-stats-entry module so either the Stimulus
    // controller or the legacy initializer attaches before user interaction.
    // However, on constrained clients (mobile viewport or low bandwidth) we
    // should respect the module's interaction-based deferral and avoid
    // eagerly importing heavy admin logic.
    try {
        if (
            typeof document !== "undefined" &&
            document.getElementById("add-row-btn")
        ) {
            const statsModule = matchingModules.find(
                (m) => m.id === "admin-stats-entry",
            );
            if (statsModule) {
                const mobileOrConstrained =
                    profile.isMobileViewport || profile.isLowBandwidth;

                if (!mobileOrConstrained) {
                    void loadModule(statsModule, stimulus);
                } else {
                    // Deliberately skip eager load on constrained clients to
                    // preserve interaction deferral semantics.
                }
            }
        }
    } catch {
        void 0;
    }

    for (const moduleDefinition of matchingModules) {
        const strategy = strategyForModule(moduleDefinition, profile);
        if (debug) {
            debug.scheduled.push({
                pathname: profile.pathname,
                id: moduleDefinition.id,
                strategy,
            });
            if (debug.scheduled.length > 100) {
                debug.scheduled.shift();
            }
        }

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

        // For most eager modules we can load asynchronously, but the
        // `admin-core` module is small and critical for admin routes. Under
        // heavy test-suite concurrency its dynamic import can sometimes be
        // delayed enough to flake tests that expect `admin-core` to be
        // registered quickly. Awaiting the `admin-core` load here ensures
        // predictable availability without changing the behavior for other
        // modules.
        if (moduleDefinition.id === "admin-core") {
            await loadModule(moduleDefinition, stimulus);
        } else {
            void loadModule(moduleDefinition, stimulus);
        }
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

    const debug = getLoaderDebug();
    if (debug) {
        debug.lastPlan = null;
        debug.plans = [];
        debug.scheduled = [];
        debug.loadedModules = [];
    }

    hasTurboListener = false;
}
