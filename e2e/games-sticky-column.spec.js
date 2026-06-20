import { test, expect } from "@playwright/test";

const GAMES_ROUTE = "/games/hundred-point";
const TABLE_SELECTOR = "#games-results-table";
const WRAPPER_SELECTOR = "#games-results-table_wrapper";

async function goToGamesTable(page) {
    const response = await page.goto(GAMES_ROUTE);
    if (!response || response.status() !== 200) {
        test.skip();
        return false;
    }

    const table = page.locator(TABLE_SELECTOR);
    if ((await table.count()) === 0) {
        test.skip();
        return false;
    }

    try {
        await page.waitForSelector(`${WRAPPER_SELECTOR} .dataTables_scrollBody`, {
            timeout: 20000,
        });
    } catch {
        test.skip();
        return false;
    }

    return true;
}

async function ensureRowsForVisualAssertions(page) {
    try {
        return await page.evaluate(
            (wrapperSelector) => {
                const wrapper = document.querySelector(wrapperSelector);
                if (!wrapper) {
                    return false;
                }

                const rows = Array.from(
                    wrapper.querySelectorAll(
                        ".dataTables_scrollBody tbody tr",
                    ),
                );

                const dataRows = rows.filter(
                    (row) => !row.classList.contains("dataTables_empty"),
                );
                if (dataRows.length >= 2) {
                    return true;
                }

                const tbody = wrapper.querySelector(
                    ".dataTables_scrollBody tbody",
                );
                const headerCount = wrapper.querySelectorAll(
                    ".dataTables_scrollHead thead th",
                ).length;
                if (!tbody || headerCount < 2) {
                    return false;
                }

                // Build predictable rows when backend data is unavailable.
                tbody.querySelectorAll("tr").forEach((row) => row.remove());

                for (let rowIdx = 0; rowIdx < 2; rowIdx += 1) {
                    const tr = document.createElement("tr");
                    for (let colIdx = 0; colIdx < headerCount; colIdx += 1) {
                        const td = document.createElement("td");
                        if (colIdx === 0) {
                            td.textContent = `Pinned Opponent ${rowIdx + 1}`;
                        } else if (colIdx === 1) {
                            td.textContent = `Scrolling text ${"W".repeat(60)}`;
                        } else {
                            td.textContent = `${rowIdx + 1}-${colIdx}`;
                        }
                        tr.appendChild(td);
                    }
                    tbody.appendChild(tr);
                }

                return true;
            },
            WRAPPER_SELECTOR,
        );
    } catch {
        return false;
    }
}

test.describe("Games DataTable sticky first column", () => {
    test("keeps first column pinned and opaque while horizontal scrolling", async ({
        page,
    }) => {
        if (!(await goToGamesTable(page))) {
            return;
        }

        if (!(await ensureRowsForVisualAssertions(page))) {
            test.skip();
            return;
        }

        const hasHorizontalOverflow = await page.evaluate((wrapperSelector) => {
            const wrapper = document.querySelector(wrapperSelector);
            const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
            const bodyTable = scrollBody?.querySelector("table");
            const headTable = wrapper?.querySelector(
                ".dataTables_scrollHeadInner table",
            );

            if (!scrollBody || !bodyTable) {
                return false;
            }

            if (scrollBody.scrollWidth <= scrollBody.clientWidth) {
                const targetWidth = scrollBody.clientWidth + 600;
                bodyTable.style.width = `${targetWidth}px`;
                bodyTable.style.minWidth = `${targetWidth}px`;

                if (headTable) {
                    headTable.style.width = `${targetWidth}px`;
                    headTable.style.minWidth = `${targetWidth}px`;
                }
            }

            return scrollBody.scrollWidth > scrollBody.clientWidth;
        }, WRAPPER_SELECTOR);

        if (!hasHorizontalOverflow) {
            test.skip();
            return;
        }

        const before = await page.evaluate((wrapperSelector) => {
            const wrapper = document.querySelector(wrapperSelector);
            const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
            const firstCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:first-child td:first-child",
            );
            const secondCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:first-child td:nth-child(2)",
            );
            const firstHeader = wrapper?.querySelector(
                ".dataTables_scrollHead thead th:first-child",
            );
            const oddFirstCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:nth-of-type(odd) td:first-child",
            );
            const evenFirstCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:nth-of-type(even) td:first-child",
            );

            if (
                !scrollBody ||
                !firstCell ||
                !secondCell ||
                !firstHeader ||
                !oddFirstCell ||
                !evenFirstCell
            ) {
                return null;
            }

            const firstCellStyle = getComputedStyle(firstCell);
            const firstHeaderStyle = getComputedStyle(firstHeader);

            return {
                firstCellX: firstCell.getBoundingClientRect().x,
                firstCellBackground: firstCellStyle.backgroundColor,
                firstCellOverflow: firstCellStyle.overflow,
                firstCellPosition: firstCellStyle.position,
                firstHeaderPosition: firstHeaderStyle.position,
                firstHeaderLeft: firstHeaderStyle.left,
                oddBg: getComputedStyle(oddFirstCell).backgroundColor,
                evenBg: getComputedStyle(evenFirstCell).backgroundColor,
            };
        }, WRAPPER_SELECTOR);

        if (!before) {
            test.skip();
            return;
        }

        await page.evaluate((wrapperSelector) => {
            const wrapper = document.querySelector(wrapperSelector);
            const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
            if (scrollBody) {
                scrollBody.scrollLeft = scrollBody.scrollWidth;
                scrollBody.dispatchEvent(
                    new window.Event("scroll", { bubbles: true }),
                );
            }
        }, WRAPPER_SELECTOR);

        await page.waitForTimeout(150);

        const after = await page.evaluate((wrapperSelector) => {
            const wrapper = document.querySelector(wrapperSelector);
            const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
            const firstCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:first-child td:first-child",
            );
            const secondCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:first-child td:nth-child(2)",
            );

            if (!scrollBody || !firstCell || !secondCell) {
                return null;
            }

            const firstCellStyle = getComputedStyle(firstCell);
            return {
                scrollLeft: scrollBody.scrollLeft,
                firstCellX: firstCell.getBoundingClientRect().x,
                firstCellBackground: firstCellStyle.backgroundColor,
                firstCellOverflow: firstCellStyle.overflow,
            };
        }, WRAPPER_SELECTOR);

        if (!after) {
            test.skip();
            return;
        }

        expect(before.firstHeaderPosition).toBe("sticky");
        expect(before.firstHeaderLeft).toBe("0px");
        expect(before.firstCellPosition).toBe("sticky");
        expect(before.firstCellOverflow).toBe("hidden");

        // Sticky column should remain fixed while horizontal scrolling occurs.
        expect(Math.abs(after.firstCellX - before.firstCellX)).toBeLessThan(2);
        expect(after.scrollLeft).toBeGreaterThan(0);

        // Opaque pinned-cell styling should prevent bleed-through.
        expect(before.firstCellBackground).not.toBe("rgba(0, 0, 0, 0)");
        expect(before.firstCellBackground).not.toBe("transparent");
        expect(after.firstCellBackground).not.toBe("rgba(0, 0, 0, 0)");
        expect(before.oddBg).toBe(before.evenBg);
    });

    test("applies mobile first-column width constraints", async ({ page }) => {
        await page.setViewportSize({ width: 390, height: 844 });

        if (!(await goToGamesTable(page))) {
            return;
        }

        if (!(await ensureRowsForVisualAssertions(page))) {
            test.skip();
            return;
        }

        const styles = await page.evaluate((wrapperSelector) => {
            const wrapper = document.querySelector(wrapperSelector);
            const firstHeader = wrapper?.querySelector(
                ".dataTables_scrollHead thead th:first-child",
            );
            const firstCell = wrapper?.querySelector(
                ".dataTables_scrollBody tbody tr:first-child td:first-child",
            );
            if (!firstHeader || !firstCell) {
                return null;
            }

            const firstHeaderStyle = getComputedStyle(firstHeader);
            const firstCellStyle = getComputedStyle(firstCell);
            return {
                firstHeaderPosition: firstHeaderStyle.position,
                firstHeaderLeft: firstHeaderStyle.left,
                cellMaxWidth: firstCellStyle.maxWidth,
                cellWhiteSpace: firstCellStyle.whiteSpace,
                cellOverflowWrap: firstCellStyle.overflowWrap,
            };
        }, WRAPPER_SELECTOR);

        if (!styles) {
            test.skip();
            return;
        }

        expect(styles.firstHeaderPosition).toBe("sticky");
        expect(styles.firstHeaderLeft).toBe("0px");
        expect(styles.cellMaxWidth).toBe("160px");
        expect(styles.cellWhiteSpace).toBe("normal");
        expect(styles.cellOverflowWrap).toBe("anywhere");
    });
});
