/* Admin shared JS: confirm delete modal + lightweight toast */
(function () {
    const modalId = 'confirm-delete-modal';
    const modal = document.getElementById(modalId);
    if (modal) {
        const assocList = modal.querySelector('#' + modalId + '-assoc');
        const hiddenForm = modal.querySelector('#' + modalId + '-hidden-form');
        let context = {};
        function populate(associated) {
            if (!assocList) return; assocList.innerHTML = '';
            if (!associated) return;
            try { JSON.parse(associated).forEach(row => { const li = document.createElement('li'); li.textContent = typeof row === 'string' ? row : (row.label || row.name || JSON.stringify(row)); assocList.appendChild(li); }); } catch (e) { }
        }
        window.showConfirmDelete = function (opts) {
            context = opts || {}; populate(context.associated);
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            } else { modal.style.display = 'block'; }
        };
        document.addEventListener('click', e => {
            const t = e.target.closest('[data-bs-target="#' + modalId + '"][data-delete-url]'); if (!t) return; e.preventDefault(); window.showConfirmDelete({
                deleteUrl: t.dataset.deleteUrl,
                associated: t.dataset.associated,
                ids: t.dataset.ids,
                idsName: t.dataset.idsName,
                formId: t.dataset.formId,
                bulkAction: t.dataset.bulkAction
            });
        });
        const delBtn = modal.querySelector('#' + modalId + '-delete-btn');
        if (delBtn) {
            delBtn.addEventListener('click', () => {
                const targetForm = context.formId ? document.getElementById(context.formId) : hiddenForm;
                if (!targetForm) return;
                targetForm.action = context.deleteUrl || '#';
                targetForm.querySelectorAll('.injected-delete').forEach(n => n.remove());
                if (context.ids && context.idsName) { try { JSON.parse(context.ids).forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = context.idsName; i.value = id; i.className = 'injected-delete'; targetForm.appendChild(i); }); } catch (e) { } }
                if (context.bulkAction) { const b = document.createElement('input'); b.type = 'hidden'; b.name = 'bulk_action'; b.value = context.bulkAction; b.className = 'injected-delete'; targetForm.appendChild(b); }
                targetForm.submit();
            });
        }
    }
    function toast(msg, type) { const n = document.createElement('div'); n.className = 'alert alert-' + (type || 'info') + ' position-fixed top-0 end-0 m-3 shadow'; n.textContent = msg; document.body.appendChild(n); setTimeout(() => n.remove(), 4000); }
    window.AdminToast = toast;
    if (typeof module !== 'undefined') module.exports = { showConfirmDelete: window.showConfirmDelete, AdminToast: window.AdminToast };
})();
