<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed,help?:string,options?:array<string,string>}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */
?>
<turbo-frame id="site_options_frame">
    <?= $this->Flash->render() ?>

    <?php
    // If any ad slot is in Google AdSense mode, verify ads.txt contains the configured Publisher ID.
    $googleEnabled = false;
    foreach ($siteOptionDefinitions as $optKey => $def) {
        if (!is_string($optKey)) {
            continue;
        }

        if (
            str_ends_with($optKey, '_google_mode')
            || str_ends_with($optKey, '_ad_mode')
            || str_ends_with($optKey, '_mode')
        ) {
            $modeValue = $siteOptions[$optKey] ?? '';
            if (in_array($modeValue, ['google', 'gpt', '1', true], true)) {
                $googleEnabled = true;
                break;
            }
        }
    }

    if ($googleEnabled) {
        $publisherId = trim((string)($siteOptions['ad_publisher_id'] ?? ''));
        $adScript = trim((string)($siteOptions['ad_script'] ?? ''));

        // Try to extract publisher id from script if admin didn't fill the field.
        if ($publisherId === '' && $adScript !== '') {
            if (preg_match('/(ca-pub-\d+|pub-\d+)/i', $adScript, $m)) {
                $publisherId = $m[1] ?? $m[0];
            }
        }

        $adsFilename = 'ads.txt';
        $adsFullPath = WWW_ROOT . $adsFilename;

        if ($publisherId === '') {
            echo $this->Html->div('alert alert-warning mb-3', 'Google-mode ads are enabled but no Publisher ID is configured. Please set the Publisher ID in Site Options (Ads - Publisher ID).');
        } else {
            if (!file_exists($adsFullPath)) {
                echo $this->Html->div('alert alert-warning mb-3', 'Google-mode ads are enabled but ' . h($adsFilename) . ' is missing from the webroot. Upload an ads.txt that contains your Publisher ID (' . h($publisherId) . ').');
            } else {
                $contents = (string)file_get_contents($adsFullPath);
                if (stripos($contents, $publisherId) !== false) {
                    echo $this->Html->div('alert alert-success mb-3', "Authorized: Your publisher ID was found in the site's ads.txt file.");
                } else {
                    echo $this->Html->div('alert alert-warning mb-3', h($adsFilename) . ' found but Publisher ID ' . h($publisherId) . ' was not present. Please add a line like: google.com, ' . h($publisherId) . ', DIRECT, f08c47fec0942fa0');
                }
            }
        }
    }

    ?>

    <?php
    $adSlots = [
        'below_nav' => [
            'label' => 'Below Navigation',
            'description' => 'A display placement immediately below the primary navigation.',
        ],
        'below_content' => [
            'label' => 'Below Content',
            'description' => 'A display placement after the main page content.',
        ],
        'footer' => [
            'label' => 'Footer',
            'description' => 'A display placement near the site footer.',
        ],
        'homepage_mid' => [
            'label' => 'Homepage Mid-Page',
            'description' => 'A display placement between major homepage content blocks.',
        ],
        'news_after_first' => [
            'label' => 'News After First Post',
            'description' => 'An in-feed placement shown after the first news post.',
        ],
        'news_every_fifth' => [
            'label' => 'News Every Fifth Post',
            'description' => 'An in-feed placement repeated after every fifth news post.',
        ],
        'news_sidebar_1' => [
            'label' => 'News Sidebar 1',
            'description' => 'The first sidebar placement on news pages.',
        ],
        'news_sidebar_2' => [
            'label' => 'News Sidebar 2',
            'description' => 'The second sidebar placement on news pages.',
        ],
    ];
    $helpFor = static function (string $optionKey, array $definition): string {
        $help = trim((string)($definition['help'] ?? ''));

        return $help !== '' ? $help : 'Enter or choose the value used by this site setting.';
    };
    $formatSizePresets = static function (mixed $value): string {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return $value;
        }

        $presets = [];
        foreach ($decoded as $size) {
            if (is_array($size) && count($size) >= 2) {
                $dimensions = array_values($size);
                if (is_numeric($dimensions[0]) && is_numeric($dimensions[1])) {
                    $presets[] = (int)$dimensions[0] . 'x' . (int)$dimensions[1];
                }
            }
        }

        return implode(', ', $presets);
    };
    $renderHelp = static function (string $help): void {
        echo '<div class="form-text">' . h($help) . '</div>';
    };
    ?>

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">Global Site Settings</h3>
            <?php if ($this->Rbac->can('Roles', 'read')) : ?>
                <?= $this->Html->link('Manage RBAC Roles', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index'], ['class' => 'btn btn-sm btn-outline-secondary float-end', 'data-turbo-frame' => 'admin-content']) ?>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <?= $this->Form->create(null, [
                'url' => ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit'],
                'templates' => [
                    'inputContainer' => '<div class="mb-3">{{content}}</div>',
                    'label' => '<label{{attrs}} class="form-label">{{text}}</label>',
                    'inputContainerError' => '<div class="mb-3">{{content}}<div class="invalid-feedback d-block">{{error}}</div></div>',
                ],
                'data-turbo-frame' => 'site_options_frame',
            ]) ?>

            <fieldset class="mb-4">
                <legend class="h4">General Site Settings</legend>
                <p class="text-muted">Configure site-wide behavior. Each setting includes its completion instructions below the control.</p>

            <?php foreach ($siteOptionDefinitions as $optionKey => $definition) : ?>
                <?php
                if (is_string($optionKey) && str_starts_with($optionKey, 'ad_')) {
                    continue;
                }
                $type = $definition['type'];
                $label = $definition['label'];
                $value = $siteOptions[$optionKey] ?? ($definition['default'] ?? null);
                $help = $helpFor($optionKey, $definition);
                ?>

                <?php if ($optionKey === 'role_privileges') : ?>
                    <div class="mb-3">
                        <label class="form-label"><?= h($label) ?></label>
                        <div class="small text-muted mb-2">Role privileges are now managed in the dedicated RBAC role matrix.</div>
                        <?php $renderHelp($help); ?>
                        <?php if ($this->Rbac->can('Roles', 'read')) : ?>
                            <div class="d-grid gap-2 col-6 p-0">
                                <?= $this->Html->link('Manage RBAC Roles', ['prefix' => 'Admin', 'controller' => 'Roles', 'action' => 'index'], ['class' => 'btn btn-primary btn-lg', 'data-turbo-frame' => 'admin-content']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($type === 'checkbox') : ?>
                    <?= $this->Form->control($optionKey, [
                        'type' => 'checkbox',
                        'label' => $label,
                        'checked' => (bool)$value,
                        'class' => 'form-check-input',
                        'required' => false,
                        'templates' => [
                            'inputContainer' => '<div class="mb-3">{{content}}</div>',
                            'checkboxWrapper' => '<div class="form-check form-switch">{{label}}</div>',
                            'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}} class="form-check-label">{{text}}</label>',
                        ],
                    ]) ?>
                    <?php $renderHelp($help); ?>
                <?php else : ?>
                    <?php
                    $inputType = in_array($type, ['number', 'email', 'textarea'], true) ? $type : 'text';
                    $controlOptions = [
                        'type' => $inputType,
                        'label' => $label,
                        'value' => $value,
                        'class' => 'form-control',
                        'required' => false,
                    ];

                    if ($inputType === 'number') {
                        $controlOptions['step'] = 1;
                        $controlOptions['min'] = 1;
                    }

                    if ($inputType === 'textarea') {
                        $controlOptions['rows'] = 4;
                    }
                    ?>
                    <?= $this->Form->control($optionKey, $controlOptions) ?>
                    <?php $renderHelp($help); ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="h4">Advertising Configuration</legend>
                <p class="text-muted">Configure the shared provider settings first, then complete each ad unit independently. The legacy Google mode checkboxes are retained in storage but are no longer shown because Delivery Mode is the single source of truth.</p>

                <?php foreach (['ad_script', 'ad_publisher_id'] as $optionKey) : ?>
                    <?php
                    if (!isset($siteOptionDefinitions[$optionKey])) {
                        continue;
                    }
                    $definition = $siteOptionDefinitions[$optionKey];
                    $value = $siteOptions[$optionKey] ?? ($definition['default'] ?? null);
                    $inputType = $definition['type'] === 'textarea' ? 'textarea' : 'text';
                    ?>
                    <?= $this->Form->control($optionKey, [
                        'type' => $inputType,
                        'label' => $definition['label'],
                        'value' => $value,
                        'class' => 'form-control',
                        'required' => false,
                        'rows' => $inputType === 'textarea' ? 4 : null,
                    ]) ?>
                    <?php $renderHelp($helpFor($optionKey, $definition)); ?>
                <?php endforeach; ?>
            </fieldset>

            <?php foreach ($adSlots as $slotName => $slotMeta) : ?>
                <?php
                $prefix = 'ad_' . $slotName;
                $activeKey = $prefix . '_active';
                $htmlKey = $prefix . '_html';
                $modeKey = $prefix . '_mode';
                $desktopSizesKey = $prefix . '_sizes_desktop';
                $mobileSizesKey = $prefix . '_sizes_mobile';
                $gptPathKey = $prefix . '_gpt_unit_path';
                $legacyModeKey = $prefix . '_google_mode';
                $modeValue = strtolower(trim((string)($siteOptions[$modeKey] ?? '')));
                if (!in_array($modeValue, ['custom', 'google', 'gpt'], true)) {
                    $modeValue = !empty($siteOptions[$legacyModeKey]) ? 'google' : 'custom';
                }
                ?>
                <fieldset class="mb-4 border rounded p-3">
                    <legend class="h5 px-2 mb-1"><?= h($slotMeta['label']) ?></legend>
                    <p class="text-muted small mb-3"><?= h($slotMeta['description']) ?></p>

                    <?php if (isset($siteOptionDefinitions[$activeKey])) : ?>
                        <?= $this->Form->control($activeKey, [
                            'type' => 'checkbox',
                            'label' => $siteOptionDefinitions[$activeKey]['label'],
                            'checked' => (bool)($siteOptions[$activeKey] ?? false),
                            'class' => 'form-check-input',
                            'required' => false,
                            'templates' => [
                                'inputContainer' => '<div class="mb-3">{{content}}</div>',
                                'checkboxWrapper' => '<div class="form-check form-switch">{{label}}</div>',
                                'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}} class="form-check-label">{{text}}</label>',
                            ],
                        ]) ?>
                        <?php $renderHelp($helpFor($activeKey, $siteOptionDefinitions[$activeKey])); ?>
                    <?php endif; ?>

                    <?php if (isset($siteOptionDefinitions[$modeKey])) : ?>
                        <?= $this->Form->control($modeKey, [
                            'type' => 'select',
                            'label' => $siteOptionDefinitions[$modeKey]['label'],
                            'value' => $modeValue,
                            'options' => [
                                'custom' => 'Custom house ad',
                                'google' => 'Google AdSense',
                                'gpt' => 'Google Publisher Tag',
                            ],
                            'empty' => false,
                            'class' => 'form-select',
                            'required' => false,
                        ]) ?>
                        <?php $renderHelp($helpFor($modeKey, $siteOptionDefinitions[$modeKey])); ?>
                    <?php endif; ?>

                    <?php if (isset($siteOptionDefinitions[$htmlKey])) : ?>
                        <?= $this->Form->control($htmlKey, [
                            'type' => 'textarea',
                            'label' => $siteOptionDefinitions[$htmlKey]['label'],
                            'value' => $siteOptions[$htmlKey] ?? '',
                            'class' => 'form-control',
                            'rows' => 4,
                            'required' => false,
                        ]) ?>
                        <?php $renderHelp($helpFor($htmlKey, $siteOptionDefinitions[$htmlKey])); ?>
                    <?php endif; ?>

                    <?php foreach (
                    [
                        $desktopSizesKey => 'Desktop size presets',
                        $mobileSizesKey => 'Mobile size presets',
                    ] as $sizeKey => $sizeLabel
) : ?>
                        <?php if (!isset($siteOptionDefinitions[$sizeKey])) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?= $this->Form->control($sizeKey, [
                            'type' => 'text',
                            'label' => $sizeLabel,
                            'value' => $formatSizePresets($siteOptions[$sizeKey] ?? ''),
                            'placeholder' => '970x250, 728x90',
                            'class' => 'form-control',
                            'required' => false,
                        ]) ?>
                        <?php $renderHelp($helpFor($sizeKey, $siteOptionDefinitions[$sizeKey])); ?>
                    <?php endforeach; ?>

                    <?php if (isset($siteOptionDefinitions[$gptPathKey])) : ?>
                        <?= $this->Form->control($gptPathKey, [
                            'type' => 'text',
                            'label' => $siteOptionDefinitions[$gptPathKey]['label'],
                            'value' => $siteOptions[$gptPathKey] ?? '',
                            'placeholder' => '/123456/racerhistory/home',
                            'class' => 'form-control',
                            'required' => false,
                        ]) ?>
                        <?php $renderHelp($helpFor($gptPathKey, $siteOptionDefinitions[$gptPathKey])); ?>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; ?>

            <div class="d-flex gap-2 mt-3">
                <?= $this->Form->button('Save Settings', ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link(
                    'Reload',
                    ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'edit'],
                    ['class' => 'btn btn-outline-secondary', 'data-turbo-frame' => 'site_options_frame'],
                ) ?>
            </div>

            <?= $this->Form->end() ?>
            <?php
            // Offer a one-click write of ads.txt when a Publisher ID is configured.
            $publisherIdBtn = trim((string)($siteOptions['ad_publisher_id'] ?? ''));
            if ($publisherIdBtn === '') {
                // try to extract from script as a fallback
                $scriptFallback = trim((string)($siteOptions['ad_script'] ?? ''));
                if ($scriptFallback !== '' && preg_match('/(ca-pub-\d+|pub-\d+)/i', $scriptFallback, $m)) {
                    $publisherIdBtn = $m[1] ?? $m[0];
                }
            }

            if ($publisherIdBtn !== '') :
                echo $this->Form->postButton('Write ads.txt', ['prefix' => 'Admin', 'controller' => 'SiteOptions', 'action' => 'writeAdsTxt'], ['class' => 'btn btn-outline-secondary mt-3', 'data-turbo-frame' => 'site_options_frame']);
            endif;
            ?>
        </div>
    </div>
</turbo-frame>
