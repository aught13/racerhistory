/** @jest-environment node */

import { describe, test, expect } from "@jest/globals";
import { readFileSync } from "fs";
import { join } from "path";

const readTemplate = (...parts) =>
    readFileSync(join(process.cwd(), ...parts), "utf8");

describe("Hybrid image profile markup", () => {
    test("season view markup uses profile routes for billboard and roster avatar", () => {
        const seasonView = readTemplate("templates", "Seasons", "view.php");

        expect(seasonView).toContain("profile' => 'season_billboard'");
        expect(seasonView).toContain("'profile' => 'roster_avatar'");
    });

    test("blog index templates use profile routes for featured and card images", () => {
        const indexFrame = readTemplate(
            "templates",
            "element",
            "blog",
            "index_frame.php",
        );
        const listItems = readTemplate(
            "templates",
            "element",
            "blog",
            "list_items.php",
        );

        expect(indexFrame).toContain("profile' => 'blog_featured'");
        expect(indexFrame).toContain("profile' => 'blog_index_card'");
        expect(listItems).toContain("profile' => 'blog_index_card'");
    });

    test("blog hero view uses the featured profile route", () => {
        const viewFrame = readTemplate(
            "templates",
            "element",
            "blog",
            "view_frame.php",
        );

        expect(viewFrame).toContain("profile' => 'blog_featured'");
    });
});
