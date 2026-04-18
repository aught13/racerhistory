<?php

/** Lightweight popup form element.
 * Variables: $popupId, $title, $formUrl, $fields (see previous doc), $successCallback, $targetSelectId.
 */

$popupId = $popupId ?? 'popup-form-modal';
$title = $title ?? 'Add Item';
$formUrl = $formUrl ?? '';
$fields = $fields ?? [];
$successCallback = $successCallback ?? 'handlePopupSuccess';
$targetSelectId = $targetSelectId ?? '';
$hiddenFormId = $hiddenFormId ?? '';
$extraHtml = $extraHtml ?? '';
?>
<div class="modal fade"
     id="<?= h($popupId) ?>"
     tabindex="-1"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= h($title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="<?= h($popupId) ?>-alerts"></div>

                <form
                    id="<?= h($popupId) ?>-form"
                    data-url="<?= h($formUrl) ?>"
                    data-target-select="<?= h($targetSelectId) ?>"
                    data-success-callback="<?= h($successCallback) ?>">

                    <?php foreach ($fields as $f) :
                        $name = $f['name'] ?? '';
                        if (!$name) {
                            continue;
                        }
                        $type = $f['type'] ?? 'text';
                        $label = $f['label'] ?? ucfirst($name);
                        $req = !empty($f['required']);
                        ?>

                    <?php if ($type === 'hidden') : ?>
                        <input type="hidden"
                               id="<?= h($popupId . '-' . $name) ?>"
                               name="<?= h($name) ?>"
                               value="" />
                    <?php else : ?>
                    <div class="mb-3">
                        <label class="form-label" for="<?= h($popupId . '-' . $name) ?>">
                            <?= h($label) ?>
                            <?php if ($req) : ?>
                                <span class="text-danger">*</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($type === 'textarea') : ?>
                            <textarea
                                class="form-control"
                                id="<?= h($popupId . '-' . $name) ?>"
                                name="<?= h($name) ?>"
                                <?= $req ? 'required' : '' ?>></textarea>

                        <?php elseif ($type === 'select') : ?>
                            <select
                                class="form-select"
                                id="<?= h($popupId . '-' . $name) ?>"
                                name="<?= h($name) ?>"
                                <?= $req ? 'required' : '' ?>>
                                <option value="">Select...</option>
                                <?php foreach (($f['options'] ?? []) as $val => $text) : ?>
                                    <option value="<?= h($val) ?>"><?= h($text) ?></option>
                                <?php endforeach; ?>
                            </select>

                        <?php else : ?>
                            <input
                                type="<?= h($type) ?>"
                                class="form-control"
                                id="<?= h($popupId . '-' . $name) ?>"
                                name="<?= h($name) ?>"
                                <?= $req ? 'required' : '' ?> />

                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if ($extraHtml) : ?>
                        <?= $extraHtml ?>
                    <?php endif; ?>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="<?= h($popupId) ?>-submit">Save</button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const id = '<?= $popupId ?>';
    const modal = document.getElementById(id);
    if (!modal) {
        return;
    }

    const form = modal.querySelector('#' + id + '-form');
    const alerts = modal.querySelector('#' + id + '-alerts');
    const submitBtn = modal.querySelector('#' + id + '-submit');

    function showErrors(errs) {
        const list = errs.map(function (e) { return '<li>' + e + '</li>'; }).join('');
        alerts.innerHTML = '<div class="alert alert-danger"><ul class="mb-0">' + list + '</ul></div>';
    }

    function toast(msg, type) {
        const n = document.createElement('div');
        n.className = 'alert alert-' + (type || 'info') + ' position-fixed top-0 end-0 m-3';
        n.textContent = msg;
        document.body.appendChild(n);
        setTimeout(function () { n.remove(); }, 4000);
    }

    submitBtn.addEventListener('click', function () {
        alerts.innerHTML = '';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        const fd = new FormData(form);
        const csrf = document.querySelector('meta[name="csrfToken"]');
        if (csrf) {
            fd.append('_csrfToken', csrf.getAttribute('content'));
        }

        // Copy FormProtection tokens from a matching hidden form to avoid controller/token mismatch.
        try {
            var hidden = null;
            if ('<?= h($hiddenFormId) ?>') {
                var el = document.getElementById('<?= h($hiddenFormId) ?>');
                if (el) {
                    hidden = el;
                }
            }

            if (!hidden) {
                var url = new URL(form.dataset.url, window.location.origin);
                var parts = url.pathname.split('/').filter(Boolean);
                var controller = '';
                if (parts.length >= 2) {
                    controller = parts[1];
                }

                if (controller) {
                    var candidates = [controller];
                    if (controller.endsWith('s')) {
                        candidates.push(controller.slice(0, -1));
                    } else {
                        candidates.push(controller + 's');
                    }
                    for (var i = 0; i < candidates.length; i++) {
                        var c = candidates[i];
                        var el2 = document.getElementById('hidden-' + c + '-form');
                        if (el2) {
                            hidden = el2;
                            break;
                        }
                    }
                }
            }

            if (!hidden) {
                hidden = document.querySelector('[id^="hidden-"][id$="-form"]');
            }

            if (hidden) {
                try { console.log && console.log('popup_form copying tokens from', hidden.id); } catch (e) { }
                hidden.querySelectorAll('input[name^="_Token"]').forEach(function (i) {
                    fd.append(i.name, i.value);
                });
            }
        } catch (e) {
            // ignore URL parsing failures and skip copying tokens
        }

        fetch(form.dataset.url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) {
                return r.text().then(function (text) {
                    try { return JSON.parse(text); } catch (e) { return { __rawText: text }; }
                });
            })
            .then(function (data) {
                if (data && data.__rawText) {
                    // Show raw server response for debugging when it's not valid JSON
                    showErrors(['Invalid JSON response from server', data.__rawText]);
                    return;
                }

                if (data.success) {
                    if (form.dataset.targetSelect && data.newOption) {
                        var sel = document.getElementById(form.dataset.targetSelect);
                        if (sel) {
                            var o = new Option(data.newOption.text, data.newOption.value, true, true);
                            sel.add(o);
                        }
                    }

                    if (form.dataset.successCallback && typeof window[form.dataset.successCallback] === 'function') {
                        window[form.dataset.successCallback](data);
                    }

                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                    toast(data.message || 'Saved', 'success');
                    form.reset();
                } else {
                    showErrors(data.errors || ['Unable to save']);
                }
            })
            .catch(function () { showErrors(['Network error']); })
            .finally(function () { submitBtn.disabled = false; submitBtn.textContent = 'Save'; });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        alerts.innerHTML = '';
    });
})();
</script>
