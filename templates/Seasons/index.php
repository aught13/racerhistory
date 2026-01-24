<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\TeamSeason[] $teamSeasons
 * @var array<int,array<string,int|float|null>> $seasonStats
 */
$this->assign('title', 'Team Seasons');

$this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .seasons-table-card {
        background: var(--rh-surface);
        border: 1px solid var(--rh-border);
        border-radius: 10px;
        overflow: hidden;
    }
    .seasons-table-card .table {
        margin-bottom: 0;
    }
    .seasons-table-card thead th {
        background: #0f2433;
        color: #eef2f7;
        border-color: rgba(255, 255, 255, 0.08);
    }
    .seasons-table-card tbody tr {
        background: rgba(255, 255, 255, 0.02);
    }
    .seasons-table-card tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.05);
    }
    .seasons-table-card td,
    .seasons-table-card th {
        white-space: nowrap;
    }
    /* per-page table styles only; SearchBuilder CSS moved to global frontend.css */
</style>
<?php $this->end(); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/searchbuilder/1.4.2/css/searchBuilder.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<?php $this->start('script'); ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/searchbuilder/1.4.2/js/dataTables.searchBuilder.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script>
    (function() {
        let seasonsTable = null;
        let sbInstance = null;

        function initSeasonsTable() {
            if (typeof $.fn.dataTable === 'undefined') {
                return;
            }

            if (seasonsTable) {
                try { seasonsTable.destroy(); } catch (e) {}
                seasonsTable = null;
                // remove leftover DOM elements from SearchBuilder and reset instance
                try {
                    if (sbInstance) {
                        if (typeof sbInstance.destroy === 'function') {
                            try { sbInstance.destroy(); } catch (e) {}
                        } else {
                            // remove DOM container if present
                            try {
                                if (sbInstance.dom && sbInstance.dom.container) $(sbInstance.dom.container).remove();
                                else if (typeof sbInstance.container === 'function') $(sbInstance.container()).remove();
                            } catch (ee) {}
                        }
                        sbInstance = null;
                    }
                } catch (e) {}

                $('.dt-button-collection').remove();
                $('#searchBuilder').remove();
                $('#searchbuilder-panel').empty();
            }

            function trySetupSearchBuilder(dt) {
                try {
                    const controls = document.getElementById('seasons-controls');
                    const panel = document.getElementById('searchbuilder-panel');

                    if (!controls || !panel) {
                        console.debug('SearchBuilder controls or panel not found in DOM');
                        return;
                    }

                    let btn = document.getElementById('seasons-filter-btn');
                    if (!btn) {
                        btn = document.createElement('button');
                        btn.type = 'button';
                        btn.id = 'seasons-filter-btn';
                        btn.className = 'btn btn-sm btn-outline-secondary';
                        btn.innerHTML = '<span><i class="bi bi-funnel"></i> Filter</span>';
                        btn.setAttribute('aria-expanded', 'false');
                        controls.appendChild(btn);
                    }

                    // attach click handler once
                    if (!btn._sbHandlerAdded) {
                        btn.addEventListener('click', function() {
                            const open = panel.classList.toggle('d-none') ? false : true;
                            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                            panel.classList.toggle('sb-open', open);
                        });
                        btn._sbHandlerAdded = true;
                    }

                    // if SearchBuilder already created, ensure it's appended
                    if (sbInstance) {
                        try {
                            $(panel).empty();
                            let containerEl = null;
                            if (typeof sbInstance.container === 'function') containerEl = sbInstance.container();
                            else if (sbInstance.dom && sbInstance.dom.container) containerEl = sbInstance.dom.container;
                            if (containerEl) $(panel).append(containerEl);
                        } catch (e) { console.warn('Failed to append existing SearchBuilder instance', e); }
                        return;
                    }

                    // instantiate SearchBuilder against the DataTable API instance
                    const sbOptions = { depthLimit: 2, columns: [2,3,4,5,6,7,8,9,10,11,12] };
                    try {
                        // eslint-disable-next-line no-undef
                        sbInstance = new $.fn.dataTable.SearchBuilder(dt, sbOptions);

                        $(panel).empty();
                        let containerEl = null;
                        if (sbInstance && typeof sbInstance.container === 'function') containerEl = sbInstance.container();
                        else if (sbInstance && sbInstance.dom && sbInstance.dom.container) containerEl = sbInstance.dom.container;
                        if (containerEl) $(panel).append(containerEl);
                        else {
                            // fallback placeholder when SearchBuilder DOM isn't available
                            const ph = document.createElement('div');
                            ph.className = 'p-3 text-muted small';
                            ph.textContent = 'Advanced filter not available.';
                            $(panel).append(ph);
                            console.warn('SearchBuilder created but container not found', sbInstance);
                        }
                        $(panel).addClass('d-none');

                        document.addEventListener('turbo:before-visit', () => { panel.classList.add('d-none'); btn.setAttribute('aria-expanded', 'false'); });
                    } catch (e) {
                        console.warn('SearchBuilder setup failed', e);
                    }
                } catch (e) {
                    console.warn('SearchBuilder setup failed', e);
                }

                // (searchbuilder setup returns early if controls/panel missing)
            }

            // Initialize the DataTable (no pagination, horizontal scroll to avoid responsive collapse)
            seasonsTable = $('#seasons-table').DataTable({
                paging: false,
                info: false,
                autoWidth: false,
                order: [[2, 'desc']],
                responsive: false,
                scrollX: true,
                dom: 'rtip',
            });

            trySetupSearchBuilder(seasonsTable);
        }

        // Initialize on DOMContentLoaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSeasonsTable);
        } else {
            initSeasonsTable();
        }

        // Re-initialize on Turbo navigation
        document.addEventListener('turbo:load', initSeasonsTable);
    })();
    </script>
<?php $this->end(); ?>

<?php if (!empty($teamSeasons)) : ?>
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-1">Team Seasons</h1>
            </div>
            <div id="seasons-controls" class="d-flex align-items-center gap-2"></div>
        </div>

        <div id="searchbuilder-panel" class="searchbuilder-panel"></div>

        <div class="seasons-table-card shadow-sm">
            <div class="table-responsive">
                <table id="seasons-table" class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th colspan="3" class="text-center">Overall</th>
                            <th colspan="3" class="text-center">Conference</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                        <tr>
                            <th>#</th>
                            <th>Team</th>
                            <th>Season</th>
                            <th>Conf</th>
                            <th class="text-end">W</th>
                            <th class="text-end">L</th>
                            <th class="text-end">W-L%</th>
                            <th class="text-end">W</th>
                            <th class="text-end">L</th>
                            <th class="text-end">W-L%</th>
                            <th>Conf finish</th>
                            <th>Conf tourn finish</th>
                            <th>Postseason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamSeasons as $index => $teamSeason) :
                            $seasonStart = $teamSeason->season->start ?? '';
                            $seasonEnd = $teamSeason->season->end ?? '';
                            $seasonLabel = ($seasonStart !== '' && $seasonEnd !== '')
                                ? sprintf('%s-%s', $seasonStart, substr((string)$seasonEnd, -2))
                                : trim((string)$seasonStart . '-' . (string)$seasonEnd, '-');
                            $stats = $seasonStats[$teamSeason->id] ?? [];
                            $overallWins = $stats['overall_wins'] ?? null;
                            $overallLosses = $stats['overall_losses'] ?? null;
                            $overallPct = $stats['overall_pct'] ?? null;
                            $confWins = $stats['conf_wins'] ?? null;
                            $confLosses = $stats['conf_losses'] ?? null;
                            $confPct = $stats['conf_pct'] ?? null;
                        ?>
                        <tr>
                            <td class="text-muted"><?= $index + 1 ?></td>
                            <td><?= h($teamSeason->team->team_name ?? 'Team') ?></td>
                            <td data-order="<?= h($seasonStart) ?>">
                                <a href="<?= $this->Url->build(['controller' => 'Seasons', 'action' => 'view', $teamSeason->id]) ?>"
                                   class="text-decoration-none">
                                    <?= h($seasonLabel) ?>
                                </a>
                            </td>
                            <td><?= h($teamSeason->league_abbr ?: $teamSeason->league ?: '-') ?></td>
                            <td class="text-end"><?= $overallWins ?? '—' ?></td>
                            <td class="text-end"><?= $overallLosses ?? '—' ?></td>
                            <td class="text-end">
                                <?= $overallPct !== null ? number_format((float)$overallPct, 3, '.', '') : '—' ?>
                            </td>
                            <td class="text-end"><?= $confWins ?? '—' ?></td>
                            <td class="text-end"><?= $confLosses ?? '—' ?></td>
                            <td class="text-end">
                                <?= $confPct !== null ? number_format((float)$confPct, 3, '.', '') : '—' ?>
                            </td>
                            <td><?= h($teamSeason->league_finish ?: '-') ?></td>
                            <td><?= h($teamSeason->league_torunament_finish ?: '-') ?></td>
                            <td><?= h($teamSeason->last_post_game ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else : ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            No seasons available yet.
        </div>
    <?php endif; ?>
</div>
