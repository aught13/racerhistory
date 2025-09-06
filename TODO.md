# TODO (Optional Next Steps)

Date: 2025-08-17

These are optional follow-up tasks focusing on the image management feature and related UI/editor integration. None are required for current green build but will expand capability.

## 1. ImagesController Tests

- Add fixtures: `ImagesFixture` (sample image rows + variants JSON) and optionally `ImageUsagesFixture`.
- Create `tests/TestCase/Controller/Admin/ImagesControllerTest.php` covering:
  - Upload success: valid mime (e.g. JPEG), stores variants, returns JSON with variant metadata.
  - Upload dedupe: second upload of identical file returns existing image (same hash) without new row.
  - Upload failure cases: unsupported mime, oversized file, zero-byte, missing file field.
  - Browse endpoint: returns paginated list (assert structure, ordering, filtering if added).
  - Assert DB rows (images + variants not empty) and HTTP status codes.
  - Use `$this->enableCsrfToken(); $this->enableSecurityToken();` for POST.
- Edge cases: duplicate filename different content (new hash), very large dimensions (ensure resizing not crashing), truncated/invalid image.

  Status: initial fixtures and a basic `ImagesControllerTest` have been added to the codebase to cover upload and browse happy paths. Remaining work:

  - Expand tests to cover dedupe behavior (identical hash), oversized files, zero-byte files, and malformed image uploads.
  - Add assertions for variants JSON structure and database row counts.

## 2. Image Picker Integration (Persons & TeamSeasons Forms)

- Create element `templates/element/Admin/image_picker.php`:
  - Hidden `image_id` input, preview container, Select/Change button, optional Remove button.
  - Accessible modal (Bootstrap) or offcanvas to list/browse images via AJAX.
- Add JS module to open picker, fetch `/admin/images/browse` (JSON), render grid, allow selection.
- Update Persons add/edit and TeamSeasons add/edit templates to include picker.
- Persistence approaches (choose one):
  1. Add nullable `image_id` FK column to each table (simple belongsTo association).
  2. Use polymorphic `image_usages` (already scaffolded) and resolve primary image via finder.
- Update table classes (`PersonsTable`, `TeamSeasonsTable`) for chosen association.
- After save (if using usages), insert usage row (`model`, `foreign_key`, `field`).
  - Accessibility: aria-label on buttons, focus trap inside modal, keyboard navigation for thumbnails.

  Status: basic person image element (`person_image.php`) and JS exist. TeamSeasons now has parallel lightweight handling (`team_season_image.php`) with direct upload + numeric ID field and preview (mirrors Persons pattern). Remaining: shared reusable picker component, accessibility improvements (focus trap, ARIA roles), modal keyboard support, and server-side persistence of usage (or FK migration) during create/edit flows.

## 3. TinyMCE Integration & Image Upload

- (DONE) Replace CKEditor with self-hosted TinyMCE including plugins: image, code, lists, liststyles, media, preview, quickbars, save, visualblocks, visualchars.
- Serve TinyMCE assets from `/js/tinymce/` (copy from `vendor/tinymce/tinymce` into webroot on deploy/build step or symlink for dev).
- Ensure upload flow uses `images_upload_handler` hitting `/admin/images/upload` (already implemented) and inserts returned `image.url`.
- Add integration test simulating TinyMCE image upload via XMLHttpRequest (POST file, assert JSON schema, ensure 200 and success true).
  - Consider adding custom toolbar buttons for variant selection (e.g., Insert thumb vs original) using variant metadata.

  Status: TinyMCE assets referenced under `webroot/js/tinymce/`. Implemented on Persons (bio) and TeamSeasons (preview & recap) with working image upload handler. Remaining: add integration test (XHR simulation) and optional toolbar customization / variant insertion buttons.

## 4. ImageUsages Logic Enhancements

- Extend `upload()` to accept optional context params (`model`, `foreign_key`, `field`) and auto-create usage when provided.
- For new entity forms (no ID yet), defer usage creation until after entity save (maybe separate endpoint or JS hook after save redirect not ideal—prefer FK column pattern if simplicity desired).
- Add `ImageUsagesTable` validation tests (unique constraint concept: one usage per (model, foreign_key, field) if appropriate).

## 5. Schema Adjustments (If Using FK Columns)

- Migration: add nullable `image_id` (int) to `persons` and `team_seasons`.
- Add indexes + foreign key constraints (if supported) referencing `images(id)`.
- Update fixtures: include `image_id` for sample rows where relevant.

## 6. Frontend Enhancements

- Lazy/infinite scroll or pagination controls in picker.
- Display variant metadata (dimensions, size) on hover or in a details pane.
- Client-side validation: mime/size check before upload.
- Loading skeletons / spinners for image grid.

## 7. Additional Tests Beyond Controller

- `ImagesTableTest`: validation (mime, hash uniqueness), rules (hash unique), beforeSave (variants JSON format).
- `ImageProcessorTest`: generate an in-memory image (e.g. small PNG) and assert returned variants and dimension constraints.
- Integration test: selecting an image and saving a Person persists association and renders preview on view page.

## 8. Documentation

- Add `docs/IMAGES.md` explaining upload flow, variants, picker integration, and TinyMCE configuration.
- Update README quick start section referencing new image capabilities.

## 9. Nice-to-Haves (Later)

- Cropper.js integration: new endpoint `/admin/images/crop` to create/update variants.
- Soft delete support using existing `status` column (set `archived` + filter in default finder).
- Background queue (e.g., separate command) for heavy transformations (webp, retina sizes).
- Usage count caching: add `usage_count` column and maintain via afterSave hooks or scheduled job.
- Automatic orientation correction (EXIF) in `ImageProcessor`.

## Acceptance Criteria Summary

- Upload & browse endpoints fully tested (success + failure).
- Image selection UX integrated into Person & TeamSeason workflows.
- TinyMCE can insert uploaded images seamlessly.
- No regression: all existing 182 tests remain green; new tests increase assertion count.

## Ordering Recommendation

1. Controller & service unit tests (safety net)
2. Schema (FK columns or finalize usage model approach)
3. Picker UI + association wiring
4. TinyMCE upload integration tests & docs
5. Extended usages logic & docs
6. Nice-to-haves (iterative)

---
Generated as a planning artifact; adjust scope based on priority and available time.
