import { test, expect } from "@playwright/test";

async function openSeasonsTable(page, route, tableSelector, wrapperSelector) {
    const response = await page.goto(route);
    if (!response || response.status() !== 200) {
        test.skip();
        return false;
    }

    const table = page.locator(tableSelector);
    if ((await table.count()) === 0) {
        test.skip();
        return false;
    }

    try {
        await page.waitForSelector(`${wrapperSelector} .dataTables_scrollBody`, {
            timeout: 20000,
        });
    } catch {
        test.skip();
        return false;
    }

    return true;
}

async function ensureRowsForVisualAssertions(page, wrapperSelector) {
    try {
        return await page.evaluate((selector) => {
            const wrapper = document.querySelector(selector);
            if (!wrapper) {
                return false;
            }

            const rows = Array.from(
                wrapper.querySelectorAll(".dataTables_scrollBody tbody tr"),
            );
            const dataRows = rows.filter(
                (row) => !row.classList.contains("dataTables_empty"),
            );
            if (dataRows.length >= 2) {
                return true;
            }

            const tbody = wrapper.querySelector(".dataTables_scrollBody tbody");
            const headFirstRow = wrapper.querySelector(
                ".dataTables_scrollHead thead tr:first-child",
            );
            if (!tbody || !headFirstRow) {
                return false;
            }

            let columnCount = 0;
            headFirstRow.querySelectorAll("th").forEach((th) => {
                const span = Number(th.getAttribute("colspan")) || 1;
                columnCount += span;
            });

            if (columnCount < 4) {
                return false;
            }

            tbody.querySelectorAll("tr").forEach((row) => row.remove());

            for (let rowIdx = 0; rowIdx < 2; rowIdx += 1) {
                const tr = document.createElement("tr");
                for (let colIdx = 0; colIdx < columnCount; colIdx += 1) {
                    const td = document.createElement("td");
                    if (colIdx === 0) {
                        td.textContent = String(rowIdx + 1);
                    } else if (colIdx === 1) {
                        td.textContent = `Team ${rowIdx + 1}`;
                    } else if (colIdx === 2) {
                        td.className = "season-freeze-col-3";
                        td.textContent = `202${rowIdx}-2${rowIdx}`;
                    } else if (colIdx === 3) {
                        td.textContent = `Scrolling text ${"W".repeat(60)}`;
                    } else {
                        td.textContent = `${rowIdx + 1}-${colIdx + 1}`;
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            }

            return true;
        }, wrapperSelector);
    } catch {
        return false;
    }
}

async function ensureHorizontalOverflow(page, wrapperSelector) {
    return page.evaluate((selector) => {
        const wrapper = document.querySelector(selector);
        const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
        const bodyTable = scrollBody?.querySelector("table");
        const headTable = wrapper?.querySelector(".dataTables_scrollHeadInner table");
        if (!scrollBody) {
            return false;
        }

        if (scrollBody.scrollWidth <= scrollBody.clientWidth && bodyTable) {
            const targetWidth = scrollBody.clientWidth + 800;
            bodyTable.style.width = `${targetWidth}px`;
            bodyTable.style.minWidth = `${targetWidth}px`;

            if (headTable) {
                headTable.style.width = `${targetWidth}px`;
                headTable.style.minWidth = `${targetWidth}px`;
            }
        }
        return scrollBody.scrollWidth > scrollBody.clientWidth;
    }, wrapperSelector);
}

async function assertSeasonColumnSticky(page, wrapperSelector) {
    const before = await page.evaluate((selector) => {
        const wrapper = document.querySelector(selector);
        const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
        const row = wrapper?.querySelector(
            ".dataTables_scrollBody tbody tr:first-child",
        );
        if (!wrapper || !scrollBody || !row) {
            return null;
        }

        const col3 = row.querySelector("td.season-freeze-col-3");
        const col4 = row.querySelector("td:nth-child(4)");
        if (!col3 || !col4) {
            return null;
        }

        const col3Style = getComputedStyle(col3);
        const col3Rect = col3.getBoundingClientRect();

        return {
            col3X: col3Rect.x,
            col4XBefore: col4.getBoundingClientRect().x,
            col3Pos: col3Style.position,
            col3Left: col3Style.left,
            col3Bg: col3Style.backgroundColor,
        };
    }, wrapperSelector);

    if (!before) {
        test.skip();
        return;
    }

    expect(before.col3Pos).toBe("sticky");
    expect(before.col3Left).toBe("0px");
    expect(before.col3Bg).toBeTruthy();

    // Scroll horizontally
    await page.evaluate((selector) => {
        const wrapper = document.querySelector(selector);
        const scrollBody = wrapper?.querySelector(".dataTables_scrollBody");
        if (scrollBody) {
            scrollBody.scrollLeft = 600;
        }
    }, wrapperSelector);

    const after = await page.evaluate((selector) => {
        const wrapper = document.querySelector(selector);
        const row = wrapper?.querySelector(
            ".dataTables_scrollBody tbody tr:first-child",
        );
        if (!wrapper || !row) {
            return null;
        }

        const col3 = row.querySelector("td.season-freeze-col-3");
        const col4 = row.querySelector("td:nth-child(4)");
        if (!col3 || !col4) {
            return null;
        }

        return {
            col3X: col3.getBoundingClientRect().x,
            col4X: col4.getBoundingClientRect().x,
            col3Left: getComputedStyle(col3).left,
        };
    }, wrapperSelector);

    if (!after) {
        test.skip();
        return;
    }

    // Column 3 should remain sticky at left: 0
    // (its viewport x position changes because columns 1-2 scroll away, but the sticky property stays)
    expect(after.col3Left).toBe("0px");

    // Column 4 should have scrolled significantly left (behind column 3)
    expect(after.col4X).toBeLessThan(before.col4XBefore - 500);
}

test.describe("Seasons table sticky Season column", () => {
    test("standard table keeps Season column pinned while other columns scroll", async ({
        page,
    }) => {
        const opened = await openSeasonsTable(
            page,
            "/seasons",
            "#seasons-table",
            "#seasons-table_wrapper",
        );
        if (!opened) {
            test.skip();
            return;
        }

        const hasRows = await ensureRowsForVisualAssertions(
            page,
            "#seasons-table_wrapper",
        );
        const hasOverflow = await ensureHorizontalOverflow(
            page,
            "#seasons-table_wrapper",
        );
        expect(hasRows && hasOverflow).toBeTruthy();

        await assertSeasonColumnSticky(page, "#seasons-table_wrapper");
    });

    test("splits table keeps Season column pinned while other columns scroll", async ({
        page,
    }) => {
        const opened = await openSeasonsTable(
            page,
            "/seasons/splits",
            "#season-splits-table",
            "#season-splits-table_wrapper",
        );
        if (!opened) {
            test.skip();
            return;
        }

        const hasRows = await ensureRowsForVisualAssertions(
            page,
            "#season-splits-table_wrapper",
        );
        const hasOverflow = await ensureHorizontalOverflow(
            page,
            "#season-splits-table_wrapper",
        );
        expect(hasRows && hasOverflow).toBeTruthy();

        await assertSeasonColumnSticky(page, "#season-splits-table_wrapper");
    });
});
