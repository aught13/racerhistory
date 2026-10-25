<?php

/** Lightweight popup form element.
 * Variables: $popupId, $title, $formUrl, $fields, $successCallback, $targetSelectId.
 * Field configs may provide help text and a display-name preview target.
 *
 * @var \App\View\AppView $this
 */

$popupId = $popupId ?? 'popup-form-modal';
$title = $title ?? 'Add Item';
$formUrl = $formUrl ?? '';
$fields = $fields ?? [];
$successCallback = $successCallback ?? 'handlePopupSuccess';
$targetSelectId = $targetSelectId ?? '';
$hiddenFormId = $hiddenFormId ?? '';
$extraHtml = $extraHtml ?? '';
$teamNamePreview = !empty($teamNamePreview);
$opponentNamePreview = !empty($opponentNamePreview);

$hasPlaceCountryField = false;
$hasPlaceCityField = false;
$hasPlaceStateField = false;
foreach ($fields as $fieldConfig) {
    $fieldName = $fieldConfig['name'] ?? '';
    $fieldType = $fieldConfig['type'] ?? 'text';
    if ($fieldType === 'hidden') {
        continue;
    }

    if ($fieldName === 'place_country') {
        $hasPlaceCountryField = true;
    }
    if ($fieldName === 'place_city') {
        $hasPlaceCityField = true;
    }
    if ($fieldName === 'place_state') {
        $hasPlaceStateField = true;
    }
}

$hasPlaceLocationBehavior =
    $hasPlaceCountryField &&
    $hasPlaceCityField &&
    $hasPlaceStateField;
$formControllers = [];
if ($hasPlaceLocationBehavior) {
    $formControllers[] = 'place-location';
}
if ($teamNamePreview) {
    $formControllers[] = 'team-name-preview';
}
if ($opponentNamePreview) {
    $formControllers[] = 'opponent-name-preview';
}
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
                    data-success-callback="<?= h($successCallback) ?>"
                    <?= $formControllers !== [] ? 'data-controller="' . h(implode(' ', $formControllers)) . '"' : '' ?>>

                    <?php if ($hasPlaceLocationBehavior) : ?>
                    <div class="mb-3">
                        <label class="form-label" for="<?= h($popupId) ?>-country-search">Country Search (common name)</label>
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="<?= h($popupId) ?>-country-search"
                                autocomplete="off"
                                placeholder="Type a country name (e.g., United States)"
                                data-place-location-target="countrySearch"
                                data-action="input->place-location#onCountryQuery blur->place-location#onCountryBlur" />
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                data-place-location-target="countrySearchBtn"
                                data-action="click->place-location#onSearchCountries">
                                Search
                            </button>
                        </div>
                        <div class="mt-1 position-relative" data-place-location-target="countryResults"></div>
                        <small class="text-muted d-block mt-1" data-place-location-target="countryMeta">Select a country to store its ISO3 code and load subdivisions/localities.</small>
                    </div>
                    <?php endif; ?>

                    <?php foreach ($fields as $f) :
                        $name = $f['name'] ?? '';
                        if (!$name) {
                            continue;
                        }
                        $type = $f['type'] ?? 'text';
                        $label = $f['label'] ?? ucfirst($name);
                        $req = !empty($f['required']);
                        $help = $f['help'] ?? '';
                        $fieldAttributes = [];
                        foreach (($f['attributes'] ?? []) as $attribute => $value) {
                            $fieldAttributes[] = h((string)$attribute) . '="' . h((string)$value) . '"';
                        }
                        $fieldAttributes = implode(' ', $fieldAttributes);
                        $previewTarget = $f['previewTarget'] ?? null;
                        $previewIdentifier = $teamNamePreview
                            ? 'team-name-preview'
                            : ($opponentNamePreview ? 'opponent-name-preview' : '');
                        $previewAttrs = is_string($previewTarget)
                            && $previewTarget !== ''
                            && $previewIdentifier !== ''
                            ? 'data-' . $previewIdentifier . '-target="' . h($previewTarget) .
                                '" data-action="input->' . $previewIdentifier .
                                '#update change->' . $previewIdentifier . '#update"'
                            : '';
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
                                <?= $req ? 'required' : '' ?>
                                <?= $fieldAttributes ?>
                                <?= $previewAttrs ?>></textarea>

                            <?php elseif ($type === 'select') : ?>
                            <select
                                class="form-select"
                                id="<?= h($popupId . '-' . $name) ?>"
                                name="<?= h($name) ?>"
                                <?= $req ? 'required' : '' ?>
                                <?= $fieldAttributes ?>
                                <?= $previewAttrs ?>>
                                <option value="">Select...</option>
                                <?php foreach (($f['options'] ?? []) as $val => $text) : ?>
                                    <option value="<?= h($val) ?>"><?= h($text) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <?php else : ?>
                                <?php
                                $inputClass = 'form-control';
                                $inputAttrs = [];

                                if ($hasPlaceLocationBehavior && $name === 'place_country') {
                                    $inputClass .= ' mt-2';
                                    $inputAttrs[] = 'readonly';
                                    $inputAttrs[] = 'data-place-location-target="countryCode"';
                                }

                                if ($hasPlaceLocationBehavior && $name === 'place_city') {
                                    $inputAttrs[] = 'list="' . h($popupId . '-place-city-options') . '"';
                                    $inputAttrs[] = 'data-place-location-target="city"';
                                    $inputAttrs[] = 'data-action="input->place-location#onCityInput blur->place-location#onCityBlur"';
                                }

                                if ($hasPlaceLocationBehavior && $name === 'place_state') {
                                    $inputAttrs[] = 'list="' . h($popupId . '-place-state-options') . '"';
                                    $inputAttrs[] = 'data-place-location-target="state"';
                                    $inputAttrs[] = 'data-action="input->place-location#onStateInput blur->place-location#onStateBlur"';
                                }
                                ?>
                            <input
                                type="<?= h($type) ?>"
                                class="<?= h($inputClass) ?>"
                                id="<?= h($popupId . '-' . $name) ?>"
                                name="<?= h($name) ?>"
                                <?= $req ? 'required' : '' ?>
                                <?= implode(' ', $inputAttrs) ?>
                                <?= $fieldAttributes ?>
                                <?= $previewAttrs ?> />

                                <?php if ($hasPlaceLocationBehavior && $name === 'place_city') : ?>
                            <datalist id="<?= h($popupId) ?>-place-city-options" data-place-location-target="cityList"></datalist>
                                <?php endif; ?>

                                <?php if ($hasPlaceLocationBehavior && $name === 'place_state') : ?>
                            <datalist id="<?= h($popupId) ?>-place-state-options" data-place-location-target="stateList"></datalist>
                                <?php endif; ?>

                            <?php endif; ?>
                            <?php if (is_string($help) && $help !== '') : ?>
                                <div class="form-text"><?= h($help) ?></div>
                            <?php endif; ?>
                    </div>
                        <?php endif; ?>

                    <?php endforeach; ?>

                    <?php if ($teamNamePreview) : ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Display Previews</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Full Team Name:</strong><br>
                                <span data-team-name-preview-target="fullTeamName">Unknown Team</span>
                            </p>
                            <p><strong>Team Name (default):</strong><br>
                                <span data-team-name-preview-target="defaultTeamName">Unknown Team</span>
                            </p>
                            <p class="mb-0"><strong>Team:</strong><br>
                                <span data-team-name-preview-target="team">Unknown Team</span>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($opponentNamePreview) : ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Display Previews</h6>
                        </div>
                        <div class="card-body">
                            <p><strong>Full Opponent Name:</strong><br>
                                <span data-opponent-name-preview-target="fullOpponentName">Unknown Opponent</span>
                            </p>
                            <p><strong>Opponent Name (default):</strong><br>
                                <span data-opponent-name-preview-target="defaultOpponentName">Unknown Opponent</span>
                            </p>
                            <p class="mb-0"><strong>Opponent:</strong><br>
                                <span data-opponent-name-preview-target="opponent">Unknown Opponent</span>
                            </p>
                            <small class="text-muted">
                                Existing records are not rewritten automatically. Update the short name and mascot as needed.
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasPlaceLocationBehavior) : ?>
                        <small class="text-muted d-block mt-1" data-place-location-target="locationMeta">Select a country to load subdivisions and localities.</small>
                    <?php endif; ?>

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

    function cleanupModalArtifacts() {
        if (document.querySelector('.modal.show')) {
            return;
        }

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
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
                    setTimeout(cleanupModalArtifacts, 350);
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
        setTimeout(cleanupModalArtifacts, 0);
    });
})();
</script>
