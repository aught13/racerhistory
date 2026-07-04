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
<style>
#stats-table-wrap,#games-table-wrap{cursor:grab}
#stats-table-wrap.is-dragging,#games-table-wrap.is-dragging{cursor:grabbing;user-select:none}
#stats-results-table,#games-results-table,.dataTables_scrollHeadInner table{font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:0.775rem}
#stats-results-table th,#stats-results-table td,#games-results-table th,#games-results-table td,.dataTables_scrollHeadInner th{white-space:nowrap;padding:0.25rem 0.5rem;font-size:0.775rem}
#stats-results-table thead th,#games-results-table thead th,.dataTables_scrollHeadInner th{font-weight:700;font-style:normal;text-transform:none;letter-spacing:normal}
.dataTables_scrollHead th,.dataTables_scrollBody td{white-space:nowrap}
.dataTables_wrapper .dataTables_info{padding:0.5rem 1rem;font-size:0.75rem}
.dataTables_wrapper .dataTables_filter{padding:0.5rem 1rem;font-size:0.8125rem}
/* Freeze first column while horizontally scrolling (stats + games) */
#stats-results-table_wrapper .dataTables_scrollHead thead th:first-child,#games-results-table_wrapper .dataTables_scrollHead thead th:first-child{position:sticky;left:0;z-index:5;background:var(--rh-surface,var(--bs-body-bg,#fff));box-shadow:2px 0 0 rgba(0,0,0,0.08);background-clip:padding-box}
#stats-results-table_wrapper .dataTables_scrollBody tbody td:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody td:first-child{position:sticky;left:0;z-index:4;background:var(--rh-surface,var(--bs-body-bg,#fff));box-shadow:2px 0 0 rgba(0,0,0,0.08);background-clip:padding-box;overflow:hidden}
#stats-results-table_wrapper .dataTables_scrollBody tbody tr.table-active td:first-child,#stats-results-table_wrapper .dataTables_scrollBody tbody tr:hover td:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody tr.table-active td:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody tr:hover td:first-child{background:var(--rh-surface,var(--bs-body-bg,#fff))}
#stats-results-table_wrapper .dataTables_scrollBody tbody tr:nth-of-type(odd) td:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody tr:nth-of-type(odd) td:first-child{background:var(--rh-surface,var(--bs-body-bg,#fff))}
#games-results-table_wrapper .dataTables_scrollHead thead th:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody td:first-child{width:clamp(8.5rem,18vw,11rem);min-width:clamp(8.5rem,18vw,11rem);max-width:11rem;white-space:normal;overflow-wrap:anywhere;line-height:1.2}
#games-results-table_wrapper .dataTables_scrollBody tbody td:first-child a{display:block;max-width:100%}
.rh-game-date{display:inline-block;max-width:100%;white-space:normal;overflow-wrap:anywhere;line-height:1.2}
/* Mobile: prevent desktop-sized first column on narrow screens (stats + games) */
@media (max-width: 767.98px){
 #stats-results-table_wrapper .dataTables_scrollHead thead th:first-child,#stats-results-table_wrapper .dataTables_scrollBody tbody td:first-child,#games-results-table_wrapper .dataTables_scrollHead thead th:first-child,#games-results-table_wrapper .dataTables_scrollBody tbody td:first-child{width:clamp(6.5rem,34vw,10rem);min-width:clamp(6.5rem,34vw,10rem);max-width:10rem;white-space:normal;overflow-wrap:anywhere;line-height:1.2}
}
/* SearchBuilder — positioned above the table card */
#stats-searchbuilder-slot,#games-searchbuilder-slot{margin-bottom:1rem}
#stats-searchbuilder-slot .dtsb-searchBuilder,#games-searchbuilder-slot .dtsb-searchBuilder{padding:0.75rem;background:var(--rh-navy);border-radius:0.375rem;font-family:system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;font-size:0.8125rem}
#stats-searchbuilder-slot .dtsb-title,#games-searchbuilder-slot .dtsb-title{font-weight:600;font-size:0.8125rem;color:var(--rh-gold)}
#stats-searchbuilder-slot .dtsb-group,#games-searchbuilder-slot .dtsb-group{border:1px solid rgba(255,255,255,0.15);border-radius:0.375rem;padding:0.5rem;background:rgba(255,255,255,0.07)}
#stats-searchbuilder-slot .dtsb-criteria select,#stats-searchbuilder-slot .dtsb-criteria input,#games-searchbuilder-slot .dtsb-criteria select,#games-searchbuilder-slot .dtsb-criteria input{font-size:0.8125rem;background:var(--rh-surface);color:var(--rh-text);border:1px solid var(--rh-border);border-radius:0.25rem;padding:0.2rem 0.4rem}
#stats-searchbuilder-slot .dtsb-button,#stats-searchbuilder-slot button.dt-button,#games-searchbuilder-slot .dtsb-button,#games-searchbuilder-slot button.dt-button{background:var(--rh-gold);color:var(--rh-navy);border:1px solid var(--rh-gold);border-radius:0.25rem;font-size:0.8125rem;font-weight:600;padding:0.25rem 0.625rem}
#stats-searchbuilder-slot .dtsb-button:hover,#stats-searchbuilder-slot button.dt-button:hover,#games-searchbuilder-slot .dtsb-button:hover,#games-searchbuilder-slot button.dt-button:hover{background:#fff;color:var(--rh-navy);border-color:#fff}
#stats-searchbuilder-slot button.dtsb-delete,#stats-searchbuilder-slot button.dtsb-right,#games-searchbuilder-slot button.dtsb-delete,#games-searchbuilder-slot button.dtsb-right{background:transparent;color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.2)}
#stats-searchbuilder-slot button.dtsb-delete:hover,#stats-searchbuilder-slot button.dtsb-right:hover,#games-searchbuilder-slot button.dtsb-delete:hover,#games-searchbuilder-slot button.dtsb-right:hover{color:#ff6b6b;border-color:#ff6b6b}
#stats-searchbuilder-slot .dtsb-inputCont,#games-searchbuilder-slot .dtsb-inputCont{min-width:0;white-space:normal!important}
.dt-datetime{z-index:1080;max-width:min(100vw - 2rem,22rem)}
.dt-datetime .dt-datetime-table{width:100%}
/* SearchBuilder — dark mode overrides for inputs */
@media(prefers-color-scheme:dark){:root:not([data-theme]) #stats-searchbuilder-slot .dtsb-criteria select,:root:not([data-theme]) #stats-searchbuilder-slot .dtsb-criteria input,:root:not([data-theme]) #games-searchbuilder-slot .dtsb-criteria select,:root:not([data-theme]) #games-searchbuilder-slot .dtsb-criteria input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}}
:root[data-theme="dark"] #stats-searchbuilder-slot .dtsb-criteria select,:root[data-theme="dark"] #stats-searchbuilder-slot .dtsb-criteria input,:root[data-theme="dark"] #games-searchbuilder-slot .dtsb-criteria select,:root[data-theme="dark"] #games-searchbuilder-slot .dtsb-criteria input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
:root[data-theme="dark"] .dt-datetime,:root[data-theme="dark"] .dt-datetime-date,:root[data-theme="dark"] .dt-datetime-time{background:var(--rh-surface);color:var(--rh-text);border:1px solid var(--rh-border)}
:root[data-theme="dark"] .dt-datetime .dt-datetime-table,:root[data-theme="dark"] .dt-datetime table{background:transparent;color:var(--rh-text)}
:root[data-theme="dark"] .dt-datetime button,:root[data-theme="dark"] .dt-datetime select,:root[data-theme="dark"] .dt-datetime .dt-datetime-label span{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
:root[data-theme="dark"] .dt-datetime td.selectable button{background:var(--rh-surface);color:var(--rh-text)}
:root[data-theme="dark"] .dt-datetime td.selectable button:hover,:root[data-theme="dark"] .dt-datetime td.selectable button:focus{background:rgba(255,255,255,0.12);color:var(--rh-text)}
:root[data-theme="dark"] .dt-datetime td.dt-datetime-empty{background:transparent}
:root[data-theme="dark"] .dt-datetime .dt-datetime-iconLeft button,:root[data-theme="dark"] .dt-datetime .dt-datetime-iconRight button,:root[data-theme="dark"] .dt-datetime .dt-datetime-title,:root[data-theme="dark"] .dt-datetime .dt-datetime-buttons a{color:var(--rh-text)}
/* DataTables wrapper dark mode */
@media(prefers-color-scheme:dark){:root:not([data-theme]){
 .dataTables_wrapper .dataTables_filter input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
 .dataTables_wrapper .dataTables_info{color:var(--rh-muted)}
 .dataTables_wrapper .dataTables_filter label{color:var(--rh-text)}
 .dt-datetime,.dt-datetime-date,.dt-datetime-time{background:var(--rh-surface);color:var(--rh-text);border:1px solid var(--rh-border)}
 .dt-datetime .dt-datetime-table,.dt-datetime table{background:transparent;color:var(--rh-text)}
 .dt-datetime button,.dt-datetime select,.dt-datetime .dt-datetime-label span{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
 .dt-datetime td.selectable button{background:var(--rh-surface);color:var(--rh-text)}
 .dt-datetime td.selectable button:hover,.dt-datetime td.selectable button:focus{background:rgba(255,255,255,0.12);color:var(--rh-text)}
 .dt-datetime td.dt-datetime-empty{background:transparent}
 .dt-datetime .dt-datetime-iconLeft button,.dt-datetime .dt-datetime-iconRight button,.dt-datetime .dt-datetime-title,.dt-datetime .dt-datetime-buttons a{color:var(--rh-text)}
}}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_filter input{background:var(--rh-surface);color:var(--rh-text);border-color:var(--rh-border)}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_info{color:var(--rh-muted)}
:root[data-theme="dark"] .dataTables_wrapper .dataTables_filter label{color:var(--rh-text)}
</style>
<?php $this->end(); ?>
