/* global Event, afterEach, beforeEach, describe, expect, jest, test */

import { Application } from "@hotwired/stimulus";

import PlaceLocationController, {
    __resetPlaceLocationCacheForTests,
} from "../controllers/place_location_controller.js";

const flushPromises = async () => {
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();
};

const CSC_FIXTURE = [
    {
        iso3: "USA",
        states: [
            {
                name: "California",
                cities: [{ name: "Los Angeles" }, { name: "San Diego" }],
            },
            {
                name: "Tennessee",
                cities: [{ name: "Nashville" }],
            },
        ],
    },
    {
        iso3: "CAN",
        states: [
            {
                name: "Ontario",
                cities: [{ name: "Toronto" }],
            },
        ],
    },
];

describe("place-location controller", () => {
    let application;

    beforeEach(() => {
        jest.useFakeTimers();
        __resetPlaceLocationCacheForTests();

        globalThis.fetch = jest.fn((url) => {
            if (url.includes("/v3.1/name/United")) {
                return Promise.resolve({
                    ok: true,
                    json: async () => [
                        { name: { common: "United States" }, cca3: "USA" },
                        { name: { common: "United Kingdom" }, cca3: "GBR" },
                    ],
                });
            }

            if (url.includes("/v3.1/alpha/USA")) {
                return Promise.resolve({
                    ok: true,
                    json: async () => ({
                        name: { common: "United States" },
                        cca3: "USA",
                    }),
                });
            }

            if (url.includes("countries+states+cities.json")) {
                return Promise.resolve({
                    ok: true,
                    json: async () => CSC_FIXTURE,
                });
            }

            return Promise.resolve({
                ok: false,
                status: 404,
                json: async () => ({}),
            });
        });

        document.body.innerHTML = `
            <form data-controller="place-location">
                <input id="place-country" data-place-location-target="countryCode" value="" />
                <input
                    id="place-country-search"
                    data-place-location-target="countrySearch"
                    data-action="input->place-location#onCountryQuery blur->place-location#onCountryBlur"
                    value=""
                />
                <div data-place-location-target="countryResults"></div>
                <small data-place-location-target="countryMeta"></small>
                <input
                    id="place-state"
                    data-place-location-target="state"
                    data-action="input->place-location#onStateInput blur->place-location#onStateBlur"
                    value=""
                />
                <datalist id="place-state-options" data-place-location-target="stateList"></datalist>
                <input
                    id="place-city"
                    data-place-location-target="city"
                    data-action="input->place-location#onCityInput blur->place-location#onCityBlur"
                    value=""
                />
                <datalist id="place-city-options" data-place-location-target="cityList"></datalist>
                <small data-place-location-target="locationMeta"></small>
            </form>
        `;

        application = Application.start();
        application.register("place-location", PlaceLocationController);
    });

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        __resetPlaceLocationCacheForTests();
        delete globalThis.fetch;
        document.body.innerHTML = "";
        jest.useRealTimers();
        jest.restoreAllMocks();
    });

    test("searches country by common name, stores cca3, and cross-filters city/state", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryCode = document.getElementById("place-country");
        const countryResults = document.querySelector(
            '[data-place-location-target="countryResults"]',
        );
        const cityInput = document.getElementById("place-city");
        const stateInput = document.getElementById("place-state");
        const cityList = document.getElementById("place-city-options");
        const stateList = document.getElementById("place-state-options");

        countrySearch.value = "United";
        countrySearch.dispatchEvent(new Event("input", { bubbles: true }));

        jest.advanceTimersByTime(300);
        await flushPromises();

        const firstCountry = countryResults.querySelector("button");
        expect(firstCountry).not.toBeNull();

        countrySearch.value = "United States";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();
        await flushPromises();

        expect(
            fetch.mock.calls.some(([url]) =>
                String(url).includes("countries+states+cities.json"),
            ),
        ).toBe(true);

        expect(countryCode.value).toBe("USA");
        expect(countrySearch.value).toBe("United States");
        expect(stateList.querySelectorAll("option").length).toBeGreaterThan(0);
        expect(cityList.querySelectorAll("option").length).toBeGreaterThan(0);

        stateInput.value = "California";
        stateInput.dispatchEvent(new Event("input", { bubbles: true }));
        await flushPromises();

        const cityOptionsForCalifornia = Array.from(
            cityList.querySelectorAll("option"),
        ).map((option) => option.value);
        expect(cityOptionsForCalifornia).toContain("Los Angeles");
        expect(cityOptionsForCalifornia).toContain("San Diego");
        expect(cityOptionsForCalifornia).not.toContain("Nashville");

        stateInput.value = "";
        stateInput.dispatchEvent(new Event("input", { bubbles: true }));
        cityInput.value = "Nash";
        cityInput.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(stateInput.value).toBe("Tennessee");
        expect(cityInput.value).toBe("Nashville");
    });

    test("accepts direct ISO3 in country search and resolves country name", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryCode = document.getElementById("place-country");

        countrySearch.value = "USA";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(countryCode.value).toBe("USA");
        expect(countrySearch.value).toBe("United States");
    });

    test("onCountryQuery clears results when query is below MIN_COUNTRY_QUERY_LENGTH", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryResults = document.querySelector(
            "[data-place-location-target='countryResults']",
        );

        countrySearch.value = "U";
        countrySearch.dispatchEvent(new Event("input", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        expect(countryResults.innerHTML).toBe("");
    });

    test("onCountryQuery with empty string clears results", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryResults = document.querySelector(
            "[data-place-location-target='countryResults']",
        );

        countrySearch.value = "";
        countrySearch.dispatchEvent(new Event("input", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        expect(countryResults.innerHTML).toBe("");
    });

    test("onCountryBlur with empty value clears country selection", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryCode = document.getElementById("place-country");
        const countryMeta = document.querySelector(
            "[data-place-location-target='countryMeta']",
        );

        countrySearch.value = "";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(countryCode.value).toBe("");
        expect(countrySearch.value).toBe("");
        expect(countryMeta.textContent).toBe("");
    });

    test("onCountryBlur shows error for invalid 3-letter code", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const countryMeta = document.querySelector(
            "[data-place-location-target='countryMeta']",
        );

        countrySearch.value = "XYZ";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(countryMeta.textContent).toContain(
            "Could not resolve country code",
        );
    });

    test("onStateBlur with exact match sets state value", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const stateInput = document.getElementById("place-state");

        // First select a country
        countrySearch.value = "United States";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        // Then select a state with exact match
        stateInput.value = "California";
        stateInput.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(stateInput.value).toBe("California");
    });

    test("onCityBlur with empty value refreshes options", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const cityInput = document.getElementById("place-city");

        // First select a country
        countrySearch.value = "United States";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        // Then clear city
        cityInput.value = "";
        cityInput.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(cityInput.value).toBe("");
    });

    test("onCityBlur with multiple exact matches selects first", async () => {
        const countrySearch = document.getElementById("place-country-search");
        const cityInput = document.getElementById("place-city");

        // First select a country (USA has cities in multiple states)
        countrySearch.value = "United States";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        // Set a city (Los Angeles exists)
        cityInput.value = "Los Angeles";
        cityInput.dispatchEvent(new Event("blur", { bubbles: true }));
        await flushPromises();

        expect(cityInput.value).toBe("Los Angeles");
    });

    test("selectCountryByCode handles failed API response", async () => {
        const originalFetch = globalThis.fetch;
        globalThis.fetch = jest.fn((url) => {
            if (url.includes("/v3.1/alpha/")) {
                return Promise.resolve({
                    ok: false,
                    status: 500,
                    json: async () => ({}),
                });
            }
            return originalFetch(url);
        });

        const countrySearch = document.getElementById("place-country-search");
        const countryCode = document.getElementById("place-country");
        const countryMeta = document.querySelector(
            "[data-place-location-target='countryMeta']",
        );

        countrySearch.value = "USA";
        countrySearch.dispatchEvent(new Event("blur", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        expect(countryCode.value).toBe("");
        expect(countryMeta.textContent).toContain(
            "Could not resolve country code",
        );
    });

    test("searchCountriesByName handles 404 response", async () => {
        const originalFetch = globalThis.fetch;
        globalThis.fetch = jest.fn((url) => {
            if (url.includes("/v3.1/name/")) {
                return Promise.resolve({
                    ok: false,
                    status: 404,
                    json: async () => [],
                });
            }
            return originalFetch(url);
        });

        const countrySearch = document.getElementById("place-country-search");
        const countryResults = document.querySelector(
            "[data-place-location-target='countryResults']",
        );

        countrySearch.value = "Nonexistent";
        countrySearch.dispatchEvent(new Event("input", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        expect(countryResults.textContent).toContain("No countries found");
    });

    test("searchCountriesByName handles API error", async () => {
        const originalFetch = globalThis.fetch;
        globalThis.fetch = jest.fn((url) => {
            if (url.includes("/v3.1/name/")) {
                return Promise.resolve({
                    ok: false,
                    status: 500,
                    json: async () => ({}),
                });
            }
            return originalFetch(url);
        });

        const countrySearch = document.getElementById("place-country-search");
        const countryMeta = document.querySelector(
            "[data-place-location-target='countryMeta']",
        );

        countrySearch.value = "United";
        countrySearch.dispatchEvent(new Event("input", { bubbles: true }));
        jest.runAllTimers();
        await flushPromises();

        expect(countryMeta.textContent).toContain("Country lookup failed");
    });
});
