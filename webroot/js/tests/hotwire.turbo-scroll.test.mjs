import { jest } from "@jest/globals";
import { initTurboScrollBehavior } from "../../../js/lib/turbo_scroll.js";

describe("hotwire/turbo_scroll", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
    });

    test("scrolls to top for blog turbo frame", () => {
        const scrollSpy = jest
            .spyOn(window, "scrollTo")
            .mockImplementation(() => {});
        initTurboScrollBehavior();

        const frame = document.createElement("turbo-frame");
        frame.id = "blog";
        document.body.appendChild(frame);

        frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));

        expect(scrollSpy).toHaveBeenCalledWith({
            top: 0,
            left: 0,
            behavior: "auto",
        });
        scrollSpy.mockRestore();
    });

    test("does not scroll for non-blog frames", () => {
        const scrollSpy = jest
            .spyOn(window, "scrollTo")
            .mockImplementation(() => {});
        initTurboScrollBehavior();

        const frame = document.createElement("turbo-frame");
        frame.id = "other";
        document.body.appendChild(frame);

        frame.dispatchEvent(new Event("turbo:frame-load", { bubbles: true }));

        expect(scrollSpy).not.toHaveBeenCalled();
        scrollSpy.mockRestore();
    });
});
