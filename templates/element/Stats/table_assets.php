<?php
declare(strict_types=1);

/**
 * Stats DataTables asset block.
 *
 * Injects DataTables CSS (core + Bootstrap 5 + SearchBuilder + Scroller)
 * into the layout's css block.
 *
 * @var \App\View\AppView $this
 */
?>
<?php $this->append('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.dataTables.min.css">
<style>
#stats-table-wrap,#games-table-wrap{cursor:grab}
#stats-table-wrap.is-dragging,#games-table-wrap.is-dragging{cursor:grabbing;user-select:none}
#stats-results-table,#games-results-table,.dataTables_scrollHeadInner table{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:0.775rem}
#stats-results-table th,#stats-results-table td,#games-results-table th,#games-results-table td,.dataTables_scrollHeadInner th{white-space:nowrap;padding:0.25rem 0.5rem;font-size:0.775rem}
.dataTables_wrapper .dataTables_info{padding:0.5rem 1rem;font-size:0.75rem}
.dataTables_wrapper .dataTables_filter{padding:0.5rem 1rem;font-size:0.8125rem}
/* SearchBuilder — positioned above the table card */
#stats-searchbuilder-slot{margin-bottom:1rem}
#stats-searchbuilder-slot .dtsb-searchBuilder{padding:0.75rem;background:var(--rh-navy);border-radius:0.375rem;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:0.8125rem}
#stats-searchbuilder-slot .dtsb-title{font-weight:600;font-size:0.8125rem;color:var(--rh-gold)}
#stats-searchbuilder-slot .dtsb-group{border:1px solid rgba(255,255,255,0.15);border-radius:0.375rem;padding:0.5rem;background:rgba(255,255,255,0.07)}
#stats-searchbuilder-slot .dtsb-criteria select,#stats-searchbuilder-slot .dtsb-criteria input{font-size:0.8125rem;background:var(--rh-surface);color:var(--rh-text);border:1px solid var(--rh-border);border-radius:0.25rem;padding:0.2rem 0.4rem}
#stats-searchbuilder-slot .dtsb-button,#stats-searchbuilder-slot button.dt-button{background:var(--rh-gold);color:var(--rh-navy);border:1px solid var(--rh-gold);border-radius:0.25rem;font-size:0.8125rem;font-weight:600;padding:0.25rem 0.625rem}
#stats-searchbuilder-slot .dtsb-button:hover,#stats-searchbuilder-slot button.dt-button:hover{background:#fff;color:var(--rh-navy);border-color:#fff}
#stats-searchbuilder-slot button.dtsb-delete,#stats-searchbuilder-slot button.dtsb-right{background:transparent;color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.2)}
#stats-searchbuilder-slot button.dtsb-delete:hover,#stats-searchbuilder-slot button.dtsb-right:hover{color:#ff6b6b;border-color:#ff6b6b}
/* SearchBuilder — dark mode overrides for inputs */
@media(prefers-color-scheme:dark){:root:not([data-theme]) #stats-searchbuilder-slot .dtsb-criteria select,:root:not([data-theme]) #stats-searchbuilder-slot .dtsb-criteria input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}}
:root[data-theme="dark"] #stats-searchbuilder-slot .dtsb-criteria select,:root[data-theme="dark"] #stats-searchbuilder-slot .dtsb-criteria input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
/* DataTables wrapper dark mode */
@media(prefers-color-scheme:dark){:root:not([data-theme]){
 .dataTables_wrapper .dataTables_filter input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
 .dataTables_wrapper .dataTables_info{color:var(--rh-muted)}
 .dataTables_wrapper .dataTables_filter label{color:var(--rh-text)}
}}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_filter input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_info{color:var(--rh-muted)}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_filter label{color:var(--rh-text)}
</style>
<?php $this->end(); ?>
