/**
 * Jest mock for @hotwired/turbo (loaded via importmap/CDN in browser).
 *
 * Provides a minimal stub so that modules importing from @hotwired/turbo
 * can be tested under Jest without installing the full package.
 */
export default {
    start() {},
    visit() {},
    clearCache() {},
    navigator: {},
    session: {},
    connectStreamSource() {},
    disconnectStreamSource() {},
};
