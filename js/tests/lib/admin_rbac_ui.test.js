/**
 * @jest-environment jsdom
 */

import { beforeEach, describe, expect, test } from "@jest/globals";
import {
    canPerformAbility,
    initAdminRbacUi,
    resolveModelAbilityFromUrl,
} from "../../lib/admin_rbac_ui.js";

describe("admin_rbac_ui", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        window.__RH_RBAC_UI__ = {
            isAdmin: false,
            permissions: {
                Games: {
                    can_create: false,
                    can_read: "none",
                    can_update: "none",
                    can_delete: "none",
                    custom_rules: {},
                },
                Images: {
                    can_create: false,
                    can_read: "all",
                    can_update: "own",
                    can_delete: "none",
                    custom_rules: {},
                },
                Users: {
                    can_create: false,
                    can_read: "own",
                    can_update: "own",
                    can_delete: "none",
                    custom_rules: {},
                },
            },
        };
    });

    test("resolves model and ability from admin URLs", () => {
        expect(resolveModelAbilityFromUrl("/admin/games")).toEqual({
            modelName: "Games",
            ability: "read",
        });

        expect(resolveModelAbilityFromUrl("/admin/images/edit/2")).toEqual({
            modelName: "Images",
            ability: "update",
        });

        expect(resolveModelAbilityFromUrl("/public/page")).toBeNull();
    });

    test("treats own-level read/update as allowed at UI-gating level", () => {
        const payload = window.__RH_RBAC_UI__;

        expect(canPerformAbility(payload, "Users", "read")).toBe(true);
        expect(canPerformAbility(payload, "Users", "update")).toBe(true);
        expect(canPerformAbility(payload, "Users", "create")).toBe(false);
    });

    test("hides disallowed nav links and disables disallowed action links", () => {
        document.body.innerHTML = `
            <ul class="sidebar-menu">
                <li class="nav-item"><a class="nav-link" href="/admin/games">Games</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/images">Images</a></li>
            </ul>
            <div>
                <a id="games-add" class="btn btn-primary" href="/admin/games/add">Add Game</a>
                <a id="images-edit" class="btn btn-primary" href="/admin/images/edit/2">Edit Image</a>
            </div>
            <form id="delete-form" action="/admin/games/delete/1" method="post">
                <button type="submit">Delete</button>
            </form>
        `;

        initAdminRbacUi(document);

        const navItems = document.querySelectorAll(".sidebar-menu li.nav-item");
        expect(navItems[0].hidden).toBe(true);
        expect(navItems[1].hidden).toBe(false);

        const addLink = document.getElementById("games-add");
        expect(addLink.classList.contains("disabled")).toBe(true);
        expect(addLink.getAttribute("aria-disabled")).toBe("true");

        const editLink = document.getElementById("images-edit");
        expect(editLink.classList.contains("disabled")).toBe(false);

        const deleteButton = document.querySelector("#delete-form button");
        expect(deleteButton.disabled).toBe(true);
    });
});
