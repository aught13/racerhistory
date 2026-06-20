<?php
/**
 * @var \App\View\AppView $this
 */
$this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<style>
#seasons-table_wrapper .season-freeze-col-3,
#season-splits-table_wrapper .season-freeze-col-3 {
    position: sticky;
    left: 0;
    z-index: 4;
    background: var(--rh-surface, var(--bs-body-bg, #fff));
    background-clip: padding-box;
    overflow: hidden;
    box-shadow: 2px 0 0 rgba(0, 0, 0, 0.08);
}

#seasons-table_wrapper thead .season-freeze-col-3,
#season-splits-table_wrapper thead .season-freeze-col-3 {
    z-index: 6;
}

#seasons-table_wrapper tbody tr:hover .season-freeze-col-3,
#season-splits-table_wrapper tbody tr:hover .season-freeze-col-3 {
    background: var(--rh-surface, var(--bs-body-bg, #fff));
}
</style>
<!-- SearchBuilder CSS moved to global frontend.css -->
<?php $this->end(); ?>

<?php $this->start('script'); ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<?php $this->end(); ?>
