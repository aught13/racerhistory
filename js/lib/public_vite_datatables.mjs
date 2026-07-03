import "datatables.net-bs5/css/dataTables.bootstrap5.css";
import "datatables.net-buttons-bs5/css/buttons.bootstrap5.css";
import "datatables.net-searchbuilder-bs5/css/searchBuilder.bootstrap5.css";
import "datatables.net-scroller-bs5/css/scroller.bootstrap5.css";
import "datatables.net-responsive-bs5/css/responsive.bootstrap5.css";
import "datatables.net-datetime/dist/dataTables.dateTime.css";

export const publicDataTablesReady = (async () => {
    if (
        typeof window === "undefined" ||
        window.location.pathname.startsWith("/admin")
    ) {
        return;
    }

    // Load fundamental dependencies first
    const [{ default: jQuery }, luxon] = await Promise.all([
        import("jquery"),
        import("luxon"),
    ]);

    window.$ = jQuery;
    window.jQuery = jQuery;
    window.luxon = luxon;

    // Load Bootstrap and core DataTables layout engine
    await import("bootstrap/dist/js/bootstrap.bundle.min.js");
    const { default: DataTable } = await import("datatables.net-bs5");
    window.DataTable = DataTable;

    // Load Bootstrap 5 Integration Extensions Only
    // (Remove the vanilla core imports to prevent class configuration overwrites)
    await Promise.all([
        import("datatables.net-scroller-bs5"),
        import("datatables.net-searchbuilder-bs5"), // Integrates layout structures automatically
        import("datatables.net-datetime"),
        import("datatables.net-buttons-bs5"),
        import("datatables.net-responsive-bs5"),
    ]);

    window.__RH_PUBLIC_VITE_DATATABLES_READY__ = true;
})();
