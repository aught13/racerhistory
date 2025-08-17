(function () {
    const MODAL_ID = 'confirm-delete-modal';

    function findModal() {
        return document.getElementById(MODAL_ID);
    }

    function renderAssociated(modal, associated) {
        if (!modal) return;
        const assocList = modal.querySelector('#' + MODAL_ID + '-assoc');
        if (!assocList) return;
        assocList.innerHTML = '';
        if (!associated) return;
        let list = [];
        if (typeof associated === 'string') {
            try { list = JSON.parse(associated); } catch (e) { list = [associated]; }
        } else if (Array.isArray(associated)) {
            list = associated;
        } else if (associated) {
            list = [associated];
        }
        list.forEach(row => {
            const li = document.createElement('li');
            li.textContent = (typeof row === 'string') ? row : (row.label || row.name || JSON.stringify(row));
            assocList.appendChild(li);
        });
    }

    // Current context for modal operations (populated from triggers)
    let context = {};

    function setContext(opts) {
        context = opts || {};
        try { console.log('confirm-delete setContext', context); } catch (e) { }
        renderAssociated(findModal(), context.associated);
    }

    // Public helper to open modal programmatically with context
    window.showConfirmDelete = function (opts) {
        setContext(opts);
        const modal = findModal();
        if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modal).show();
        } else if (modal) {
            modal.style.display = 'block';
        } else {
            console.log('showConfirmDelete called but modal not present');
        }
    };

    function submitTempForm(action, tokensSource, extraFields) {
        const temp = document.createElement('form');
        temp.style.display = 'none';
        temp.method = 'post';
        temp.action = action || '#';

        // copy hidden inputs (tokens and any existing hidden fields)
        if (tokensSource) {
            tokensSource.querySelectorAll('input[type="hidden"]').forEach(i => {
                const ni = document.createElement('input'); ni.type = 'hidden'; ni.name = i.name; ni.value = i.value || ''; temp.appendChild(ni);
            });
        }

        // extraFields: array of {name, value}
        if (Array.isArray(extraFields)) {
            extraFields.forEach(f => {
                const ni = document.createElement('input'); ni.type = 'hidden'; ni.name = f.name; ni.value = f.value; temp.appendChild(ni);
            });
        }

        document.body.appendChild(temp);
        try { console.log('confirm-delete submitting temp form', { action: temp.action, inputs: temp.querySelectorAll('input').length }); } catch (e) { }
        try { if (typeof temp.requestSubmit === 'function') temp.requestSubmit(); else temp.submit(); } catch (e) { console.log('error submitting temp form', e); }
    }

    function onDomReady(fn) {
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
        else fn();
    }

    onDomReady(function () {
        console.log('admin.js initialized, modalPresent=', !!findModal());

        // Modal show event: when Bootstrap opens modal via data-bs-toggle, relatedTarget is available
        const modal = findModal();
        if (modal) {
            modal.addEventListener('show.bs.modal', function (ev) {
                try {
                    const trigger = ev && ev.relatedTarget;
                    console.log('confirm-delete modal show event, relatedTarget=', trigger);
                    if (trigger) {
                        setContext({
                            deleteUrl: trigger.dataset.deleteUrl,
                            associated: trigger.dataset.associated,
                            ids: trigger.dataset.ids,
                            idsName: trigger.dataset.idsName,
                            formId: trigger.dataset.formId,
                            bulkAction: trigger.dataset.bulkAction
                        });
                    }
                } catch (e) { console.log('error in show.bs.modal handler', e); }
            });
        }

        // Delegated click handler: picks up triggers created dynamically (e.g., bulk temporary trigger)
        document.addEventListener('click', function (e) {
            const t = e.target.closest('[data-bs-target="#' + MODAL_ID + '"][data-delete-url]');
            if (t) {
                console.log('confirm-delete trigger clicked', t);
                setContext({
                    deleteUrl: t.dataset.deleteUrl,
                    associated: t.dataset.deleteAssociated || t.dataset.associated,
                    ids: t.dataset.ids,
                    idsName: t.dataset.idsName,
                    formId: t.dataset.formId,
                    bulkAction: t.dataset.bulkAction
                });
                return; // let bootstrap open modal automatically
            }

            // Delete button inside modal (use event delegation so the element may not exist at script load)
            const delBtn = e.target.closest('#' + MODAL_ID + '-delete-btn');
            if (delBtn) {
                // Determine token source: prefer referenced formId, else modal hidden form
                let source = null;
                try { source = (context && context.formId) ? document.getElementById(context.formId) : (findModal() ? findModal().querySelector('#' + MODAL_ID + '-hidden-form') : null); } catch (e) { source = null; }

                // Build extra fields
                const extra = [];
                if (context.ids && context.idsName) {
                    let idsArr = [];
                    if (typeof context.ids === 'string') {
                        try { idsArr = JSON.parse(context.ids); } catch (e) { idsArr = [context.ids]; }
                    } else if (Array.isArray(context.ids)) idsArr = context.ids; else if (context.ids) idsArr = [context.ids];
                    idsArr.forEach(id => extra.push({ name: context.idsName, value: id }));
                }
                if (context.bulkAction) extra.push({ name: 'bulk_action', value: context.bulkAction });

                // If a source form exists, prefer injecting into and submitting that form so tests
                // and server-side FormProtection tokens align. Otherwise fallback to a temporary form.
                let postAction = context.deleteUrl;
                try {
                    if (source && source.action) postAction = source.action;
                } catch (e) { }
                console.log('confirm-delete final post action:', postAction, 'source form id=', source && source.id);

                if (source) {
                    try {
                        // cleanup previous injected inputs
                        source.querySelectorAll('.injected-delete').forEach(n => n.remove());

                        // ensure form posts to the expected action (use postAction computed above)
                        source.action = postAction;

                        // add extra hidden fields to the source form
                        if (Array.isArray(extra)) {
                            extra.forEach(f => {
                                const ni = document.createElement('input'); ni.type = 'hidden'; ni.name = f.name; ni.value = f.value; ni.className = 'injected-delete'; source.appendChild(ni);
                            });
                        }

                        // submit the source form
                        try { if (typeof source.requestSubmit === 'function') source.requestSubmit(); else source.submit(); } catch (e) { console.log('error submitting source form', e); }
                    } catch (e) { console.log('error preparing source form submit', e); }
                } else {
                    submitTempForm(postAction, source, extra);
                }
            }
        });
    });

    // Toast helper
    function toast(msg, type) { const n = document.createElement('div'); n.className = 'alert alert-' + (type || 'info') + ' position-fixed top-0 end-0 m-3 shadow'; n.textContent = msg; document.body.appendChild(n); setTimeout(() => n.remove(), 4000); }
    window.AdminToast = toast;

    if (typeof module !== 'undefined') module.exports = { showConfirmDelete: window.showConfirmDelete, AdminToast: window.AdminToast };
})();
