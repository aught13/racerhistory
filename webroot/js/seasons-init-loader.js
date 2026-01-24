/* Loader for seasons init (ES module).
 * Imports the initializer module and boots it on DOM/turbo load events.
 */
import initSeasons from './modules/seasons-init.js';

function boot() {
    try {
        if (typeof initSeasons === 'function') {
            initSeasons();
        } else {
            console.warn('seasons-init default export is not a function');
        }
    } catch (err) {
        console.warn('seasons-init boot failed', err);
    }
}

document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('turbo:load', boot);
