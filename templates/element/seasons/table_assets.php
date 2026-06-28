<?php
/**
 * @var \App\View\AppView $this
 */
$this->start('css'); ?>
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

