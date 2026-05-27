/* global afterEach, describe, expect, test */

import { Application } from "@hotwired/stimulus";

import TeamSeasonImageController from "../controllers/team_season_image_controller.js";

describe("team-season-image controller", () => {
    let application;

    afterEach(() => {
        if (application) {
            application.stop();
            application = null;
        }

        document.body.innerHTML = "";
    });

    test("shows image card and sets src when image url is provided", async () => {
        document.body.innerHTML = `
            <div
                id="team-season-image-card"
                style="display:none;"
                data-controller="team-season-image"
                data-team-season-image-image-url-value="/img/storage/team-season.jpg"
            >
                <img data-team-season-image-target="image" src="" alt="Team Season Image" />
            </div>
        `;

        application = Application.start();
        application.register("team-season-image", TeamSeasonImageController);
    await Promise.resolve();

        const card = document.getElementById("team-season-image-card");
        const image = card.querySelector("img");

        expect(card.style.display).toBe("block");
        expect(image.getAttribute("src")).toBe("/img/storage/team-season.jpg");
    });

    test("keeps image card hidden when image url is empty", () => {
        document.body.innerHTML = `
            <div
                id="team-season-image-card"
                style="display:none;"
                data-controller="team-season-image"
            >
                <img data-team-season-image-target="image" src="" alt="Team Season Image" />
            </div>
        `;

        application = Application.start();
        application.register("team-season-image", TeamSeasonImageController);

        const card = document.getElementById("team-season-image-card");
        const image = card.querySelector("img");

        expect(card.style.display).toBe("none");
        expect(image.getAttribute("src")).toBe("");
    });
});
