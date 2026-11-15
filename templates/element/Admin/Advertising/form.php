<?php
declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Advertisement $advertisement
 * @var array<string,string> $sports
 * @var array<int,array{label:string,sportKey:string}> $teams
 * @var array<string,string> $placementOptions
 */

$isEditing = (int)$advertisement->id > 0;
$formUrl = [
    'prefix' => 'Admin',
    'controller' => 'Advertising',
    'action' => $isEditing ? 'edit' : 'add',
];
if ($isEditing) {
    $formUrl[] = (int)$advertisement->id;
}
$sportOptions = ['' => 'Any sport'] + $sports;
$teamChoices = ['' => 'Any team'];
foreach ($teams as $teamId => $team) {
    $teamChoices[$teamId] = $team['label'];
}
?>
<div class="card shadow-sm">
    <div class="card-body">
        <?= $this->Form->create($advertisement, [
            'url' => $formUrl,
            'data-turbo-frame' => 'admin-content',
        ]) ?>

        <?= $this->Form->control('title', [
            'label' => 'Title',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
        <div class="form-text mb-3">Enter a short internal name so you can recognize this advertisement in the campaign list.</div>

        <?= $this->Form->control('placement', [
            'type' => 'select',
            'label' => 'Position',
            'options' => $placementOptions,
            'class' => 'form-select',
            'required' => true,
        ]) ?>
        <div class="form-text mb-3">Choose where this advertisement should appear. Several campaigns can use the same position; display order determines their order.</div>

        <?= $this->Form->control('target_type', [
            'type' => 'select',
            'label' => 'Audience',
            'options' => [
                'site' => 'Site-wide sponsor',
                'content' => 'Content sponsor',
            ],
            'class' => 'form-select',
            'required' => true,
        ]) ?>
        <div class="form-text mb-3">Choose Site-wide for a sponsor that can appear anywhere, or Content sponsor to match pages by sport and/or team.</div>

        <?= $this->Form->control('sport_key', [
            'type' => 'select',
            'label' => 'Sport filter',
            'options' => $sportOptions,
            'class' => 'form-select',
            'empty' => false,
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">For a content sponsor, choose a sport to cover all matching teams, or leave Any sport when targeting only one team. If both filters are set, both must match.</div>

        <?= $this->Form->control('team_id', [
            'type' => 'select',
            'label' => 'Team filter',
            'options' => $teamChoices,
            'class' => 'form-select',
            'empty' => false,
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">Choose one team to show this campaign on pages about that team, or leave Any team to target the selected sport alone.</div>

        <?= $this->Form->control('is_active', [
            'type' => 'checkbox',
            'label' => 'Active',
            'class' => 'form-check-input',
            'templates' => [
                'inputContainer' => '<div class="mb-1">{{content}}</div>',
                'checkboxWrapper' => '<div class="form-check form-switch">{{label}}</div>',
                'nestingLabel' => '{{hidden}}{{input}}<label{{attrs}} class="form-check-label">{{text}}</label>',
            ],
        ]) ?>
        <div class="form-text mb-3">Turn this on to deliver the campaign now; turn it off to keep the campaign saved without displaying it.</div>

        <?= $this->Form->control('mode', [
            'type' => 'select',
            'label' => 'Delivery mode',
            'options' => [
                'custom' => 'Custom HTML',
                'google' => 'Google AdSense',
                'gpt' => 'Google Publisher Tag',
            ],
            'class' => 'form-select',
            'required' => true,
        ]) ?>
        <div class="form-text mb-3">Choose Custom HTML for a sponsor snippet, Google AdSense for an AdSense unit, or Google Publisher Tag for a GPT unit.</div>

        <?= $this->Form->control('html', [
            'type' => 'textarea',
            'label' => 'HTML',
            'rows' => 8,
            'class' => 'form-control font-monospace',
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">1. Copy the complete ad or sponsor snippet from a provider you trust. 2. Paste it here with its opening and closing tags intact. 3. For custom sponsor HTML, replace the example link, image URL, and alt text with your own values. Example: <code>&lt;a href="https://sponsor.example/"&gt;&lt;img src="/img/sponsor.webp" alt="Sponsor name"&gt;&lt;/a&gt;</code>. Custom and AdSense campaigns need markup; GPT can use this as an optional fallback.</div>

        <?= $this->Form->control('sizes_desktop', [
            'type' => 'text',
            'label' => 'Desktop size presets',
            'placeholder' => '970x250, 728x90',
            'class' => 'form-control',
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">For GPT campaigns, enter one or more desktop width-by-height sizes separated by commas, such as <code>970x250, 728x90</code>. Do not enter JSON.</div>

        <?= $this->Form->control('sizes_mobile', [
            'type' => 'text',
            'label' => 'Mobile size presets',
            'placeholder' => '320x50, 300x250',
            'class' => 'form-control',
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">For GPT campaigns, enter one or more mobile width-by-height sizes separated by commas, such as <code>320x50, 300x250</code>. Do not enter JSON.</div>

        <?= $this->Form->control('gpt_unit_path', [
            'type' => 'text',
            'label' => 'GPT unit path',
            'placeholder' => '/123456/racerhistory/home',
            'class' => 'form-control',
            'required' => false,
        ]) ?>
        <div class="form-text mb-3">For Google Publisher Tag, enter the complete unit path supplied by your provider, such as <code>/123456/racerhistory/home</code>.</div>

        <?= $this->Form->control('display_order', [
            'type' => 'number',
            'label' => 'Display order',
            'min' => 0,
            'step' => 1,
            'class' => 'form-control',
            'required' => true,
        ]) ?>
        <div class="form-text mb-3">Enter a non-negative whole number. Lower numbers display first when campaigns share a position.</div>

        <div class="d-flex gap-2 mt-4">
            <?= $this->Form->button('Save Advertisement', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                'Cancel',
                ['prefix' => 'Admin', 'controller' => 'Advertising', 'action' => 'index'],
                ['class' => 'btn btn-outline-secondary', 'data-turbo-frame' => 'admin-content'],
            ) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>
