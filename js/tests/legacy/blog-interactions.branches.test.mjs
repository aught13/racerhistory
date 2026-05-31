import {
    setupBlogPostCollapse,
    markBlogPostExpanded,
} from "../../legacy/modules/blog-interactions.mjs";

beforeEach(() => {
    document.body.innerHTML = "";
});

test("setupBlogPostCollapse does not throw on minimal DOM", () => {
    const container = document.createElement("div");
    // minimal structure to exercise the initializer
    container.innerHTML = `
    <div class="blog-posts-list"></div>
    <div class="blog-post-view"></div>
    <button data-post-collapse>Toggle</button>
  `;
    document.body.appendChild(container);

    expect(() => setupBlogPostCollapse(container)).not.toThrow();
});

test("markBlogPostExpanded does not throw on minimal view element", () => {
    const postView = document.createElement("div");
    postView.innerHTML = `
    <div class="featured"></div>
    <div class="list"></div>
  `;
    document.body.appendChild(postView);

    expect(() => markBlogPostExpanded(postView)).not.toThrow();
});
/* blog-interactions.branches.test.mjs
 * Tests for webroot/js/modules/blog-interactions.mjs (ESM)
 */
import * as mod from "../../legacy/modules/blog-interactions.mjs";

beforeEach(() => {
    document.body.innerHTML = "";
});

test("setupBlogPostCollapse clears view frame and toggles classes", () => {
    const container = document.createElement("div");
    container.id = "blog-post-test-slug";
    const viewFrame = document.createElement("turbo-frame");
    viewFrame.setAttribute("data-view-frame", "1");
    const closeBtn = document.createElement("button");
    closeBtn.className = "blog-collapse";
    viewFrame.appendChild(closeBtn);
    container.appendChild(viewFrame);

    const featured = document.createElement("div");
    featured.className = "blog-featured";
    container.appendChild(featured);
    const listItem = document.createElement("div");
    listItem.className = "blog-list-item";
    container.appendChild(listItem);
    document.body.appendChild(container);

    const { setupBlogPostCollapse } = mod;
    setupBlogPostCollapse(document);
    closeBtn.click();
    expect(viewFrame.innerHTML).toBe("");
    expect(container.hasAttribute("data-expanded")).toBe(false);
    expect(container.classList.contains("blog-post-expanded")).toBe(false);
});

test("markBlogPostExpanded toggles container and hides featured/list", () => {
    const postView = document.createElement("div");
    postView.className = "blog-post-view";
    postView.setAttribute("data-blog-post", "slug-1");

    const container = document.createElement("div");
    container.id = "blog-post-slug-1";
    const featured = document.createElement("div");
    featured.className = "blog-featured";
    container.appendChild(featured);
    const listItem = document.createElement("div");
    listItem.className = "blog-list-item";
    container.appendChild(listItem);

    document.body.appendChild(container);
    document.body.appendChild(postView);

    const { markBlogPostExpanded } = mod;
    markBlogPostExpanded(document);

    expect(container.getAttribute("data-expanded")).toBe("true");
    expect(container.classList.contains("blog-post-expanded")).toBe(true);
    expect(featured.style.display).toBe("none");
    expect(listItem.style.display).toBe("none");
});

test("setupBlogPostCollapse emits blog:post-collapsed with the frame id", () => {
    const container = document.createElement("div");
    container.id = "blog-post-test-slug";

    const viewFrame = document.createElement("turbo-frame");
    viewFrame.setAttribute("data-view-frame", "1");
    const closeBtn = document.createElement("button");
    closeBtn.className = "blog-collapse";
    viewFrame.appendChild(closeBtn);
    container.appendChild(viewFrame);
    document.body.appendChild(container);

    let eventDetail = null;
    document.addEventListener(
        "blog:post-collapsed",
        (event) => {
            eventDetail = event.detail;
        },
        { once: true },
    );

    const { setupBlogPostCollapse } = mod;
    setupBlogPostCollapse(document);
    closeBtn.click();

    expect(eventDetail).toEqual({ frameId: "blog-post-test-slug" });
});
