/* global MouseEvent, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PlaceSearchController from "../controllers/place_search_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
};

describe("place-search controller", () => {
    let application;

    beforeEach(async () => {
        jest.useFakeTimers();
        globalThis.fetch = jest.fn();

        document.body.innerHTML = `
            <div
                data-controller="place-search"
                data-place-search-search-url-value="/places/search"
                data-action="
                    click->place-search#handleClick
                    click@document->place-search#handleDocumentClick
                    admin:person-birth-place-added@document->place-search#handleBirthPlaceAdded
                    turbo:before-cache@document->place-search#beforeCache
                "
            >
                <input
                    id="place-search-input"
                    data-place-search-target="input"
                    data-action="input->place-search#search"
                />
                <div id="place-search-results" data-place-search-target="results"></div>
                <div id="place-search-selected" data-place-search-target="selected">
                    <span class="text-muted fst-italic">None selected</span>
                </div>
                <input id="place-search-hidden" data-place-search-target="hidden" />
            </div>
        `;

        application = Application.start();
        application.register("place-search", PlaceSearchController);
        await flushPromises();
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

    test("ignores short queries and clears stale results", () => {
        const results = document.getElementById("place-search-results");
        const input = document.getElementById("place-search-input");

        results.innerHTML = '<div class="stale">Old result</div>';
        input.value = "A";
        input.dispatchEvent(new Event("input", { bubbles: true }));

        expect(results.innerHTML).toBe("");
        expect(fetch).not.toHaveBeenCalled();
    });

    test("searches, renders results, selects a place, and clears it again", async () => {
        fetch.mockResolvedValue({
            json: async () => ({
                success: true,
                results: [
                    { id: 8, place_city: "Nashville", place_state: "TN" },
                ],
            }),
        });

        const input = document.getElementById("place-search-input");
        const results = document.getElementById("place-search-results");
        const hidden = document.getElementById("place-search-hidden");
        const selected = document.getElementById("place-search-selected");

        input.value = "Na";
        input.dispatchEvent(new Event("input", { bubbles: true }));

        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(fetch).toHaveBeenCalledWith("/places/search?q=Na", {
            signal: expect.anything(),
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        expect(results.innerHTML).toContain("Nashville, TN");

        results.querySelector("button").click();

        expect(hidden.value).toBe("8");
        expect(selected.innerHTML).toContain("Nashville, TN");
        expect(results.innerHTML).toBe("");
        expect(input.value).toBe("");

        selected.querySelector(".clear-birth-place").click();

        expect(hidden.value).toBe("");
        expect(selected.textContent).toContain("None selected");
    });

    test("shows empty and error states, handles scoped selection, and cleans up on disconnect", async () => {
        const input = document.getElementById("place-search-input");
        const results = document.getElementById("place-search-results");
        const hidden = document.getElementById("place-search-hidden");

        fetch.mockResolvedValueOnce({
            json: async () => ({ success: true, results: [] }),
        });

        input.value = "No";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(results.innerHTML).toContain("No results");

        fetch.mockRejectedValueOnce(new Error("network"));
        input.value = "Er";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        await flushPromises();

        expect(results.innerHTML).toContain("Error");

        document.dispatchEvent(
            new CustomEvent("admin:person-birth-place-added", {
                detail: {
                    place: { id: 13, place_city: "Paris", place_state: "KY" },
                },
            }),
        );

        expect(hidden.value).toBe("13");
        expect(
            document.getElementById("place-search-selected").innerHTML,
        ).toContain("Paris, KY");

        results.innerHTML = '<button type="button">Visible</button>';
        document.body.dispatchEvent(new MouseEvent("click", { bubbles: true }));
        expect(results.innerHTML).toBe("");

        const root = document.querySelector('[data-controller="place-search"]');
        root.remove();
        await flushPromises();

        hidden.value = "";
        document.dispatchEvent(
            new CustomEvent("admin:person-birth-place-added", {
                detail: {
                    place: { id: 14, place_city: "Not selected" },
                },
            }),
        );
        expect(hidden.value).toBe("");
    });

    test("aborts stale requests and clears only ephemeral state before Turbo caching", async () => {
        const input = document.getElementById("place-search-input");
        const results = document.getElementById("place-search-results");
        const hidden = document.getElementById("place-search-hidden");
        const selected = document.getElementById("place-search-selected");

        fetch.mockImplementationOnce(
            (_url, { signal }) =>
                new Promise((_resolve, reject) => {
                    signal.addEventListener(
                        "abort",
                        () =>
                            reject(
                                Object.assign(new Error("aborted"), {
                                    name: "AbortError",
                                }),
                            ),
                        { once: true },
                    );
                }),
        );

        input.value = "Ab";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        const signal = fetch.mock.calls[0][1].signal;
        results.textContent = "Old results";

        document.dispatchEvent(new Event("turbo:before-cache"));
        await flushPromises();

        expect(signal.aborted).toBe(true);
        expect(input.value).toBe("");
        expect(results.textContent).toBe("");
        expect(hidden.value).toBe("");
        expect(selected.textContent).toContain("None selected");

        input.value = "No";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        jest.advanceTimersByTime(300);
        expect(fetch).toHaveBeenCalledTimes(1);
    });

    test("routes place-added events only to matching controller actions", async () => {
        const firstHidden = document.getElementById("place-search-hidden");
        const secondRoot = document.createElement("div");
        secondRoot.dataset.controller = "place-search";
        secondRoot.dataset.placeSearchSearchUrlValue = "/places/other-search";
        secondRoot.dataset.action =
            "admin:other-birth-place-added@document->place-search#handleBirthPlaceAdded";
        secondRoot.innerHTML = `
            <input data-place-search-target="input">
            <div data-place-search-target="results"></div>
            <div data-place-search-target="selected"></div>
            <input data-place-search-target="hidden">
        `;
        document.body.append(secondRoot);
        await flushPromises();

        document.dispatchEvent(
            new CustomEvent("admin:person-birth-place-added", {
                detail: {
                    place: { id: 25, place_city: "Scoped Place" },
                },
            }),
        );

        expect(firstHidden.value).toBe("25");
        expect(
            secondRoot.querySelector('[data-place-search-target="hidden"]')
                .value,
        ).toBe("");
    });
});
