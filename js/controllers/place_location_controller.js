import { Controller } from "@hotwired/stimulus";

const DEFAULT_COUNTRY_NAME_URL = "/admin/places/countries-lookup";
const DEFAULT_COUNTRY_ALPHA_URL = "https://restcountries.com/v3.1/alpha";
const DEFAULT_CSC_URL =
    "https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/json/countries+states+cities.json";

const COUNTRY_SEARCH_DEBOUNCE_MS = 300;
const MIN_COUNTRY_QUERY_LENGTH = 2;
const DEFAULT_DATALIST_OPTIONS_LIMIT = 250;
const FILTERED_DATALIST_OPTIONS_LIMIT = 2000;

const datasetCache = new Map();

const normalize = (value) =>
    typeof value === "string" ? value.trim().toLowerCase() : "";

const dedupeStrings = (values) => {
    const seen = new Set();
    const result = [];

    for (const value of values) {
        const cleaned = typeof value === "string" ? value.trim() : "";
        if (!cleaned) {
            continue;
        }

        const key = normalize(cleaned);
        if (seen.has(key)) {
            continue;
        }

        seen.add(key);
        result.push(cleaned);
    }

    return result.sort((a, b) => a.localeCompare(b));
};

async function loadCscDataset(url) {
    if (!datasetCache.has(url)) {
        datasetCache.set(
            url,
            fetch(url, { credentials: "omit" })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            "Failed to load country/state/city data",
                        );
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (!Array.isArray(payload)) {
                        throw new Error(
                            "Unexpected country/state/city payload",
                        );
                    }

                    return payload;
                }),
        );
    }

    return datasetCache.get(url);
}

function createCountryResultButton(country) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "list-group-item list-group-item-action py-1 small";
    button.dataset.countryCode = String(country.cca3 || "").toUpperCase();
    button.dataset.countryName = String(country?.name?.common || "");
    button.textContent = `${country?.name?.common || "Unknown"} (${button.dataset.countryCode})`;

    return button;
}

export function __resetPlaceLocationCacheForTests() {
    datasetCache.clear();
}

export default class extends Controller {
    static targets = [
        "countryCode",
        "countrySearch",
        "countrySearchBtn",
        "countryResults",
        "countryMeta",
        "state",
        "stateList",
        "city",
        "cityList",
        "locationMeta",
    ];

    static values = {
        countryNameUrl: {
            type: String,
            default: DEFAULT_COUNTRY_NAME_URL,
        },
        countryAlphaUrl: {
            type: String,
            default: DEFAULT_COUNTRY_ALPHA_URL,
        },
        cscUrl: {
            type: String,
            default: DEFAULT_CSC_URL,
        },
    };

    connect() {
        this.stateRecords = [];
        this.cityRecords = [];
        this.countryResultsByName = new Map();
        this.countryResultsByCode = new Map();

        this.boundHandleDocumentClick = this.handleDocumentClick.bind(this);
        document.addEventListener("click", this.boundHandleDocumentClick);

        this.hydrateFromCurrentValues();
    }

    disconnect() {
        if (this.countryQueryDebounceTimer) {
            clearTimeout(this.countryQueryDebounceTimer);
        }
        document.removeEventListener("click", this.boundHandleDocumentClick);
    }

    async onSearchCountries() {
        if (!this.hasCountrySearchTarget || !this.hasCountryResultsTarget) {
            return;
        }

        const query = this.countrySearchTarget.value.trim();
        if (query.length < MIN_COUNTRY_QUERY_LENGTH) {
            this.setCountryMeta("Please enter at least 2 characters.", true);
            this.clearCountryResults();
            return;
        }

        await this.searchCountriesByName(query);
    }

    onCountryQuery() {
        if (!this.hasCountrySearchTarget) {
            return;
        }

        const query = this.countrySearchTarget.value.trim();

        // Clear any existing debounce timer
        if (this.countryQueryDebounceTimer) {
            clearTimeout(this.countryQueryDebounceTimer);
        }

        // Too short, don't bother searching
        if (query.length < MIN_COUNTRY_QUERY_LENGTH) {
            this.clearCountryResults();
            return;
        }

        // Set up debounced search
        this.countryQueryDebounceTimer = setTimeout(() => {
            this.searchCountriesByName(query);
        }, COUNTRY_SEARCH_DEBOUNCE_MS);
    }

    async onCountryBlur() {
        if (!this.hasCountrySearchTarget) {
            return;
        }

        const value = this.countrySearchTarget.value.trim();
        if (!value) {
            this.clearCountrySelection();
            return;
        }

        const exactNameMatch = this.countryResultsByName.get(normalize(value));
        if (exactNameMatch) {
            await this.applyCountrySelection(exactNameMatch);
            return;
        }

        const maybeCode = value.toUpperCase();
        if (/^[A-Z]{3}$/.test(maybeCode)) {
            await this.selectCountryByCode(maybeCode);
            return;
        }

        this.setCountryMeta("Select a country from the suggestions.", true);
    }

    onStateInput() {
        this.refreshFilteredOptions();
    }

    onStateBlur() {
        if (!this.hasStateTarget) {
            return;
        }

        const inputValue = this.stateTarget.value.trim();
        if (!inputValue) {
            this.refreshFilteredOptions();
            return;
        }

        const exact = this.stateRecords.find(
            (state) => normalize(state.name) === normalize(inputValue),
        );
        if (exact) {
            this.stateTarget.value = exact.name;
        } else {
            const partialMatches = this.stateRecords.filter((state) =>
                normalize(state.name).includes(normalize(inputValue)),
            );
            if (partialMatches.length === 1) {
                this.stateTarget.value = partialMatches[0].name;
            }
        }

        this.refreshFilteredOptions();
    }

    onCityInput() {
        this.refreshFilteredOptions();
    }

    onCityBlur() {
        if (!this.hasCityTarget) {
            return;
        }

        const inputValue = this.cityTarget.value.trim();
        if (!inputValue) {
            this.refreshFilteredOptions();
            return;
        }

        const exactMatches = this.cityRecords.filter(
            (city) => normalize(city.name) === normalize(inputValue),
        );

        const candidateMatches =
            exactMatches.length > 0
                ? exactMatches
                : this.cityRecords.filter((city) =>
                      normalize(city.name).includes(normalize(inputValue)),
                  );

        if (exactMatches.length === 0 && candidateMatches.length === 1) {
            this.cityTarget.value = candidateMatches[0].name;
        }

        if (candidateMatches.length > 0) {
            if (exactMatches.length > 0) {
                this.cityTarget.value = exactMatches[0].name;
            }

            const matchingStates = dedupeStrings(
                candidateMatches.map((city) => city.stateName).filter(Boolean),
            );

            if (matchingStates.length === 1 && this.hasStateTarget) {
                this.stateTarget.value = matchingStates[0];
            }
        }

        this.refreshFilteredOptions();
    }

    async hydrateFromCurrentValues() {
        if (!this.hasCountryCodeTarget || !this.hasCountrySearchTarget) {
            return;
        }

        const existingCode = this.countryCodeTarget.value.trim().toUpperCase();
        const existingSearch = this.countrySearchTarget.value.trim();

        if (existingCode && /^[A-Z]{3}$/.test(existingCode)) {
            await this.selectCountryByCode(existingCode);
            return;
        }

        if (existingSearch && /^[A-Z]{3}$/.test(existingSearch.toUpperCase())) {
            await this.selectCountryByCode(existingSearch.toUpperCase());
        }
    }

    async searchCountriesByName(query) {
        try {
            const url = new URL(this.countryNameUrlValue, window.location.origin);
            url.searchParams.set("q", query);

            const response = await fetch(url.toString(), { credentials: "include" });

            if (!response.ok) {
                if (response.status === 404) {
                    this.renderCountryResults([]);
                    return;
                }

                throw new Error("Country lookup failed");
            }

            const payload = await response.json();
            const countries = Array.isArray(payload)
                ? payload
                      .filter(
                          (country) => country?.name?.common && country?.cca3,
                      )
                      .map((country) => ({
                          name: { common: String(country.name.common) },
                          cca3: String(country.cca3).toUpperCase(),
                      }))
                : [];

            this.renderCountryResults(countries);
        } catch {
            this.setCountryMeta(
                "Country lookup failed. Please try again.",
                true,
            );
            this.clearCountryResults();
        }
    }

    renderCountryResults(countries) {
        if (!this.hasCountryResultsTarget) {
            return;
        }

        this.countryResultsByName.clear();
        this.countryResultsByCode.clear();

        if (!countries.length) {
            this.countryResultsTarget.innerHTML =
                '<div class="small text-muted py-1">No countries found</div>';
            return;
        }

        const wrapper = document.createElement("div");
        wrapper.className = "list-group list-group-flush border rounded";
        wrapper.style.maxHeight = "220px";
        wrapper.style.overflowY = "auto";

        countries.forEach((country) => {
            this.countryResultsByName.set(
                normalize(country.name.common),
                country,
            );
            this.countryResultsByCode.set(normalize(country.cca3), country);

            const button = createCountryResultButton(country);
            button.addEventListener("click", async () => {
                await this.applyCountrySelection(country);
            });
            wrapper.appendChild(button);
        });

        this.countryResultsTarget.innerHTML = "";
        this.countryResultsTarget.appendChild(wrapper);
    }

    clearCountryResults() {
        if (this.hasCountryResultsTarget) {
            this.countryResultsTarget.innerHTML = "";
        }
    }

    async selectCountryByCode(code) {
        try {
            const response = await fetch(
                `${this.countryAlphaUrlValue}/${encodeURIComponent(code)}?fields=name,cca3`,
                { credentials: "omit" },
            );

            if (!response.ok) {
                throw new Error("Country alpha lookup failed");
            }

            const payload = await response.json();
            const country = Array.isArray(payload) ? payload[0] : payload;
            if (!country?.cca3 || !country?.name?.common) {
                throw new Error("Country alpha payload missing data");
            }

            await this.applyCountrySelection({
                name: { common: String(country.name.common) },
                cca3: String(country.cca3).toUpperCase(),
            });
        } catch {
            this.clearCountrySelection();
            this.setCountryMeta("Could not resolve country code.", true);
        }
    }

    async applyCountrySelection(country) {
        if (!this.hasCountryCodeTarget || !this.hasCountrySearchTarget) {
            return;
        }

        const code = String(country.cca3 || "").toUpperCase();
        const commonName = String(country?.name?.common || "");
        if (!code || !commonName) {
            return;
        }

        this.countryCodeTarget.value = code;
        this.countrySearchTarget.value = commonName;
        this.countrySearchTarget.dataset.selectedCountryCode = code;
        this.countrySearchTarget.dataset.selectedCountryName = commonName;

        this.clearCountryResults();
        this.setCountryMeta(`Selected: ${commonName} (${code})`);

        await this.loadCountryLocations(code);
        this.refreshFilteredOptions();
    }

    clearCountrySelection() {
        if (this.hasCountryCodeTarget) {
            this.countryCodeTarget.value = "";
        }

        this.stateRecords = [];
        this.cityRecords = [];
        this.refreshStateOptions([]);
        this.refreshCityOptions([]);
        this.clearCountryResults();
        this.setLocationMeta(
            "Select a country to load subdivisions and localities.",
        );
    }

    async loadCountryLocations(iso3Code) {
        this.setLocationMeta("Loading subdivision and locality data...");

        try {
            const dataset = await loadCscDataset(this.cscUrlValue);
            const country = dataset.find(
                (entry) => normalize(entry?.iso3) === normalize(iso3Code),
            );

            if (!country) {
                this.stateRecords = [];
                this.cityRecords = [];
                this.setLocationMeta(
                    "No subdivisions/localities found for this country.",
                    true,
                );
                return;
            }

            const stateRecords = Array.isArray(country.states)
                ? country.states
                      .map((state) => {
                          const stateName = String(state?.name || "").trim();
                          if (!stateName) {
                              return null;
                          }

                          const cities = Array.isArray(state.cities)
                              ? dedupeStrings(
                                    state.cities
                                        .map((city) => city?.name)
                                        .filter(Boolean),
                                )
                              : [];

                          return {
                              name: stateName,
                              cities,
                          };
                      })
                      .filter(Boolean)
                : [];

            const cityRecords = [];
            stateRecords.forEach((state) => {
                state.cities.forEach((cityName) => {
                    cityRecords.push({
                        name: cityName,
                        stateName: state.name,
                    });
                });
            });

            this.stateRecords = stateRecords;
            this.cityRecords = cityRecords;

            this.setLocationMeta(
                `Loaded ${stateRecords.length} subdivisions and ${cityRecords.length} localities.`,
            );
        } catch {
            this.stateRecords = [];
            this.cityRecords = [];
            this.setLocationMeta(
                "Failed to load subdivision/locality data.",
                true,
            );
        }
    }

    refreshFilteredOptions() {
        const stateQuery = this.hasStateTarget
            ? normalize(this.stateTarget.value)
            : "";
        const cityQuery = this.hasCityTarget
            ? normalize(this.cityTarget.value)
            : "";
        const optionLimit =
            stateQuery || cityQuery
                ? FILTERED_DATALIST_OPTIONS_LIMIT
                : DEFAULT_DATALIST_OPTIONS_LIMIT;

        const filteredStates = this.getFilteredStates();
        const filteredCities = this.getFilteredCities();

        this.refreshStateOptions(filteredStates, optionLimit);
        this.refreshCityOptions(filteredCities, optionLimit);
    }

    getFilteredStates() {
        const stateQuery = this.hasStateTarget
            ? normalize(this.stateTarget.value)
            : "";
        const cityQuery = this.hasCityTarget
            ? normalize(this.cityTarget.value)
            : "";

        let states = this.stateRecords.map((state) => state.name);

        if (cityQuery) {
            const statesFromCityQuery = dedupeStrings(
                this.cityRecords
                    .filter((city) => normalize(city.name).includes(cityQuery))
                    .map((city) => city.stateName),
            );
            if (statesFromCityQuery.length) {
                const stateSet = new Set(
                    statesFromCityQuery.map((name) => normalize(name)),
                );
                states = states.filter((stateName) =>
                    stateSet.has(normalize(stateName)),
                );
            }
        }

        if (stateQuery) {
            states = states.filter((stateName) =>
                normalize(stateName).includes(stateQuery),
            );
        }

        return dedupeStrings(states);
    }

    getFilteredCities() {
        const cityQuery = this.hasCityTarget
            ? normalize(this.cityTarget.value)
            : "";
        const stateQuery = this.hasStateTarget
            ? normalize(this.stateTarget.value)
            : "";

        let cities = this.cityRecords;

        if (stateQuery) {
            const exactState = this.stateRecords.find(
                (state) => normalize(state.name) === stateQuery,
            );

            if (exactState) {
                cities = cities.filter(
                    (city) => normalize(city.stateName) === stateQuery,
                );
            } else {
                cities = cities.filter((city) =>
                    normalize(city.stateName).includes(stateQuery),
                );
            }
        }

        if (cityQuery) {
            cities = cities.filter((city) =>
                normalize(city.name).includes(cityQuery),
            );
        }

        return dedupeStrings(cities.map((city) => city.name));
    }

    refreshStateOptions(states, optionLimit = DEFAULT_DATALIST_OPTIONS_LIMIT) {
        if (!this.hasStateListTarget) {
            return;
        }

        const limited = states.slice(0, optionLimit);
        this.stateListTarget.innerHTML = "";
        limited.forEach((stateName) => {
            const option = document.createElement("option");
            option.value = stateName;
            this.stateListTarget.appendChild(option);
        });
    }

    refreshCityOptions(cities, optionLimit = DEFAULT_DATALIST_OPTIONS_LIMIT) {
        if (!this.hasCityListTarget) {
            return;
        }

        const limited = cities.slice(0, optionLimit);
        this.cityListTarget.innerHTML = "";
        limited.forEach((cityName) => {
            const option = document.createElement("option");
            option.value = cityName;
            this.cityListTarget.appendChild(option);
        });
    }

    setCountryMeta(message, isError = false) {
        if (!this.hasCountryMetaTarget) {
            return;
        }

        this.countryMetaTarget.textContent = message;
        this.countryMetaTarget.classList.toggle("text-danger", isError);
        this.countryMetaTarget.classList.toggle("text-muted", !isError);
    }

    setLocationMeta(message, isError = false) {
        if (!this.hasLocationMetaTarget) {
            return;
        }

        this.locationMetaTarget.textContent = message;
        this.locationMetaTarget.classList.toggle("text-danger", isError);
        this.locationMetaTarget.classList.toggle("text-muted", !isError);
    }

    handleDocumentClick(event) {
        if (!this.hasCountryResultsTarget || !this.hasCountrySearchTarget) {
            return;
        }

        if (
            this.countrySearchTarget.contains(event.target) ||
            this.countryResultsTarget.contains(event.target)
        ) {
            return;
        }

        this.clearCountryResults();
    }
}
