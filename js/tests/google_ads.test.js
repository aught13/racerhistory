describe("google ad slot lifecycle", () => {
    beforeEach(() => {
        document.body.innerHTML = "";
        delete window.adsbygoogle;
    });

    afterEach(() => {
        document.body.innerHTML = "";
        delete window.adsbygoogle;
    });

    test("initializes each Google slot once and hides unfilled ones", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle" data-ad-status="unfilled"></ins>
                </div>
            </section>
        `;

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        const section = document.querySelector(".rh-ad-slot--google");
        const ad = section.querySelector("ins.adsbygoogle");

        expect(window.adsbygoogle).toBeDefined();
        expect(window.adsbygoogle.length).toBe(1);
        expect(section.dataset.rhAdInitialized).toBe("1");
        expect(section.classList.contains("rh-ad-slot--empty")).toBe(true);
        expect(ad.style.display).toBe("none");
    });

    test("does not re-push a slot that is already initialized", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1" data-rh-ad-initialized="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle" data-ad-status="filled"></ins>
                </div>
            </section>
        `;

        window.adsbygoogle = ["existing"];

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        expect(window.adsbygoogle).toHaveLength(1);
        expect(window.adsbygoogle[0]).toBe("existing");
    });

    test("uses existing adsbygoogle queue object without replacing it", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle"></ins>
                </div>
            </section>
        `;

        const push = jest.fn();
        window.adsbygoogle = { push };

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        expect(window.adsbygoogle.push).toBe(push);
        expect(push).toHaveBeenCalledTimes(1);
    });

    test("does not re-push a slot already rendered by Google", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle" data-adsbygoogle-status="done"></ins>
                </div>
            </section>
        `;

        const push = jest.fn();
        window.adsbygoogle = { push };

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        expect(push).not.toHaveBeenCalled();
        const section = document.querySelector(".rh-ad-slot--google");
        expect(section.dataset.rhAdInitialized).toBe("1");
    });

    test("marks slot initialized when no ins.adsbygoogle exists", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <div>No ad element yet</div>
                </div>
            </section>
        `;

        const push = jest.fn();
        window.adsbygoogle = { push };

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        const section = document.querySelector(".rh-ad-slot--google");
        expect(section.dataset.rhAdInitialized).toBe("1");
        expect(push).not.toHaveBeenCalled();
    });

    test("falls back gracefully when MutationObserver is unavailable", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle" data-ad-status="filled"></ins>
                </div>
            </section>
        `;

        const originalMutationObserver = globalThis.MutationObserver;
        delete globalThis.MutationObserver;
        const push = jest.fn();
        window.adsbygoogle = { push };

        try {
            const { initGoogleAdSlots } = await import("../lib/google_ads.js");
            expect(() => initGoogleAdSlots(document)).not.toThrow();
        } finally {
            globalThis.MutationObserver = originalMutationObserver;
        }

        expect(push).toHaveBeenCalledTimes(1);
        const section = document.querySelector(".rh-ad-slot--google");
        expect(section.dataset.rhAdInitialized).toBe("1");
    });

    test("does not treat an AdSense placement name as a GPT slot", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <div id="div-display-ad"></div>
                </div>
            </section>
        `;

        const push = jest.fn();
        window.googletag = { cmd: { push } };

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        const section = document.querySelector(".rh-ad-slot--google");
        expect(section.dataset.rhAdInitialized).toBe("1");
        expect(push).not.toHaveBeenCalled();
    });

    test("removes duplicate AdSense loader scripts", async () => {
        document.body.innerHTML = `
            <script src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123"></script>
            <script src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-123"></script>
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav">
                <div class="rh-ad-slot__inner"></div>
            </section>
        `;

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        expect(
            document.querySelectorAll(
                'script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]',
            ),
        ).toHaveLength(1);
    });

    test("installs one cleanup listener for Turbo head merges", async () => {
        delete window.__RH_ADSENSE_SCRIPT_CLEANUP__;
        const addEventListener = jest.spyOn(document, "addEventListener");

        const { installGoogleAdScriptCleanup } =
            await import("../lib/google_ads.js");
        installGoogleAdScriptCleanup();
        installGoogleAdScriptCleanup();

        expect(
            addEventListener.mock.calls.filter(
                ([eventName]) => eventName === "turbo:load",
            ),
        ).toHaveLength(1);

        addEventListener.mockRestore();
    });

    test("initializes an explicit GPT slot without treating an AdSense slot as GPT", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-google-tag-slot-id="div-display-ad">
                <div class="rh-ad-slot__inner">
                    <div id="div-display-ad"></div>
                </div>
            </section>
        `;

        const push = jest.fn();
        window.googletag = { cmd: { push } };

        const { initGoogleAdSlots } = await import("../lib/google_ads.js");
        initGoogleAdSlots(document);

        const section = document.querySelector(".rh-ad-slot--google");
        expect(section.dataset.rhAdInitialized).toBe("1");
        expect(push).toHaveBeenCalledTimes(1);
        expect(typeof push.mock.calls[0][0]).toBe("function");
    });

    test("ignores non-element inputs in state sync", async () => {
        const { syncGoogleAdSlotState } = await import("../lib/google_ads.js");
        expect(syncGoogleAdSlotState(null, null)).toBe(false);
    });

    test("initializes and tears down a single google section", async () => {
        document.body.innerHTML = `
            <section class="rh-ad-slot rh-ad-slot--google" data-ad-slot="below_nav" data-google-mode="1">
                <div class="rh-ad-slot__inner">
                    <ins class="adsbygoogle" data-ad-status="filled"></ins>
                </div>
            </section>
        `;

        const { destroyGoogleAdSlotSection, initGoogleAdSlotSection } =
            await import("../lib/google_ads.js");

        const section = document.querySelector(".rh-ad-slot--google");
        const initialized = initGoogleAdSlotSection(section);

        expect(initialized).toBe(true);
        expect(section.dataset.rhAdInitialized).toBe("1");

        destroyGoogleAdSlotSection(section);

        expect(section.getAttribute("data-rh-ad-initialized")).toBeNull();
        expect(section.classList.contains("rh-ad-slot--empty")).toBe(false);
    });
});
