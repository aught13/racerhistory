<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<int,\App\Model\Entity\Advertisement> $advertisements
 * @var array{global_script:string,publisher_id:string} $settings
 * @var array<string,string> $sports
 * @var array<int,array{label:string,sportKey:string}> $teams
 * @var array<string,string> $placementOptions
 */

$this->assign('title', 'Advertising');
$googleAdsEnabled = false;
foreach ($advertisements as $advertisement) {
    if ($advertisement->is_active && $advertisement->mode === 'google') {
        $googleAdsEnabled = true;
        break;
    }
}

$publisherId = trim($settings['publisher_id']);
if ($publisherId === '' && preg_match('/(ca-pub-\d+|pub-\d+)/i', $settings['global_script'], $matches) === 1) {
    $publisherId = (string)($matches[1] ?? $matches[0]);
}
?>
<div class="container py-4">
    <?= $this->Flash->render() ?>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-2">Advertising</h1>
            <p class="text-muted mb-0">Manage site-wide sponsors and sport/team-targeted content campaigns.</p>
        </div>
        <?= $this->Html->link(
            'Add Advertisement',
            ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'add'],
            ['class' => 'btn btn-primary', 'data-turbo-frame' => 'admin-content'],
        ) ?>
    </div>

    <?php if ($googleAdsEnabled) : ?>
        <?php
        $adsFilename = 'ads.txt';
        $adsFullPath = WWW_ROOT . $adsFilename;
        if ($publisherId === '') :
            echo $this->Html->div('alert alert-warning', 'Google AdSense campaigns are active, but no Publisher ID is configured below.');
        elseif (!file_exists($adsFullPath)) :
            echo $this->Html->div(
                'alert alert-warning',
                $adsFilename . ' is missing from webroot. Add an ads.txt file containing Publisher ID ' . h($publisherId) . '.',
            );
        else :
            $contents = (string)file_get_contents($adsFullPath);
            if (stripos($contents, $publisherId) !== false) :
                echo $this->Html->div('alert alert-success', 'Authorized: Your publisher ID was found in the site ads.txt file.');
            else :
                echo $this->Html->div(
                    'alert alert-warning',
                    $adsFilename . ' does not contain Publisher ID ' . h($publisherId)
                    . '. Add a line like: google.com, ' . h($publisherId) . ', DIRECT, f08c47fec0942fa0',
                );
            endif;
        endif;
        ?>
    <?php endif; ?>

    <section class="card shadow-sm mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Provider Settings</h2>
        </div>
        <div class="card-body">
            <?= $this->Form->create(null, [
                'url' => ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'saveSettings'],
                'data-turbo-frame' => 'admin-content',
            ]) ?>
            <?= $this->Form->control('global_script', [
                'type' => 'textarea',
                'label' => 'Global provider script',
                'value' => $settings['global_script'],
                'rows' => 4,
                'class' => 'form-control',
                'required' => false,
            ]) ?>
            <div class="form-text mb-3">Use this only when your provider requires a site-wide loader. 1. Copy the full script tag from the provider. 2. Paste it here with both opening and closing tags intact. 3. Save the settings. Example: <code>&lt;script async src="https://example.com/ads.js"&gt;&lt;/script&gt;</code>.</div>

            <?= $this->Form->control('publisher_id', [
                'type' => 'text',
                'label' => 'Publisher ID',
                'value' => $settings['publisher_id'],
                'class' => 'form-control',
                'required' => false,
            ]) ?>
            <div class="form-text mb-3">Enter the ID supplied by your ad provider, such as <code>ca-pub-1234567890123456</code>. Do not paste the ads.txt row or surrounding HTML.</div>

            <div class="d-flex flex-wrap gap-2">
                <?= $this->Form->button('Save Provider Settings', ['class' => 'btn btn-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
            <?php if ($publisherId !== '') : ?>
                <?= $this->Form->postButton(
                    'Write ads.txt',
                    ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'writeAdsTxt'],
                    ['class' => 'btn btn-outline-secondary mt-2', 'data-turbo-frame' => 'admin-content'],
                ) ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">Advertisements</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Position</th>
                        <th>Audience</th>
                        <th>Delivery</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($advertisements === []) : ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No advertisements have been configured yet.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($advertisements as $advertisement) : ?>
                        <?php
                        $audience = 'Site-wide sponsor';
                        if ($advertisement->target_type === 'content') {
                            $criteria = [];
                            if ($advertisement->sport_key !== null && $advertisement->sport_key !== '') {
                                $criteria[] = $sports[$advertisement->sport_key] ?? $advertisement->sport_key;
                            }
                            if ($advertisement->team_id !== null) {
                                $criteria[] = $teams[(int)$advertisement->team_id]['label'] ?? 'Team ' . $advertisement->team_id;
                            }
                            $audience = 'Content sponsor: ' . implode(' / ', $criteria);
                        }
                        ?>
                        <tr>
                            <td>
                                <?= $this->Html->link(
                                    (string)$advertisement->title,
                                    ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'edit', (int)$advertisement->id],
                                    ['data-turbo-frame' => 'admin-content'],
                                ) ?>
                            </td>
                            <td><?= h($placementOptions[$advertisement->placement] ?? $advertisement->placement) ?></td>
                            <td><?= h($audience) ?></td>
                            <td><?= h(strtoupper((string)$advertisement->mode)) ?></td>
                            <td>
                                <span class="badge <?= $advertisement->is_active ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                    <?= $advertisement->is_active ? 'Active' : 'Disabled' ?>
                                </span>
                            </td>
                            <td><?= (int)$advertisement->display_order ?></td>
                            <td class="text-end text-nowrap">
                                <?= $this->Html->link(
                                    'Edit',
                                    ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'edit', (int)$advertisement->id],
                                    ['class' => 'btn btn-sm btn-outline-primary', 'data-turbo-frame' => 'admin-content'],
                                ) ?>
                                <?= $this->Form->postButton(
                                    'Delete',
                                    ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'delete', (int)$advertisement->id],
                                    ['class' => 'btn btn-sm btn-outline-danger', 'data-turbo-frame' => 'admin-content'],
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted small">
            Content campaign filters use AND matching when both a sport and team are selected. Site-wide sponsors are available on every page.
        </div>
    </section>
</div>
