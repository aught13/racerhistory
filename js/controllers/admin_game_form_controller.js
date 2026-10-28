import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this.isConnected = true;
        this.lifecycle = (this.lifecycle ?? 0) + 1;
        this.lookupsInitialized = false;
        this.modulesPromise ??= Promise.all([
            import("../lib/game_form_lookups.js"),
            import("../legacy/games_sport_dynamic.js"),
        ]);
        this.initializeForm();
    }

    initializeForm() {
        if (!this.isConnected || this.lookupsInitialized) {
            return;
        }

        const lifecycle = ++this.lifecycle;
        void this.modulesPromise.then(([lookupsModule, sportModule]) => {
            const sportModuleExports =
                sportModule?.default && typeof sportModule.default === "object"
                    ? sportModule.default
                    : sportModule;
            const initSport = sportModuleExports?.initGamesSportDynamic;

            if (!this.isConnected || this.lifecycle !== lifecycle) {
                if (typeof initSport === "function") {
                    const cleanup = initSport();
                    if (typeof cleanup === "function") {
                        cleanup();
                    }
                }
                return;
            }
            if (this.lookupsInitialized) {
                return;
            }

            const initLookups = lookupsModule.initGameFormLookups;
            if (typeof initLookups === "function") {
                this.lookupsCleanup = initLookups(this.element);
            }
            if (typeof initSport === "function") {
                this.sportCleanup = initSport();
            }
            this.lookupsInitialized = true;
        });
    }

    beforeCache() {
        this.lifecycle++;
        this.disposeForm();
    }

    afterCache() {
        this.initializeForm();
    }

    disconnect() {
        this.isConnected = false;
        this.lifecycle++;
        this.disposeForm();
    }

    disposeForm() {
        const lookupsCleanup = this.lookupsCleanup;
        const sportCleanup = this.sportCleanup;
        this.lookupsCleanup = null;
        this.sportCleanup = null;
        this.lookupsInitialized = false;

        try {
            if (typeof lookupsCleanup === "function") {
                lookupsCleanup();
            }
        } finally {
            if (typeof sportCleanup === "function") {
                sportCleanup();
            }
        }
    }
}
