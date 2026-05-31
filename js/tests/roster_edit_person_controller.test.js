/* global afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import RosterEditPersonController from "../controllers/roster_edit_person_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

describe("roster-edit-person controller", () => {
    let application;

    beforeEach(() => {
        jest.useFakeTimers();
        globalThis.fetch = jest.fn();

        document.body.innerHTML = `
            <div
                data-controller="roster-edit-person"
                data-roster-edit-person-search-url-value="/admin/people/search"
                data-roster-edit-person-current-id-value="5"
                data-roster-edit-person-current-label-value="Current Person"
            >
                <div id="select-wrapper">
                    <select data-roster-edit-person-target="select" class="form-select">
                        <option value="">(Select a person)</option>
                    </select>
                </div>
            </div>
        `;

        application = Application.start();
        application.register("roster-edit-person", RosterEditPersonController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        delete globalThis.fetch;
        document.body.innerHTML = "";
        jest.useRealTimers();
        jest.restoreAllMocks();
    });

    test("injects search input and ensures the current option exists", () => {
        const wrapper = document.querySelector(".dynamic-person-wrapper");
        const searchInput = document.querySelector(".roster-person-filter");
        const select = document.querySelector("select");

        expect(wrapper).not.toBeNull();
        expect(searchInput).not.toBeNull();
        expect(select.value).toBe("5");
        expect(select.querySelector('option[value="5"]').textContent).toBe(
            "Current Person",
        );
    });

    test("uses an existing wrapper when present and does not duplicate controls", () => {
        application.stop();
        application = null;

        document.body.innerHTML = `
            <div
                data-controller="roster-edit-person"
                data-roster-edit-person-search-url-value="/admin/people/search"
            >
                <div class="dynamic-person-wrapper">
                    <input class="roster-person-filter" type="text" />
                    <select data-roster-edit-person-target="select" class="form-select">
                        <option value="">(Select a person)</option>
                    </select>
                </div>
            </div>
        `;

        application = Application.start();
        application.register("roster-edit-person", RosterEditPersonController);

        expect(
            document.querySelectorAll(".dynamic-person-wrapper"),
        ).toHaveLength(1);
        expect(document.querySelectorAll(".roster-person-filter")).toHaveLength(
            1,
        );
    });

    test("debounces searches, preserves current selection, and ignores duplicate queries", async () => {
        await flushPromises();

        fetch.mockResolvedValue({
            json: async () => ({
                success: true,
                results: [
                    { value: 7, text: "Alice Adams" },
                    { value: 8, text: "Bob Brown" },
                ],
            }),
        });

        const searchInput = document.querySelector(".roster-person-filter");
        const select = document.querySelector("select");

        searchInput.value = "Al";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith("/admin/people/search?q=Al", {
            credentials: "same-origin",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        expect(select.querySelector('option[value=""]').textContent).toBe(
            "(Select a person)",
        );
        expect(select.querySelector('option[value="5"]').textContent).toBe(
            "Current Person",
        );
        expect(select.querySelector('option[value="7"]').textContent).toBe(
            "Alice Adams",
        );
        expect(select.value).toBe("5");

        searchInput.value = "Al";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(fetch).toHaveBeenCalledTimes(1);

        searchInput.value = "A";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();

        expect(fetch).toHaveBeenCalledTimes(1);
    });

    test("keeps options stable on invalid or failing responses and clears timers on disconnect", async () => {
        await flushPromises();

        fetch
            .mockResolvedValueOnce({
                json: async () => ({ success: false, results: null }),
            })
            .mockRejectedValueOnce(new Error("network"))
            .mockResolvedValue({
                json: async () => ({ success: true, results: [] }),
            });

        const searchInput = document.querySelector(".roster-person-filter");
        const select = document.querySelector("select");

        searchInput.value = "No";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();
        expect(fetch).toHaveBeenCalledTimes(1);
        expect(select.querySelector('option[value="5"]').textContent).toBe(
            "Current Person",
        );

        searchInput.value = "Er";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();
        await flushPromises();
        expect(fetch).toHaveBeenCalledTimes(2);

        searchInput.value = "Late";
        searchInput.dispatchEvent(new Event("input", { bubbles: true }));
        document
            .querySelector('[data-controller="roster-edit-person"]')
            .remove();
        await flushPromises();
        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(fetch).toHaveBeenCalledTimes(2);
    });
});
