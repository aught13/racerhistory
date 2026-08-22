<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array<string,array{label:string,type:string,default:mixed}> $siteOptionDefinitions
 * @var array<string,mixed> $siteOptions
 */
?>
<turbo-frame id="site_options_frame">
    <?= $this->Flash->render() ?>

    <?php
    // If any ad slot is in Google AdSense mode, verify ads.txt contains the configured Publisher ID.
    $googleEnabled = false;
    foreach ($siteOptionDefinitions as $optKey => $def) {
        if (is_string($optKey) && str_ends_with($optKey, '_google_mode')) {
            if (!empty($siteOptions[$optKey])) {
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

    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">Global Site Settings</h3>
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

            <?php foreach ($siteOptionDefinitions as $optionKey => $definition) : ?>
                <?php
                $type = $definition['type'];
                $label = $definition['label'];
                $value = $siteOptions[$optionKey] ?? ($definition['default'] ?? null);
                ?>

                <?php if ($type === 'checkbox') : ?>
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
                <?php endif; ?>
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
