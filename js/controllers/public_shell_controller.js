import { Controller } from "@hotwired/stimulus";

const MOBILE_BREAKPOINT = 992;
const ULTRAWIDE_BREAKPOINT = 1600;

export default class extends Controller {
    connect() {
        this.handleScroll = this.handleScroll.bind(this);
        this.handleResize = this.handleResize.bind(this);

        this.syncLayoutVariant();
        this.updateChromeState();

        window.addEventListener("scroll", this.handleScroll, {
            passive: true,
        });
        window.addEventListener("resize", this.handleResize);
    }

    disconnect() {
        window.removeEventListener("scroll", this.handleScroll);
        window.removeEventListener("resize", this.handleResize);
    }

    scrollToTop(event) {
        event?.preventDefault();

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    handleScroll() {
        this.updateChromeState();
    }

    handleResize() {
        this.syncLayoutVariant();
        this.updateChromeState();
    }

    syncLayoutVariant() {
        const width = window.innerWidth;
        const variant =
            width >= ULTRAWIDE_BREAKPOINT
                ? "ultrawide"
                : width < MOBILE_BREAKPOINT
                  ? "mobile"
                  : "desktop";

        this.element.dataset.layoutVariant = variant;
        this.element.classList.toggle(
            "rh-layout--mobile",
            variant === "mobile",
        );
        this.element.classList.toggle(
            "rh-layout--desktop",
            variant === "desktop",
        );
        this.element.classList.toggle(
            "rh-layout--ultrawide",
            variant === "ultrawide",
        );

        if (document.body) {
            document.body.dataset.layoutVariant = variant;
            document.body.classList.toggle(
                "rh-layout--mobile",
                variant === "mobile",
            );
            document.body.classList.toggle(
                "rh-layout--desktop",
                variant === "desktop",
            );
            document.body.classList.toggle(
                "rh-layout--ultrawide",
                variant === "ultrawide",
            );
        }
    }

    updateChromeState() {
        const head = this.element.querySelector(".rh-head");
        const navWrap = this.element.querySelector(".rh-nav-wrap");
        const scrollTopButton = this.element.querySelector(".rh-scroll-top");

        if (!head || !navWrap) {
            this.setStickyState(true, scrollTopButton);
            return;
        }

        const navHeight = navWrap.offsetHeight ?? 0;
        const headHeight = head.offsetHeight ?? 0;
        const isStuck = window.scrollY >= Math.max(0, headHeight - navHeight);

        this.setStickyState(isStuck, scrollTopButton);
    }

    setStickyState(isStuck, scrollTopButton) {
        if (document.body) {
            document.body.classList.toggle("rh-nav-stuck", isStuck);
            document.body.classList.toggle("rh-head-collapsed", isStuck);
        }

        if (!scrollTopButton) {
            return;
        }

        const show = window.scrollY > window.innerHeight * 1.25;
        scrollTopButton.classList.toggle("is-visible", show);
    }
}
