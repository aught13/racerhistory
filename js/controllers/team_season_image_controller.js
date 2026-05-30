import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["image"];

    static values = {
        imageUrl: String,
    };

    connect() {
        if (!this.hasImageUrlValue || !this.imageUrlValue) {
            return;
        }

        if (this.hasImageTarget) {
            this.imageTarget.src = this.imageUrlValue;
        }

        this.element.style.display = "block";
    }
}
