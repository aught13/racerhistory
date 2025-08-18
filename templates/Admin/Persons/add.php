<?php $this->assign('title', 'Add Person'); ?>
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h2 class="mb-0">Add New Person</h2>
                </div>
                <div class="card-body">
                    <?= $this->Form->create($person, [
                        'url' => ['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'add'],
                        'class' => 'needs-validation',
                        'novalidate' => true
                    ]) ?>

                    <div class="row g-3">
                        <div class="col-md-6"> <?= $this->Form->control('first', ['class' => 'form-control', 'label' => ['text' => 'First Name', 'class' => 'form-label'], 'maxlength' => 30]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('last', ['class' => 'form-control', 'label' => ['text' => 'Last Name', 'class' => 'form-label'], 'maxlength' => 30]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('display', ['class' => 'form-control', 'label' => ['text' => 'Display Name', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('full', ['class' => 'form-control', 'label' => ['text' => 'Full Name', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('birth', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Birth Date', 'class' => 'form-label']]); ?> </div>
                        <div class="col-md-6"> <?= $this->Form->control('death', ['type' => 'date', 'class' => 'form-control', 'label' => ['text' => 'Death Date', 'class' => 'form-label']]); ?> </div>
                        <div class="col-12"> <?= $this->Form->control('person_image', ['class' => 'form-control', 'label' => ['text' => 'Image Path', 'class' => 'form-label'], 'maxlength' => 162]); ?> </div>
                        <div class="col-12"> <?= $this->Form->control('bio', [
                                'type' => 'textarea',
                                'rows' => 8,
                                'class' => 'form-control',
                                'id' => 'bio-editor',
                                'label' => ['text' => 'Biography', 'class' => 'form-label'],
                                'templates' => [
                                    // Ensure we keep the textarea markup simple for JS replacement
                                    'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>'
                                ]
                            ]); ?>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <?= $this->Form->button('Save Person', ['class' => 'btn btn-success']) ?>
                        <a href="<?= $this->Url->build(['prefix' => 'Admin', 'controller' => 'Persons', 'action' => 'index']) ?>" class="btn btn-secondary">Cancel</a>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
<?php
// Load CKEditor (Classic) via CDN & initialize the bio textarea progressively.
echo $this->Html->script('https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js', ['block' => true]);
echo $this->Html->scriptBlock(<<<JS
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('bio-editor');
    if (!el) return;
    ClassicEditor.create(el, {
        toolbar: {
            items: [
                'heading','|','bold','italic','underline','link','bulletedList','numberedList','blockQuote','|','undo','redo'
            ]
        }
    }).catch(function(error){ console.error('CKEditor init failed', error); });
});
JS, ['block' => true]);
?>
</div>
