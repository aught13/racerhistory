/* image-selector.branches.test.js
 * Focused tests for webroot/js/image-selector.js
 */
// ...existing code...

beforeEach(() => {
  jest.resetModules();
  document.body.innerHTML = '';
  global.fetch = undefined;
  global.alert = jest.fn();
  // minimal bootstrap stub
  global.bootstrap = { Modal: { getInstance: () => ({ hide: jest.fn() }) } };
});

test('constructor handles missing modal gracefully', () => {
  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('no-such-modal');
  expect(inst.modal).toBeFalsy();
});

test('loadImages renders gallery on success and handles failure', async () => {
  // create modal and gallery
  const modal = document.createElement('div');
  modal.id = 'test-image-selector';
  const gallery = document.createElement('div');
  gallery.id = 'test-image-selector-gallery';
  modal.appendChild(gallery);
  document.body.appendChild(modal);

  // config and target field
  window.imageSelectorConfig = { 'test-image-selector': { targetFieldId: 'target' } };
  const target = document.createElement('input');
  target.id = 'target';
  document.body.appendChild(target);

  // mock fetch success
  global.fetch = jest.fn().mockResolvedValue({ ok: true, json: async () => ({ images: [{ id: 5, url: '/img5.jpg' }] }) });

  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('test-image-selector');
  await inst.loadImages();
  expect(gallery.innerHTML).toMatch(/image-card/);

  // mock fetch failure
  global.fetch = jest.fn().mockResolvedValue({ ok: false });
  await inst.loadImages();
  expect(gallery.innerHTML).toMatch(/Failed to load images/);
});

test('onSearch filters rendered images', async () => {
  const modal = document.createElement('div');
  modal.id = 'test-image-selector-2';
  const gallery = document.createElement('div');
  gallery.id = 'test-image-selector-2-gallery';
  modal.appendChild(gallery);
  document.body.appendChild(modal);

  window.imageSelectorConfig = { 'test-image-selector-2': {} };
  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('test-image-selector-2');

  inst.loadedImages = [
    { id: 1, original_name: 'cat.jpg', tags: ['pet'] },
    { id: 2, original_name: 'dog.jpg', tags: ['pet'] },
    { id: 3, original_name: 'car.jpg', tags: ['vehicle'] },
  ];
  inst.renderGallery(inst.loadedImages);
  expect(gallery.innerHTML).toMatch(/#1/);
  inst.onSearch('car');
  expect(gallery.innerHTML).toMatch(/#3/);
  expect(gallery.innerHTML).not.toMatch(/#1/);
});

test('onGalleryImageClick selects image and toggles classes', () => {
  const modal = document.createElement('div');
  modal.id = 'test-image-selector-3';
  const gallery = document.createElement('div');
  gallery.id = 'test-image-selector-3-gallery';
  const card = document.createElement('div');
  card.dataset.imageId = '77';
  card.className = 'image-card';
  gallery.appendChild(card);
  document.body.appendChild(modal);
  document.body.appendChild(gallery);

  window.imageSelectorConfig = { 'test-image-selector-3': {} };
  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('test-image-selector-3');
  inst.gallery = gallery; // ensure reference
  inst.onGalleryImageClick(card);
  expect(inst.selectedImageId).toBe(77);
  expect(card.classList.contains('border-primary')).toBe(true);
});

test('onSelectImage alerts when none selected and sets target when selected', () => {
  const modal = document.createElement('div');
  modal.id = 'test-image-selector-4';
  document.body.appendChild(modal);
  const gallery = document.createElement('div');
  gallery.id = 'test-image-selector-4-gallery';
  document.body.appendChild(gallery);
  const target = document.createElement('input');
  target.id = 'target4';
  document.body.appendChild(target);
  window.imageSelectorConfig = { 'test-image-selector-4': { targetFieldId: 'target4' } };

  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('test-image-selector-4');
  // no selection
  inst.selectedImageId = null;
  inst.onSelectImage();
  expect(global.alert).toHaveBeenCalled();

  // with selection
  inst.selectedImageId = 99;
  // mock bootstrap modal instance hide
  const hideMock = jest.fn();
  global.bootstrap = { Modal: { getInstance: () => ({ hide: hideMock }) } };
  inst.onSelectImage();
  expect(document.getElementById('target4').value).toBe('99');
  expect(hideMock).toHaveBeenCalled();
});

test('onUploadImage uses skipCrop path and updates target on success', async () => {
  // setup modal and buttons
  const modal = document.createElement('div');
  modal.id = 'test-image-selector-5';
  document.body.appendChild(modal);
  const gallery = document.createElement('div');
  gallery.id = 'test-image-selector-5-gallery';
  document.body.appendChild(gallery);
  const target = document.createElement('input');
  target.id = 'target5';
  document.body.appendChild(target);
  const uploadBtn = document.createElement('button');
  uploadBtn.id = 'test-image-selector-5-upload-btn';
  document.body.appendChild(uploadBtn);
  const skip = document.createElement('input');
  skip.type = 'checkbox';
  skip.id = 'test-image-selector-5-skip-crop';
  document.body.appendChild(skip);

  window.imageSelectorConfig = { 'test-image-selector-5': { targetFieldId: 'target5' } };
  const ImageSelector = require('../image-selector.js');
  const inst = new ImageSelector('test-image-selector-5');
  inst.uploadBtn = uploadBtn;
  inst.skipCropToggle = skip;
  // set selected file and skipCrop true
  const blob = new Blob(['x'], { type: 'image/jpeg' });
  inst.selectedFile = blob;
  skip.checked = true;

  // mock fetch upload success
  global.fetch = jest.fn().mockResolvedValue({ ok: true, json: async () => ({ success: true, image: { id: 555 } }) });
  // mock csrf meta
  const meta = document.createElement('meta');
  meta.name = 'csrfToken';
  meta.content = 'token';
  document.head.appendChild(meta);

  await inst.onUploadImage();
  expect(document.getElementById('target5').value).toBe('555');
  expect(uploadBtn.disabled).toBe(false);
  expect(uploadBtn.textContent).toBe('Upload & Crop');
});
