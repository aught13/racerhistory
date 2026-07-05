import { Controller } from "@hotwired/stimulus";

function getLookupsInit(defaultInit) {
    if (typeof window !== "undefined") {
        const override = window.__ADMIN_GAME_FORM_LOOKUPS_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return defaultInit;
}

function getSportInit(defaultInit) {
    if (typeof window !== "undefined") {
        const override = window.__ADMIN_GAME_FORM_SPORT_INIT__;
        if (typeof override === "function") {
            return override;
        }
    }

    return defaultInit;
}

export default class extends Controller {
    connect() {
        this.isConnected = true;

        void Promise.all([
            import("../legacy/game-form-lookups.js"),
            import("../legacy/games_sport_dynamic.js"),
        ]).then(([lookupsModule, sportModule]) => {
            if (!this.isConnected) {
                return;
            }

            const sportModuleExports =
                sportModule?.default && typeof sportModule.default === "object"
                    ? sportModule.default
                    : sportModule;

            const initLookups = getLookupsInit(
                lookupsModule.initGameFormLookups,
            );
            const initSport = getSportInit(
                sportModuleExports?.initGamesSportDynamic,
            );

            if (typeof initLookups === "function") {
                initLookups();
            }
            if (typeof initSport === "function") {
                initSport();
            }
        });
    }

    disconnect() {
        this.isConnected = false;
    }
}
