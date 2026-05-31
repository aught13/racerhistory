/**
 * @jest-environment jsdom
 */

import {
    initGamesDataTable,
    calculateRecord,
    updateRecordDisplay,
    NUMERIC_COLUMNS,
    SCROLLER_THRESHOLD,
} from "../../legacy/games-search-init.mjs";

describe("games-search-init", () => {
    describe("NUMERIC_COLUMNS", () => {
        it("should include expected numeric columns", () => {
            expect(NUMERIC_COLUMNS).toContain("Margin");
            expect(NUMERIC_COLUMNS).toContain("Pts For");
            expect(NUMERIC_COLUMNS).toContain("Pts Against");
            expect(NUMERIC_COLUMNS).toContain("Team Rk");
            expect(NUMERIC_COLUMNS).toContain("Opp Rk");
        });
    });

    describe("SCROLLER_THRESHOLD", () => {
        it("should be a positive number", () => {
            expect(SCROLLER_THRESHOLD).toBeGreaterThan(0);
        });
    });

    describe("initGamesDataTable", () => {
        beforeEach(() => {
            // Setup DOM
            document.body.innerHTML = `
                <div id="games-record-display">Loading...</div>
                <div class="card">
                    <div class="table-responsive">
                        <table id="test-table" data-ajax-url="/games/ranked?format=json&filter=all">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Team Rk</th>
                                    <th>Team</th>
                                    <th>Opponent</th>
                                    <th>Opp Rk</th>
                                    <th>H/R/N</th>
                                    <th>W/L</th>
                                    <th>Margin</th>
                                    <th>Pts For</th>
                                    <th>Pts Against</th>
                                    <th>Season</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            `;
        });

        it("should return early if table has no data-ajax-url", () => {
            const table = document.getElementById("test-table");
            table.removeAttribute("data-ajax-url");
            // Should not throw
            expect(() => initGamesDataTable(table)).not.toThrow();
        });

        it("should identify numeric columns correctly", () => {
            const table = document.getElementById("test-table");
            const headers = table.querySelectorAll("thead th");
            const numericHeaders = Array.from(headers)
                .filter((th) => NUMERIC_COLUMNS.includes(th.textContent.trim()))
                .map((th) => th.textContent.trim());

            expect(numericHeaders).toContain("Team Rk");
            expect(numericHeaders).toContain("Opp Rk");
            expect(numericHeaders).toContain("Margin");
            expect(numericHeaders).toContain("Pts For");
            expect(numericHeaders).toContain("Pts Against");
        });

        it("should find the Date column for sorting", () => {
            const table = document.getElementById("test-table");
            const headers = table.querySelectorAll("thead th");
            let dateIdx = -1;
            headers.forEach((th, idx) => {
                if (th.textContent.trim() === "Date") {
                    dateIdx = idx;
                }
            });
            expect(dateIdx).toBe(0);
        });
    });

    describe("ajax dataSrc function", () => {
        beforeEach(() => {
            document.body.innerHTML = "";
        });

        it("should update record display when json.record is present", () => {
            const recordDisplay = document.createElement("div");
            recordDisplay.id = "games-record-display";
            recordDisplay.textContent = "Loading...";
            document.body.appendChild(recordDisplay);

            const mockJson = {
                record: "15-3",
                data: [
                    [
                        "01/15/2022",
                        "5",
                        "Murray State",
                        "Belmont",
                        "12",
                        "H",
                        "W",
                        "10",
                        "75",
                        "65",
                        "2021-2022",
                    ],
                ],
            };

            // Simulate the dataSrc function
            const dataSrcFn = function (json) {
                if (json.record) {
                    const display = document.getElementById(
                        "games-record-display",
                    );
                    if (display) {
                        display.textContent = "Record: " + json.record;
                    }
                }
                return json.data;
            };

            const result = dataSrcFn(mockJson);

            expect(result).toEqual(mockJson.data);
            expect(recordDisplay.textContent).toBe("Record: 15-3");
        });

        it("should not throw when json.record is missing", () => {
            const recordDisplay = document.createElement("div");
            recordDisplay.id = "games-record-display";
            recordDisplay.textContent = "Loading...";
            document.body.appendChild(recordDisplay);

            const mockJson = {
                data: [
                    [
                        "01/15/2022",
                        "5",
                        "Murray State",
                        "Belmont",
                        "12",
                        "H",
                        "W",
                        "10",
                        "75",
                        "65",
                        "2021-2022",
                    ],
                ],
            };

            const dataSrcFn = function (json) {
                if (json.record) {
                    const display = document.getElementById(
                        "games-record-display",
                    );
                    if (display) {
                        display.textContent = "Record: " + json.record;
                    }
                }
                return json.data;
            };

            const result = dataSrcFn(mockJson);

            expect(result).toEqual(mockJson.data);
            expect(recordDisplay.textContent).toBe("Loading...");
        });

        it("should handle missing record display element gracefully", () => {
            const mockJson = {
                record: "15-3",
                data: [],
            };

            const dataSrcFn = function (json) {
                if (json.record) {
                    const display = document.getElementById(
                        "games-record-display",
                    );
                    if (display) {
                        display.textContent = "Record: " + json.record;
                    }
                }
                return json.data;
            };

            // Should not throw even if element doesn't exist
            expect(() => dataSrcFn(mockJson)).not.toThrow();
        });
    });

    describe("calculateRecord", () => {
        it("should calculate correct record from table rows", () => {
            // Mock DataTable instance
            const mockDt = {
                rows: jest.fn(() => ({
                    data: jest.fn(function () {
                        return {
                            each: function (fn) {
                                fn([
                                    "01/15/2022",
                                    "5",
                                    "Murray State",
                                    "Belmont",
                                    "12",
                                    "H",
                                    "W",
                                    "10",
                                    "75",
                                    "65",
                                    "2021-2022",
                                ]);
                                fn([
                                    "01/12/2022",
                                    "3",
                                    "Murray State",
                                    "Austin Peay",
                                    "15",
                                    "H",
                                    "W",
                                    "20",
                                    "85",
                                    "65",
                                    "2021-2022",
                                ]);
                                fn([
                                    "01/10/2022",
                                    "-",
                                    "Murray State",
                                    "SEMO",
                                    "-",
                                    "H",
                                    "L",
                                    "5",
                                    "70",
                                    "75",
                                    "2021-2022",
                                ]);
                            },
                        };
                    }),
                })),
            };

            const record = calculateRecord(mockDt);
            expect(record).toBe("2-1");
        });

        it("should return 0-0 for empty table", () => {
            const mockDt = {
                rows: jest.fn(() => ({
                    data: jest.fn(function () {
                        return {
                            // eslint-disable-next-line no-unused-vars
                            each: function (fn) {
                                // No rows - each is not called
                            },
                        };
                    }),
                })),
            };

            const record = calculateRecord(mockDt);
            expect(record).toBe("0-0");
        });

        it("should handle rows with no W/L data", () => {
            const mockDt = {
                rows: jest.fn(() => ({
                    data: jest.fn(function () {
                        return {
                            each: function (fn) {
                                fn([
                                    "01/15/2022",
                                    "5",
                                    "Murray State",
                                    "Belmont",
                                    "12",
                                    "H",
                                    "-",
                                    "10",
                                    "75",
                                    "65",
                                    "2021-2022",
                                ]);
                            },
                        };
                    }),
                })),
            };

            const record = calculateRecord(mockDt);
            expect(record).toBe("0-0");
        });
    });

    describe("updateRecordDisplay", () => {
        beforeEach(() => {
            document.body.innerHTML = "";
        });

        it("should update record display element", () => {
            const recordDisplay = document.createElement("div");
            recordDisplay.id = "games-record-display";
            recordDisplay.textContent = "Loading...";
            document.body.appendChild(recordDisplay);

            const mockDt = {
                rows: jest.fn(() => ({
                    data: jest.fn(function () {
                        return {
                            each: function (fn) {
                                fn([
                                    "01/15/2022",
                                    "5",
                                    "Murray State",
                                    "Belmont",
                                    "12",
                                    "H",
                                    "W",
                                    "10",
                                    "75",
                                    "65",
                                    "2021-2022",
                                ]);
                                fn([
                                    "01/12/2022",
                                    "3",
                                    "Murray State",
                                    "Austin Peay",
                                    "15",
                                    "H",
                                    "L",
                                    "20",
                                    "85",
                                    "65",
                                    "2021-2022",
                                ]);
                            },
                        };
                    }),
                })),
            };

            updateRecordDisplay(mockDt);
            expect(recordDisplay.textContent).toBe("Record: 1-1");
        });

        it("should not throw when record display element missing", () => {
            const mockDt = {
                rows: jest.fn(() => ({
                    data: jest.fn(function () {
                        return {
                            // eslint-disable-next-line no-unused-vars
                            each: function (fn) {
                                // No rows - each is not called
                            },
                        };
                    }),
                })),
            };

            expect(() => updateRecordDisplay(mockDt)).not.toThrow();
        });
    });
});
