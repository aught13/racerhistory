<?php

/**
 * Admin Layout Template
 *
 * Administrative interface layout providing a clean, functional design for admin operations.
 * Features simplified Bootstrap styling optimized for administrative tasks and data management.
 *
 * Features:
 * - Bootstrap 5.3.2 framework for consistent admin UI
 * - Admin navigation element integration
 * - Bootstrap Icons for admin interface elements
 * - Custom cake.css for admin-specific styling
 * - jQuery support for enhanced admin functionality
 * - Container-based layout for admin content
 *
 * Security:
 * - Admin-only access required
 * - Authentication status integration
 * - CSRF protection for admin forms
 *
 * @var \App\View\AppView $this
 */
?>
<!DOCTYPE html>
<html>

<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="turbo-refresh-method" content="morph">
    <meta name="turbo-refresh-scroll" content="reset">
    <meta name="csrfToken" content="<?= $this->request->getAttribute('csrfToken') ?>">
    <title><?= $this->fetch('title') ?></title>
    <?= $this->Html->meta('icon') ?>
    <script>
        // Admin UI is intentionally light-only; set this before CSS paints.
        (function () {
            const root = document.documentElement;
            root.setAttribute('data-bs-theme', 'light');
            root.setAttribute('data-theme', 'light');
            root.classList.remove('dark-mode', 'theme-dark');
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <?= $this->Html->css(['cake']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>

    <!-- Import maps + Hotwire Turbo for SPA-like admin navigation -->
    <?= $this->Html->importmap(require CONFIG . 'importmap.php') ?>
    <?= $this->Html->script('admin-turbo', ['type' => 'module', 'ext' => '.mjs']) ?>

    <!-- jQuery for admin pages (used by DataTables and other plugins) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous">
    </script>
    <?= $this->Html->script('admin.js') ?>
    <?= $this->Html->script('admin-image-pages.js') ?>
    <?= $this->fetch('script') ?>
    <script>
        (function () {
            if (window.__rhImageRetryInit) {
                return;
            }
            window.__rhImageRetryInit = true;
            let retrySequence = 0;

            function isServeUrl(url) {
                if (!url) {
                    return false;
                }
                try {
                    const parsed = new URL(url, window.location.origin);
                    return parsed.pathname.indexOf('/images/serve/') === 0;
                } catch (err) {
                    return false;
                }
            }

            function bustUrl(url) {
                const parsed = new URL(url, window.location.origin);
                parsed.searchParams.set('_ts', String(Date.now()));

                return parsed.pathname + parsed.search;
            }

            function bustSrcset(srcset) {
                return srcset
                    .split(',')
                    .map(function (candidate) {
                        const trimmed = candidate.trim();
                        if (!trimmed) {
                            return trimmed;
                        }

                        const splitAt = trimmed.search(/\s/);
                        const url = splitAt === -1 ? trimmed : trimmed.slice(0, splitAt);
                        const descriptor = splitAt === -1 ? '' : trimmed.slice(splitAt);

                        if (!isServeUrl(url)) {
                            return trimmed;
                        }

                        return bustUrl(url) + descriptor;
                    })
                    .join(', ');
            }

            function retryBrokenImage(img) {
                if (!(img instanceof HTMLImageElement)) {
                    return;
                }
                if (!img.isConnected) {
                    return;
                }
                if (img.dataset.rhNoRetry === '1') {
                    return;
                }
                if (img.dataset.rhRetryAttempted === '1') {
                    return;
                }

                const current = img.currentSrc || img.getAttribute('src') || '';
                if (!isServeUrl(current)) {
                    return;
                }

                img.dataset.rhRetryAttempted = '1';

                // Stagger retries to avoid amplifying transient backend overload.
                const delayMs = Math.min(2000, retrySequence * 75);
                retrySequence += 1;
                window.setTimeout(function () {
                    const picture = img.closest('picture');
                    if (picture) {
                        picture.querySelectorAll('source').forEach(function (sourceEl) {
                            const srcset = sourceEl.getAttribute('srcset');
                            if (!srcset) {
                                return;
                            }
                            sourceEl.setAttribute('srcset', bustSrcset(srcset));
                        });
                    }

                    const baseSrc = img.getAttribute('src') || current;
                    img.setAttribute('src', bustUrl(baseSrc));
                }, delayMs);
            }

            function retryAlreadyBroken() {
                document.querySelectorAll('img').forEach(function (img) {
                    if (img.complete && img.naturalWidth === 0) {
                        retryBrokenImage(img);
                    }
                });
            }

            document.addEventListener('error', function (event) {
                retryBrokenImage(event.target);
            }, true);

            document.addEventListener('DOMContentLoaded', retryAlreadyBroken);
            document.addEventListener('turbo:load', retryAlreadyBroken);
            document.addEventListener('turbo:frame-load', retryAlreadyBroken);
        })();
    </script>
</head>

<body>
    <?= $this->element('Admin/nav') ?>
    <main class="main">
        <div class="container">
            <turbo-frame id="admin-content" data-turbo-action="advance">
                <?= $this->Flash->render() ?>
                <?= $this->fetch('content') ?>
            </turbo-frame>
        </div>
    </main>
    <footer class="footer bg-light py-3 mt-4">
        <div class="container text-center">
            <span class="text-muted">&copy; <?= date('Y') ?> RacerHistory Admin</span>
        </div>
    </footer>
    <script>
        // Small runtime check to help debug whether the admin layout and admin.js are executing.
        try {
            console.log('admin layout loaded — URL:', window.location.href);
            console.log('showConfirmDelete present:', typeof window.showConfirmDelete);
        } catch (e) { /* ignore */ }
    </script>
</body>

</html>
