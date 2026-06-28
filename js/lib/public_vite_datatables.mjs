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

    const [{ default: jQuery }, luxon] = await Promise.all([
        import("jquery"),
        import("luxon"),
    ]);

    window.$ = jQuery;
    window.jQuery = jQuery;
    window.luxon = luxon;

    const { default: DataTable } = await import("datatables.net");
    window.DataTable = DataTable;

    await import("datatables.net-bs5");
    await import("datatables.net-scroller");
    await import("datatables.net-scroller-bs5");
    await import("datatables.net-searchbuilder");
    await import("datatables.net-searchbuilder-bs5");
    await import("datatables.net-datetime");
    await import("datatables.net-buttons");
    await import("datatables.net-buttons-bs5");
    await import("datatables.net-responsive");
    await import("datatables.net-responsive-bs5");
    await import("bootstrap/dist/js/bootstrap.bundle.min.js");

    window.__RH_PUBLIC_VITE_DATATABLES_READY__ = true;
})();
