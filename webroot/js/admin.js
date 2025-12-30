(function () {
    /**
     * admin.js - Handles confirm delete modal logic for admin UI.
     *
     * Key features:
     * - Renders associated items in modal
     * - Sets context for delete actions
     * - Handles delegated click events for modal triggers and delete button
     * - Submits correct form with extra fields for deletion
     * - Provides toast notifications
     *
     * IMPORTANT: Do not remove or stub out event handlers for modal triggers or delete button.
     * These are critical for admin delete functionality and are covered by integration tests.
     */
    const MODAL_ID = "confirm-delete-modal";

    function findModal() {
        return document.getElementById(MODAL_ID);
    }

    function renderAssociated(modal, associated) {
        /**
         * Renders the associated items list in the modal.
         * @param {HTMLElement} modal - The modal element
         * @param {string|Array|Object} associated - Associated items to display
         */
        if (!modal) return;
        const assocList = modal.querySelector("#" + MODAL_ID + "-assoc");
        if (!assocList) return;
        assocList.innerHTML = "";
        if (!associated) return;
        let list = [];
        if (typeof associated === "string") {
            try {
                list = JSON.parse(associated);
            } catch (e) {
                console.error("Error parsing associated:", e);
                window.AdminToast &&
                    window.AdminToast(
                        "Error parsing associated items",
                        "danger",
                    );
                list = [associated];
            }
            // ...existing code...
        } else if (Array.isArray(associated)) {
            list = associated;
        } else if (associated) {
            list = [associated];
        }
        list.forEach((row) => {
            const li = document.createElement("li");
            li.textContent =
                typeof row === "string"
                    ? row
                    : row.label || row.name || JSON.stringify(row);
            assocList.appendChild(li);
        });
    }

    // Current context for modal operations (populated from triggers)
    let context = {};

    function setContext(opts) {
        /**
         * Sets the current context for modal operations.
         * @param {Object} opts - Context options (deleteUrl, associated, ids, idsName, formId, bulkAction)
         */
        context = opts || {};
        try {
            console.log("confirm-delete setContext", context);
        } catch (e) {
            console.error("Error in setContext:", e);
            window.AdminToast &&
                window.AdminToast("Error setting context", "danger");
        }
        renderAssociated(findModal(), context.associated);
    }

    // Public helper to open modal programmatically with context
    window.showConfirmDelete = function (opts) {
        /**
         * Public helper to open the confirm delete modal programmatically.
         * @param {Object} opts - Context options
         */
        setContext(opts);
        const modal = findModal();
        if (modal && typeof bootstrap !== "undefined" && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        } else if (modal) {
            modal.style.display = "block";
        } else {
            console.log("showConfirmDelete called but modal not present");
        }
    };

    function submitTempForm(action, tokensSource, extraFields) {
        /**
         * Submits a temporary form for deletion when no source form is available.
         * @param {string} action - Form action URL
         * @param {HTMLElement|null} tokensSource - Source for CSRF tokens
         * @param {Array} extraFields - Extra hidden fields to inject
         */
        const temp = document.createElement("form");
        temp.style.display = "none";
        temp.method = "post";
        temp.action = action || "#";

        // copy hidden inputs (tokens and any existing hidden fields)
        if (tokensSource) {
            tokensSource
                .querySelectorAll('input[type="hidden"]')
                .forEach((i) => {
                    const ni = document.createElement("input");
                    ni.type = "hidden";
                    ni.name = i.name;
                    ni.value = i.value || "";
                    temp.appendChild(ni);
                });
        }

        // extraFields: array of {name, value}
        if (Array.isArray(extraFields)) {
            extraFields.forEach((f) => {
                const ni = document.createElement("input");
                ni.type = "hidden";
                ni.name = f.name;
                ni.value = f.value;
                temp.appendChild(ni);
            });
        }

        document.body.appendChild(temp);
        try {
            console.log("confirm-delete submitting temp form", {
                action: temp.action,
                inputs: temp.querySelectorAll("input").length,
            });
        } catch (e) {
            console.error("Error preparing temp form:", e);
            window.AdminToast &&
                window.AdminToast("Error preparing temp form", "danger");
        }
        try {
            if (typeof temp.requestSubmit === "function") temp.requestSubmit();
            else temp.submit();
        } catch (e) {
            console.error("Error submitting temp form:", e);
            window.AdminToast &&
                window.AdminToast("Error submitting temp form", "danger");
        }
    }

    function onDomReady(fn) {
        /**
         * Runs a function when DOM is ready.
         * @param {Function} fn
         */
        try {
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", fn);
            } else {
                fn();
            }
        } catch (e) {
            console.error("Error in onDomReady:", e);
            window.AdminToast &&
                window.AdminToast("Error initializing admin UI", "danger");
        }
    }

    onDomReady(function () {
        /**
         * Main event handler wiring for confirm delete modal.
         *
         * WARNING: Do not remove the delegated click handler for the delete button.
         * This logic is required for admin delete actions and is validated by tests.
         */
        console.log("admin.js initialized, modalPresent=", !!findModal());

        // Modal show event: when Bootstrap opens modal via data-bs-toggle, relatedTarget is available
        const modal = findModal();
        if (modal) {
            modal.addEventListener("show.bs.modal", function (ev) {
                try {
                    const trigger = ev && ev.relatedTarget;
                    if (trigger) {
                        setContext({
                            deleteUrl: trigger.dataset.deleteUrl,
                            associated: trigger.dataset.associated,
                            ids: trigger.dataset.ids,
                            idsName: trigger.dataset.idsName,
                            formId: trigger.dataset.formId,
                            bulkAction: trigger.dataset.bulkAction,
                        });
                    }
                } catch (e) {
                    console.error("Error in show.bs.modal handler:", e);
                    window.AdminToast &&
                        window.AdminToast("Error opening modal", "danger");
                }
            });
        }

        // Delegated click handler: picks up triggers created dynamically (e.g., bulk temporary trigger)
        document.addEventListener("click", function (event) {
            /**
             * Delegated click handler:
             * - Opens modal when trigger is clicked
             * - Handles delete button click inside modal
             *
             * CRITICAL: The delete button logic below must be preserved for correct admin behavior.
             */
            const t = event.target.closest(
                '[data-bs-target="#' + MODAL_ID + '"][data-delete-url]',
            );
            if (t) {
                console.log("confirm-delete trigger clicked", t);
                setContext({
                    deleteUrl: t.dataset.deleteUrl,
                    associated:
                        t.dataset.deleteAssociated || t.dataset.associated,
                    ids: t.dataset.ids,
                    idsName: t.dataset.idsName,
                    formId: t.dataset.formId,
                    bulkAction: t.dataset.bulkAction,
                });
                return; // let bootstrap open modal automatically
            }

            // Delete button inside modal
            const delBtn = event.target.closest("#" + MODAL_ID + "-delete-btn");
            if (delBtn) {
                /**
                 * Handles the delete button click inside the modal.
                 * Finds the correct form, injects extra fields, and submits for deletion.
                 *
                 * DO NOT REMOVE: This is required for admin delete functionality.
                 */
                // Determine token source: prefer referenced formId, else modal hidden form
                let source = null;
                try {
                    try {
                        try {
                            source =
                                context && context.formId
                                    ? document.getElementById(context.formId)
                                    : findModal()
                                      ? findModal().querySelector(
                                            "#" + MODAL_ID + "-hidden-form",
                                        )
                                      : null;
                        } catch (e) {
                            console.error("Error finding source form:", e);
                            window.AdminToast &&
                                window.AdminToast(
                                    "Error finding source form",
                                    "danger",
                                );
                            source = null;
                        }
                    } catch (e) {
                        console.error("Error finding source form:", e);
                        window.AdminToast &&
                            window.AdminToast(
                                "Error finding source form",
                                "danger",
                            );
                        source = null;
                    }
                } catch {
                    source = null;
                }

                // Build extra fields
                const extra = (function buildExtraFields(ctx) {
                    const res = [];
                    if (!ctx || !ctx.ids || !ctx.idsName) return res;
                    let idsArr = [];
                    if (typeof ctx.ids === "string") {
                        const trimmed = ctx.ids.trim();
                        try {
                            const parsed = JSON.parse(ctx.ids);
                            if (Array.isArray(parsed)) {
                                idsArr = parsed;
                            } else if (
                                parsed !== null &&
                                parsed !== undefined
                            ) {
                                idsArr = [parsed];
                            }
                        } catch (e) {
                            console.error("Error parsing context.ids JSON:", e);
                            // Fallback: accept a single numeric id string and parse it explicitly with radix 10
                            if (/^\s*[+-]?\d+\s*$/.test(trimmed)) {
                                try {
                                    idsArr = [parseInt(trimmed, 10)];
                                } catch (pe) {
                                    console.error("parseInt error:", pe);
                                    idsArr = [];
                                }
                            } else {
                                idsArr = [];
                            }
                        }
                    } else if (Array.isArray(ctx.ids)) {
                        idsArr = ctx.ids;
                    } else if (ctx.ids) {
                        idsArr = [ctx.ids];
                    }
                    try {
                        idsArr
                            .filter(
                                (v) =>
                                    v !== null && v !== undefined && v !== "",
                            )
                            .forEach((id) =>
                                res.push({
                                    name: ctx.idsName,
                                    value: String(id).trim(),
                                }),
                            );
                    } catch (e) {
                        console.error("Error normalizing ids:", e);
                        window.AdminToast &&
                            window.AdminToast(
                                "Error normalizing ids",
                                "danger",
                            );
                    }
                    if (ctx.bulkAction)
                        res.push({
                            name: "bulk_action",
                            value: ctx.bulkAction,
                        });
                    return res;
                })(context);

                // If a source form exists, prefer injecting into and submitting that form so tests
                // and server-side FormProtection tokens align. Otherwise fallback to a temporary form.
                // Prefer provided deleteUrl; only override with existing form action if the attribute is explicitly set.
                let postAction = context.deleteUrl || "#";
                try {
                    if (
                        source &&
                        typeof source.getAttribute === "function" &&
                        source.getAttribute("action")
                    ) {
                        postAction = source.action;
                    }
                } catch (err) {
                    // If reading attributes from the source form throws, treat the source as
                    // unavailable so we fall back to submitting a temporary form. This prevents
                    // attempting to mutate or submit a potentially broken form element.
                    console.error(
                        "Error determining postAction from source form:",
                        err,
                    );
                    source = null;
                }
                console.log(
                    "confirm-delete final post action:",
                    postAction,
                    "source form id=",
                    source && source.id,
                );

                if (source) {
                    try {
                        // cleanup previous injected inputs
                        source
                            .querySelectorAll(".injected-delete")
                            .forEach((n) => n.remove());

                        // ensure form posts to the expected action (use postAction computed above)
                        source.action = postAction;

                        // add extra hidden fields to the source form
                        if (Array.isArray(extra)) {
                            extra.forEach((f) => {
                                const ni = document.createElement("input");
                                ni.type = "hidden";
                                ni.name = f.name;
                                ni.value = f.value;
                                ni.className = "injected-delete";
                                source.appendChild(ni);
                            });
                        }

                        // submit the source form
                        try {
                            if (typeof source.requestSubmit === "function")
                                source.requestSubmit();
                            else source.submit();
                        } catch (err) {
                            console.log("error submitting source form", err);
                        }
                    } catch (err) {
                        console.log("error preparing source form submit", err);
                    }
                } else {
                    submitTempForm(postAction, source, extra);
                }
            }
        });
    });

    // Toast helper
    function toast(msg, type) {
        /**
         * Shows a toast notification in the admin UI.
         * @param {string} msg - Message to display
         * @param {string} [type] - Bootstrap alert type
         */
        const n = document.createElement("div");
        n.className =
            "alert alert-" +
            (type || "info") +
            " position-fixed top-0 end-0 m-3 shadow";
        n.textContent = msg;
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 4000);
    }
    window.AdminToast = toast;

    // For Jest testing (Node/CommonJS) - export functions when `module.exports` is available.
    // Use `typeof module !== 'undefined'` check so this remains safe in browser globals.
    /* eslint-disable no-undef */
    if (
        typeof module !== "undefined" &&
        typeof module.exports !== "undefined"
    ) {
        try {
            // Export internals to allow focused unit tests to exercise branches
            module.exports = {
                showConfirmDelete:
                    typeof window !== "undefined"
                        ? window.showConfirmDelete
                        : undefined,
                AdminToast:
                    typeof window !== "undefined"
                        ? window.AdminToast
                        : undefined,
                // Internals
                __internals: {
                    findModal,
                    renderAssociated,
                    setContext,
                    submitTempForm,
                    // expose helper for unit testing
                    buildExtraFields: function (ctx) {
                        // replicate the same logic as used above
                        const res = [];
                        if (!ctx || !ctx.ids || !ctx.idsName) return res;
                        let idsArr = [];
                        if (typeof ctx.ids === "string") {
                            const trimmed = ctx.ids.trim();
                            try {
                                const parsed = JSON.parse(ctx.ids);
                                if (Array.isArray(parsed)) {
                                    idsArr = parsed;
                                } else if (
                                    parsed !== null &&
                                    parsed !== undefined
                                ) {
                                    idsArr = [parsed];
                                }
                            } catch {
                                if (/^\s*[+-]?\d+\s*$/.test(trimmed)) {
                                    try {
                                        idsArr = [parseInt(trimmed, 10)];
                                    } catch {
                                        // ignore parseInt errors
                                        idsArr = [];
                                    }
                                } else {
                                    idsArr = [];
                                }
                            }
                        } else if (Array.isArray(ctx.ids)) {
                            idsArr = ctx.ids;
                        } else if (ctx.ids) {
                            idsArr = [ctx.ids];
                        }
                        try {
                            idsArr
                                .filter(
                                    (v) =>
                                        v !== null &&
                                        v !== undefined &&
                                        v !== "",
                                )
                                .forEach((id) =>
                                    res.push({
                                        name: ctx.idsName,
                                        value: String(id).trim(),
                                    }),
                                );
                        } catch {
                            // swallow
                        }
                        if (ctx.bulkAction)
                            res.push({
                                name: "bulk_action",
                                value: ctx.bulkAction,
                            });
                        return res;
                    },
                },
            };
        } catch (e) {
            // If assignment fails, log for debugging
            console.error("Error assigning module.exports in admin.js:", e);
        }
    }
    /* eslint-enable no-undef */
})();
