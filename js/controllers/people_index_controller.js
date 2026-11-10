import { Controller } from "@hotwired/stimulus";

import {
    cleanupPeopleIndexPage,
    initPeopleIndexPage,
} from "../lib/people_index_runtime.js";

export default class extends Controller {
    connect() {
        void initPeopleIndexPage();
    }

    disconnect() {
        cleanupPeopleIndexPage();
    }
}
