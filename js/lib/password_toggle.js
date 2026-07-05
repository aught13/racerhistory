/**
 * Password visibility toggle utility.
 *
 * Extracted as an ES module so it can be unit-tested independently of templates.
 * Templates that embed the toggle script inline implement the same logic.
 *
 * @module password_toggle
 */

/**
 * Wire up a single password-visibility toggle button.
 *
 * @param {string} btnId   ID of the toggle <button>.
 * @param {string} inputId ID of the <input type="password">.
 * @returns {void}
 */
export function initPasswordToggle(btnId, inputId) {
    const btn = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    if (!btn || !input) {
        return;
    }
    // Replace the node to remove any previously attached listener (Turbo re-use).
    const clone = btn.cloneNode(true);
    btn.parentNode.replaceChild(clone, btn);
    clone.addEventListener("click", function () {
        input.type = input.type === "password" ? "text" : "password";
        clone.innerHTML =
            input.type === "password"
                ? '<span class="bi bi-eye"></span>'
                : '<span class="bi bi-eye-slash"></span>';
    });
}

/**
 * Wire up multiple toggle pairs in one call.
 *
 * @param {Array<[string, string]>} pairs Array of [btnId, inputId] tuples.
 * @returns {void}
 */
export function initPasswordToggles(pairs) {
    pairs.forEach(function ([btnId, inputId]) {
        initPasswordToggle(btnId, inputId);
    });
}
