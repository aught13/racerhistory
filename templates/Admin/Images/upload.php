<?php
/**
 * @var \App\View\AppView $this
 */
?>

<div class="container py-4">
    <h1 class="mb-4">Upload Image</h1>

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Select & Upload Image</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="imageFile" class="form-label">Image File</label>
                            <input type="file" class="form-control" id="imageFile" name="file" accept="image/*" required>
                            <small class="form-text text-muted">Supported: JPG, PNG, GIF, WebP (max 50MB)</small>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags (optional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="person-123, teamseason-456, roster">
                            <small class="form-text text-muted">Comma-separated tag names or slugs</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <span id="uploadBtn">Upload Image</span>
                            <span id="uploadSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>

                    <div id="uploadStatus" class="mt-3 d-none"></div>
                </div>
            </div>
        </div>

        <!-- Preview & Basic Manipulations -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Basic Upload Adjustments (Optional)</h5>
                </div>
                <div class="card-body">
                    <div id="previewContainer" class="mb-3 d-none">
                        <img id="previewImage" src="" alt="Preview" class="img-fluid rounded" style="max-height: 300px; display: block; margin: 0 auto;">
                        <small class="form-text text-muted d-block mt-2">Selected image preview</small>
                    </div>

                    <div id="manipulationControls" class="d-none">
                        <!-- Rotate Quick Buttons -->
                        <div class="mb-3">
                            <label class="form-label">Rotation</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(0)">None</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(90)">90°</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(180)">180°</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setRotation(270)">270°</button>
                            </div>
                            <input type="hidden" id="rotate" name="rotate" value="0">
                        </div>

                        <!-- Brightness -->
                        <div class="mb-3">
                            <label for="brightness" class="form-label">
                                Brightness <span class="badge bg-secondary" id="brightnessBadge">0</span>
                            </label>
                            <input type="range" class="form-range" id="brightness" name="brightness" min="-100" max="100" value="0">
                            <small class="form-text text-muted">-100 (darker) to 100 (brighter)</small>
                        </div>

                        <!-- Contrast -->
                        <div class="mb-3">
                            <label for="contrast" class="form-label">
                                Contrast <span class="badge bg-secondary" id="contrastBadge">0</span>
                            </label>
                            <input type="range" class="form-range" id="contrast" name="contrast" min="-100" max="100" value="0">
                            <small class="form-text text-muted">-100 (less) to 100 (more)</small>
                        </div>

                        <div class="alert alert-info small mt-3">
                            <strong>Note:</strong> These basic adjustments are applied during upload. For more advanced crop/filters, upload first, then use <strong>Manipulate Image</strong> from the edit view.
                        </div>
                    </div>

                    <p id="noFileText" class="text-muted small mb-0">Select an image above to see adjustment options</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <?= $this->Html->link(
                '← Back to Images',
                ['action' => 'index'],
                ['class' => 'btn btn-outline-secondary']
            ) ?>
        </div>
    </div>
</div>

<script>
let currentImageFile = null;

// File input change handler
document.getElementById('imageFile').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
        document.getElementById('previewContainer').classList.add('d-none');
        document.getElementById('manipulationControls').classList.add('d-none');
        document.getElementById('noFileText').classList.remove('d-none');
        return;
    }

    currentImageFile = file;

    // Show preview
    const reader = new FileReader();
    reader.onload = function(event) {
        document.getElementById('previewImage').src = event.target.result;
        document.getElementById('previewContainer').classList.remove('d-none');
        document.getElementById('manipulationControls').classList.remove('d-none');
        document.getElementById('noFileText').classList.add('d-none');
    };
    reader.readAsDataURL(file);
});

// Update brightness badge
document.getElementById('brightness')?.addEventListener('input', function() {
    document.getElementById('brightnessBadge').textContent = this.value;
    document.getElementById('brightnessBadge').className = 'badge ' +
        (this.value > 0 ? 'bg-success' : this.value < 0 ? 'bg-danger' : 'bg-secondary');
});

// Update contrast badge
document.getElementById('contrast')?.addEventListener('input', function() {
    document.getElementById('contrastBadge').textContent = this.value;
    document.getElementById('contrastBadge').className = 'badge ' +
        (this.value > 0 ? 'bg-success' : this.value < 0 ? 'bg-danger' : 'bg-secondary');
});

// Set rotation value
function setRotation(degrees) {
    document.getElementById('rotate').value = degrees;
}

// Upload form submit
document.getElementById('uploadForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!currentImageFile) {
        showStatus('error', 'Please select an image file');
        return;
    }

    const formData = new FormData();
    formData.append('file', currentImageFile);

    const tags = document.getElementById('tags').value;
    if (tags) {
        formData.append('tags', tags);
    }

    // Add manipulations if any
    const rotate = parseInt(document.getElementById('rotate').value);
    if (rotate > 0) {
        formData.append('rotate', rotate);
    }

    const brightness = parseInt(document.getElementById('brightness').value);
    if (brightness !== 0) {
        formData.append('brightness', brightness);
    }

    const contrast = parseInt(document.getElementById('contrast').value);
    if (contrast !== 0) {
        formData.append('contrast', contrast);
    }

    try {
        document.getElementById('uploadBtn').classList.add('d-none');
        document.getElementById('uploadSpinner').classList.remove('d-none');

        const response = await fetch('/admin/images/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showStatus('success', 'Image uploaded successfully! Redirecting...');
            setTimeout(() => {
                window.location.href = '/admin/images/edit/' + data.image.id;
            }, 1500);
        } else {
            showStatus('error', data.error || 'Upload failed');
            document.getElementById('uploadBtn').classList.remove('d-none');
            document.getElementById('uploadSpinner').classList.add('d-none');
        }
    } catch (err) {
        showStatus('error', 'Upload error: ' + err.message);
        document.getElementById('uploadBtn').classList.remove('d-none');
        document.getElementById('uploadSpinner').classList.add('d-none');
    }
});

// Show status message
function showStatus(type, message) {
    const statusEl = document.getElementById('uploadStatus');
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    statusEl.innerHTML = `<div class="alert ${alertClass}" role="alert">${message}</div>`;
    statusEl.classList.remove('d-none');
}
</script>
