// ESM wrapper for admin.js UMD module
// This file provides proper ESM exports by loading the UMD module via a different mechanism

// The UMD module attaches to window in browser context
// We need to simulate that for imports

// Import the actual module (which will run the IIFE)
import "../admin.js";

/* eslint-disable no-undef */
// Export what the module attached to module.exports
export const showConfirmDelete =
    typeof window !== "undefined" ? window.showConfirmDelete : undefined;
export const AdminToast =
    typeof window !== "undefined" ? window.AdminToast : undefined;

// __internals is only available via module.exports, which we need to access differently
// For now, export undefined - this needs a better solution
export const __internals =
    typeof module !== "undefined" && module.exports
        ? module.exports.__internals
        : undefined;
/* eslint-enable no-undef */

export default {
    showConfirmDelete,
    AdminToast,
    __internals,
};
