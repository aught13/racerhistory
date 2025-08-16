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
?>
<div class="modal fade" id="<?= h($popupId) ?>" tabindex="-1" aria-hidden="true">
 <div class="modal-dialog">
    <div class="modal-content">
     <div class="modal-header"><h5 class="modal-title"><?= h($title) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
     <div class="modal-body">
        <div id="<?= h($popupId) ?>-alerts"></div>
        <form id="<?= h($popupId) ?>-form" data-url="<?= h($formUrl) ?>" data-target-select="<?= h($targetSelectId) ?>" data-success-callback="<?= h($successCallback) ?>">
         <?php foreach($fields as $f): $name=$f['name']??''; if(!$name) continue; $type=$f['type']??'text'; $label=$f['label']??ucfirst($name); $req=!empty($f['required']); ?>
            <div class="mb-3">
             <label class="form-label" for="<?= h($popupId.'-'.$name) ?>"><?= h($label) ?><?= $req? ' <span class=\'text-danger\'>*</span>':'' ?></label>
             <?php if($type==='textarea'): ?>
                 <textarea class="form-control" id="<?= h($popupId.'-'.$name) ?>" name="<?= h($name) ?>" <?= $req?'required':'' ?>></textarea>
             <?php elseif($type==='select'): ?>
                 <select class="form-select" id="<?= h($popupId.'-'.$name) ?>" name="<?= h($name) ?>" <?= $req?'required':'' ?>>
                     <option value="">Select...</option>
                     <?php foreach(($f['options']??[]) as $val=>$text): ?>
                         <option value="<?= h($val) ?>"><?= h($text) ?></option>
                     <?php endforeach; ?>
                 </select>
             <?php else: ?>
                 <input type="<?= h($type) ?>" class="form-control" id="<?= h($popupId.'-'.$name) ?>" name="<?= h($name) ?>" <?= $req?'required':'' ?> />
             <?php endif; ?>
            </div>
         <?php endforeach; ?>
        </form>
     </div>
     <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="<?= h($popupId) ?>-submit">Save</button></div>
    </div>
 </div>
</div>
<script>
(function(){
 const id='<?= $popupId ?>';
 const modal=document.getElementById(id); if(!modal) return;
 const form=modal.querySelector('#'+id+'-form');
 const alerts=modal.querySelector('#'+id+'-alerts');
 const submitBtn=modal.querySelector('#'+id+'-submit');
 function showErrors(errs){alerts.innerHTML='<div class="alert alert-danger"><ul class="mb-0">'+errs.map(e=>'<li>'+e+'</li>').join('')+'</ul></div>';}
 function toast(msg,type){const n=document.createElement('div');n.className='alert alert-'+(type||'info')+' position-fixed top-0 end-0 m-3';n.textContent=msg;document.body.appendChild(n);setTimeout(()=>n.remove(),4000);}
 submitBtn.addEventListener('click',()=>{
     alerts.innerHTML='';
     submitBtn.disabled=true; submitBtn.textContent='Saving...';
     const fd=new FormData(form);
     const csrf=document.querySelector('meta[name="csrfToken"]'); if(csrf) fd.append('_csrfToken', csrf.getAttribute('content'));
     // Copy FormProtection tokens if hidden form present (by convention hidden-sport-form)
     const hidden=document.getElementById('hidden-sport-form'); if(hidden){hidden.querySelectorAll('input[name^="_Token"]').forEach(i=>fd.append(i.name,i.value));}
     fetch(form.dataset.url,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r=>r.json().catch(()=>({success:false,errors:['Invalid JSON response']})))
        .then(data=>{
             if(data.success){
                 if(form.dataset.targetSelect && data.newOption){const sel=document.getElementById(form.dataset.targetSelect); if(sel){const o=new Option(data.newOption.text,data.newOption.value,true,true); sel.add(o);}}
                 if(form.dataset.successCallback && typeof window[form.dataset.successCallback]==='function'){window[form.dataset.successCallback](data);}
                 bootstrap.Modal.getOrCreateInstance(modal).hide();
                 toast(data.message||'Saved','success');
                 form.reset();
             } else { showErrors(data.errors||['Unable to save']); }
        })
        .catch(()=>showErrors(['Network error']))
        .finally(()=>{submitBtn.disabled=false; submitBtn.textContent='Save';});
 });
 modal.addEventListener('hidden.bs.modal',()=>{alerts.innerHTML='';});
})();
</script>
