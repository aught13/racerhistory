const CONTROLLER_MODEL_MAP = {
    "blog-posts": "BlogPosts",
    images: "Images",
    games: "Games",
    "team-seasons": "TeamSeasons",
    "team-season-rosters": "TeamSeasonRosters",
    persons: "Persons",
    opponents: "Opponents",
    places: "Places",
    sites: "Sites",
    "game-types": "GameTypes",
    teams: "Teams",
    seasons: "Seasons",
    users: "Users",
    "site-options": "SiteOptions",
    roles: "Roles",
};

const ACTION_ABILITY_MAP = {
    index: "read",
    view: "read",
    datatables: "read",
    "ajax-list": "read",
    "ajax-game-eav-meta": "read",
    "ajax-sites-by-place": "read",
    browse: "read",
    "sports-configs": "read",
    persons: "read",
    rosters: "read",

    add: "create",
    create: "create",
    upload: "create",
    "bulk-upload": "create",
    "upload-form": "create",
    "bulk-upload-form": "create",

    edit: "update",
    manage: "update",
    approve: "update",
    "toggle-approval": "update",
    "edit-sport-configs": "update",
    "add-sport-config": "update",
    "reset-sport-configs": "update",
    "write-ads-txt": "update",
    tags: "update",
    manipulate: "update",
    "crop-thumb": "update",
    "crop-hero": "update",

    delete: "delete",
    "bulk-delete": "delete",
    "delete-sport-config": "delete",
};

function getPayload() {
    const payload =
        typeof window !== "undefined" ? window.__RH_RBAC_UI__ : undefined;
    if (!payload || typeof payload !== "object") {
        return { isAdmin: false, permissions: {} };
    }

    return {
        isAdmin: payload.isAdmin === true,
        permissions:
            payload.permissions && typeof payload.permissions === "object"
                ? payload.permissions
                : {},
    };
}

function toUrl(urlLike) {
    if (typeof window === "undefined") {
        return null;
    }

    try {
        return new URL(String(urlLike), window.location.origin);
    } catch {
        return null;
    }
}

export function resolveModelAbilityFromUrl(urlLike) {
    const parsed = toUrl(urlLike);
    if (!parsed) {
        return null;
    }

    const parts = parsed.pathname.split("/").filter(Boolean);
    if (parts.length < 2 || parts[0] !== "admin") {
        return null;
    }

    const controllerSlug = parts[1] || "";
    const modelName = CONTROLLER_MODEL_MAP[controllerSlug];
    if (!modelName) {
        return null;
    }

    const rawAction = parts[2] || "index";
    const actionSlug = /^\d+$/.test(rawAction) ? "view" : rawAction;
    const ability = ACTION_ABILITY_MAP[actionSlug] || "read";

    return { modelName, ability };
}

export function canPerformAbility(payload, modelName, ability) {
    if (!payload || payload.isAdmin === true) {
        return true;
    }

    const permission = payload.permissions?.[modelName];
    if (!permission || typeof permission !== "object") {
        return false;
    }

    if (ability === "create") {
        return permission.can_create === true;
    }

    if (ability === "read") {
        return permission.can_read !== "none";
    }

    if (ability === "update") {
        return permission.can_update !== "none";
    }

    if (ability === "delete") {
        return permission.can_delete !== "none";
    }

    return true;
}

function hideNavItem(el) {
    const navItem = el.closest("li.nav-item");
    if (navItem) {
        navItem.hidden = true;
    }
}

function disableAnchor(anchor) {
    anchor.classList.add("disabled", "opacity-50");
    anchor.setAttribute("aria-disabled", "true");
    anchor.setAttribute("tabindex", "-1");
    anchor.style.pointerEvents = "none";
}

function disableForm(form) {
    form.classList.add("opacity-50");
    const controls = form.querySelectorAll(
        'button[type="submit"], input[type="submit"]',
    );
    controls.forEach((control) => {
        control.disabled = true;
        control.classList.add("disabled");
    });
}

function processAnchor(anchor, payload) {
    const target = resolveModelAbilityFromUrl(anchor.getAttribute("href"));
    if (!target) {
        return;
    }

    const allowed = canPerformAbility(
        payload,
        target.modelName,
        target.ability,
    );
    if (allowed) {
        return;
    }

    if (anchor.closest(".sidebar-menu")) {
        hideNavItem(anchor);

        return;
    }

    disableAnchor(anchor);
}

function processForm(form, payload) {
    const action = form.getAttribute("action") || "";
    if (!action || action.includes("/admin/users/login")) {
        return;
    }

    const target = resolveModelAbilityFromUrl(action);
    if (!target) {
        return;
    }

    const allowed = canPerformAbility(
        payload,
        target.modelName,
        target.ability,
    );
    if (!allowed) {
        disableForm(form);
    }
}

function processButton(button, payload) {
    const formAction = button.getAttribute("formaction") || "";
    if (!formAction || formAction.includes("/admin/users/login")) {
        return;
    }

    const target = resolveModelAbilityFromUrl(formAction);
    if (!target) {
        return;
    }

    const allowed = canPerformAbility(
        payload,
        target.modelName,
        target.ability,
    );
    if (!allowed) {
        button.disabled = true;
        button.classList.add("disabled", "opacity-50");
    }
}

export function initAdminRbacUi(root = document) {
    if (typeof document === "undefined") {
        return;
    }

    const payload = getPayload();
    const scope = root && root.querySelectorAll ? root : document;

    scope
        .querySelectorAll('a[href*="/admin/"]:not([data-rbac-processed="1"])')
        .forEach((anchor) => {
            anchor.setAttribute("data-rbac-processed", "1");
            processAnchor(anchor, payload);
        });

    scope
        .querySelectorAll(
            'form[action*="/admin/"]:not([data-rbac-processed="1"])',
        )
        .forEach((form) => {
            form.setAttribute("data-rbac-processed", "1");
            processForm(form, payload);
        });

    scope
        .querySelectorAll(
            'button[formaction*="/admin/"]:not([data-rbac-processed="1"])',
        )
        .forEach((button) => {
            button.setAttribute("data-rbac-processed", "1");
            processButton(button, payload);
        });
}
