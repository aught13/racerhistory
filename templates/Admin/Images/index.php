<?php
/**
 * @var \App\View\AppView $this
 * @var int $imageCount
 */
$this->assign('title', 'Images');
$datatableUrl = $this->Url->build(['prefix' => 'Admin', 'controller' => 'Images', 'action' => 'datatables']);
?>
<?php $this->start('css'); ?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.bootstrap5.min.css">
<?php $this->end(); ?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-1">Images</h1>
            <p class="text-muted mb-3">
                All Images: <?= (int)$imageCount ?> total.
            </p>
            <a href="<?= $this->Url->build(['action' => 'bulkUploadForm']) ?>" class="btn btn-success mb-3">
                <i class="bi bi-upload"></i> Upload Images
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="d-flex align-items-center gap-2 mb-2">
                <label for="images-search" class="form-label mb-0 text-nowrap">Search:</label>
                <input type="search" id="images-search" class="form-control form-control-sm" placeholder="Name, mime, status, id..." autocomplete="off">
            </div>

            <table
                id="images-table"
                class="table table-striped table-bordered table-hover align-middle w-100"
                data-datatables-url="<?= h($datatableUrl) ?>"
            >
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th style="width: 5rem;">Preview</th>
                        <th>Original Name</th>
                        <th>Mime</th>
                        <th>Size</th>
                        <th>Dimensions</th>
                        <th>Status</th>
                        <th style="width: 8rem;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<?php $this->start('script'); ?>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js"></script>
<script>
(function () {
    'use strict';

    var dtInstance = null;
    var searchDebounce = null;
    var thumbObserver = null;
    var thumbQueue = [];
    var thumbInFlight = 0;
    var initRetryTimer = null;
    var initRetryCount = 0;
    var THUMB_MAX_CONCURRENT = 1;
    var THUMB_OBSERVER_MARGIN = '50px 0px';

    function resetThumbLoader() {
        if (thumbObserver) {
            thumbObserver.disconnect();
            thumbObserver = null;
        }
        thumbQueue = [];
        thumbInFlight = 0;
    }

    function flushThumbQueue() {
        while (thumbInFlight < THUMB_MAX_CONCURRENT && thumbQueue.length > 0) {
            (function () {
                var img = thumbQueue.shift();
                if (!img || img.dataset.thumbLoaded === '1') {
                    return;
                }

                var thumbUrl = img.getAttribute('data-thumb-src');
                if (!thumbUrl) {
                    return;
                }

                thumbInFlight += 1;

                var settled = false;
                var done = function () {
                    if (settled) {
                        return;
                    }
                    settled = true;
                    thumbInFlight = Math.max(0, thumbInFlight - 1);
                    flushThumbQueue();
                };

                img.addEventListener('load', function () {
                    img.dataset.thumbLoaded = '1';
                    img.removeAttribute('data-thumb-src');
                    img.removeAttribute('data-thumb-queued');
                    done();
                }, { once: true });

                img.addEventListener('error', function () {
                    img.removeAttribute('data-thumb-queued');

                    if (!img.isConnected) {
                        done();

                        return;
                    }

                    // Retry once without cache-busting so caches can still be effective.
                    if (img.dataset.thumbRetried !== '1') {
                        img.dataset.thumbRetried = '1';
                        window.setTimeout(function () {
                            if (!img.isConnected || img.dataset.thumbLoaded === '1') {
                                return;
                            }
                            enqueueThumb(img);
                            flushThumbQueue();
                        }, 800);
                    }
                    done();
                }, { once: true });

                // Small stagger to avoid immediate N-way spikes when many rows appear at once.
                window.setTimeout(function () {
                    if (!img.isConnected || img.dataset.thumbLoaded === '1') {
                        done();

                        return;
                    }
                    img.src = thumbUrl;
                }, 90);
            }());
        }
    }

    function enqueueThumb(img) {
        if (!img || img.dataset.thumbQueued === '1' || img.dataset.thumbLoaded === '1') {
            return;
        }
        if (!img.getAttribute('data-thumb-src')) {
            return;
        }

        img.dataset.thumbQueued = '1';
        thumbQueue.push(img);
    }

    function wireDeferredThumbs() {
        var imgs = document.querySelectorAll('#images-table img[data-thumb-src]');
        if (imgs.length === 0) {
            return;
        }

        // Fallback for older browsers: still paced via queue, but no viewport detection.
        if (!('IntersectionObserver' in window)) {
            imgs.forEach(function (img) {
                enqueueThumb(img);
            });
            flushThumbQueue();

            return;
        }

        var scrollBody = document.querySelector('#images-table_wrapper .dataTables_scrollBody');
        thumbObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var img = entry.target;
                thumbObserver.unobserve(img);
                enqueueThumb(img);
            });
            flushThumbQueue();
        }, {
            root: scrollBody || null,
            rootMargin: THUMB_OBSERVER_MARGIN,
            threshold: 0.01,
        });

        imgs.forEach(function (img) {
            thumbObserver.observe(img);
        });
    }

    function destroyTable() {
        if (initRetryTimer) {
            window.clearTimeout(initRetryTimer);
            initRetryTimer = null;
        }
        initRetryCount = 0;
        resetThumbLoader();
        if (dtInstance) {
            try {
                dtInstance.destroy(false);
            } catch (_) {
                // no-op
            }
            dtInstance = null;
        }
    }

    function isDataTableReady() {
        return Boolean(window.jQuery && $.fn && $.fn.DataTable && $.fn.DataTable.isDataTable('#images-table'));
    }

    function scheduleInitRetry() {
        if (isDataTableReady()) {
            return;
        }
        if (initRetryCount >= 8) {
            return;
        }

        initRetryCount += 1;
        if (initRetryTimer) {
            window.clearTimeout(initRetryTimer);
        }
        initRetryTimer = window.setTimeout(function () {
            initRetryTimer = null;
            initImagesTable();
        }, 300 * initRetryCount);
    }

    function initImagesTable() {
        var tableEl = document.getElementById('images-table');
        if (!tableEl || !window.jQuery || typeof $.fn.DataTable !== 'function') {
            scheduleInitRetry();
            return;
        }

        if ($.fn.DataTable.isDataTable('#images-table')) {
            dtInstance = $('#images-table').DataTable();
            wireDeferredThumbs();

            return;
        }

        var dataUrl = tableEl.dataset.datatablesUrl;
        if (!dataUrl) {
            scheduleInitRetry();

            return;
        }

        try {
            dtInstance = $('#images-table').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: dataUrl,
                    type: 'GET'
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'preview', name: 'preview', orderable: false, searchable: false },
                    { data: 'original_name', name: 'original_name' },
                    { data: 'mime', name: 'mime' },
                    { data: 'size', name: 'size' },
                    { data: 'dimensions', name: 'dimensions' },
                    { data: 'status', name: 'status' },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'desc']],
                pageLength: 15,
                lengthMenu: [15, 30, 45],
                paging: true,
                pagingType: 'simple_numbers',
                scrollY: '60vh',
                scrollX: true,
                scrollCollapse: true,
                scroller: {
                    loadingIndicator: true,
                    displayBuffer: 1,
                    boundaryScale: 0.2,
                    serverWait: 500,
                },
                deferRender: true,
                language: {
                    processing: '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Loading...',
                    search: '',
                    zeroRecords: 'No matching images found.',
                    info: 'Showing _START_ to _END_ of _TOTAL_ images',
                    infoEmpty: 'No images found.',
                    infoFiltered: '(filtered from _MAX_ total images)'
                },
                dom: 'rltip',
                initComplete: function () {
                    wireDeferredThumbs();
                },
                drawCallback: function () {
                    wireDeferredThumbs();
                },
            });
            initRetryCount = 0;
        } catch (err) {
            scheduleInitRetry();
        }

        var searchInput = document.getElementById('images-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    if (dtInstance) {
                        dtInstance.search(searchInput.value).draw();
                    }
                }, 250);
            });
        }
    }

    var adminFrame = document.getElementById('admin-content');
    if (adminFrame && !adminFrame._imagesFrameListenerAttached) {
        adminFrame._imagesFrameListenerAttached = true;
        adminFrame.addEventListener('turbo:before-frame-render', destroyTable);
    }
    document.addEventListener('turbo:before-cache', destroyTable);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initImagesTable);
    } else {
        initImagesTable();
    }

    // Safety retries for edge Turbo timing/race conditions.
    window.setTimeout(initImagesTable, 300);
    window.setTimeout(initImagesTable, 1000);

    document.addEventListener('turbo:load', initImagesTable);
    document.addEventListener('turbo:frame-load', initImagesTable);
}());
</script>
<?php $this->end(); ?>
