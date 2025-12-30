Image Storage Guide

This document explains how the image uploading stack works: the storage/persistence layer (`ImageStorageService`), the tagging helpers (`TaggingService`), and the processing utilities (`ImageProcessor`). It also summarizes configuration knobs and test scaffolding that keep this pipeline reliable.

## 1. High-level flow

1. `ImagesController::persistNewImage` (and similar upload actions) accept a PSR-7 `UploadedFileInterface` and hand it to `ImageStorageService::upload()` along with any user-supplied tags/manipulations.
2. `ImageStorageService` validates the upload, delegates pixel work to `ImageProcessor`, deduplicates by hash, writes the original and variant files, writes the `images` record, and finally hands off tag persistence to `TaggingService`.
3. Uploaded files are immediately accessible through `ImageStorageService::resolveImagePath()` or the existing `ImagesController::serve()` action (which can wrap the raw bytes with ACL/caching logic).
4. Tags flow through `TaggingService::attachTags()`/`replaceTags()`; the service builds canonical slugs, enriches friendly names via dependent services (person/team/roster), and links them via the `ImageTags` HABTM table.
5. `ImageProcessor` powers both freshly uploaded variants and the manipulation APIs (`manipulateExisting()`), so the `images` row always points to consistent metadata for the original and configured variants.

## 2. Storage layout and persistence

- The root storage path defaults to `WWW_ROOT/img/storage/` but can be overridden via `Configure::write('Images.storageRoot', '/custom/path/')`. The legacy path is still `ROOT/storage/images/` (configurable via `Images.legacyStorageRoot`).
- Images are grouped by year/month (`YYYY/MM`) and saved with UUID filenames (`{uuid}.{ext}`), while variants carry the pattern `{uuid}-{variant}.{ext}`. The database row stores `storage_subdir`, `storage_path`, `variants` JSON, `hash`, and core metadata (dimensions, mime, byte size).
- `ImageStorageService::persistNewImage()` ensures the directory exists (`createStorageDir()`), writes the files (`writeImageFiles()`), and bails out if any write fails while reporting `lastError`.
- Duplicates are detected by hashing the processed original bytes (`sha256`). If a hash match exists, no writes occur; the existing image ID is returned and tags are attached to it.
- On retrieval, `resolveImagePath()` first checks the configured `storage_path`, falls back to the `storage_subdir` grouping, and finally falls back to the legacy storage area when the file is missing.

## 3. Upload validation and tagging hooks

- `validateUpload()` enforces:
	- `UPLOAD_ERR_OK` status and non-empty payload
	- MIME type restricted to `image/jpeg`, `image/png`, `image/gif`, `image/webp`
	- A `getimagesize()` sanity check (wrapped with a temporary error handler) to block corrupt binaries
- `tags` can be raw strings, slug/name arrays, or entities pulled from `context`/select inputs.
- `applyFromData()` consumes controller form payloads and builds record-based tags first (person, teamseason, roster, game, site, opponent, team, sport) using the respective service layers for friendly labels.
- Roster uploads take priority, attaching both roster and person tags while suppressing unrelated entity tags to keep the tag set coherent.
- Freeform tag strings are deduplicated and never override existing friendly labels.
- `parseTagsFromRequest()` mirrors the controller behavior, supporting both `tags` arrays/comma lists and JSON `context` descriptors so API clients can send structured metadata.

## 4. Image processing details

- `ImageProcessor` relies on the `Intervention/Image` manager but gracefully degrades if the library is unavailable (falling back to native GD helpers). The optional manager may be injected for tests/mocks.
- The `variants` configuration lives in `Configure::read('Images.variants', [])` and is set centrally in `Application::bootstrap()`.
- Current defaults include:
	- `thumb` (150×150 cover) encoded as WebP
	- `medium` (maxWidth 800) encoded as WebP
	- `webp` (alternate WebP encoding of the original)
- Each variant config can include:
	- `fit` `[width, height]` → uses `cover()` to crop+fill
	- `maxWidth` → scales down (maintaining aspect ratio)
	- `format` (e.g., `webp`) → controls encoding + mime
	- `crop` coordinates for explicit cropping prior to resizing
- Manipulations (e.g., `rotate`, `crop`, `flip`) may be passed via the `$manipulations` array and are applied before variant generation by `applyManipulations()`.
- The processor returns raw binary data, dimensions, MIME, and inferred extension for both the original and each variant; `ImageStorageService` uses those outputs to build metadata and write files.
- Post-upload editing (e.g., re-cropping) uses `manipulateExisting()` with the saved file content, re-running the variant logic so `TaggingService` or other callers can regenerate downstream assets.

## 5. Tag lifecycle and maintenance

- `attachTags()` / `replaceTags()` normalize tag slugs using `Text::slug()` when only names are supplied, ensuring consistent `slug` + `name` pairs in the database.
- Popular tag patterns are `person-{id}`, `teamseason-{id}`, `team-{id}`, `game-{id}`, `site-{id}`, `opponent-{id}`, `sport-{id}`, and `team_season_roster-{rosterId}`.
- Generic names (eg. `person 1`, `teamseason 1`) are automatically upgraded when a nicer display label becomes available.
- `pruneOrphanedTags()` can be run (usually post-replacement) to delete tags no longer linked to any image.
- Tagging operations are idempotent; existing links are skipped and only new tag associations are created via CakePHP’s association `link()` call.

## 6. Configuration and runtime knobs

| Config key | Purpose | Default |
|------------|---------|---------|
| `Images.storageRoot` | Public storage root (must end with `/`). | `WWW_ROOT/img/storage/` |
| `Images.legacyStorageRoot` | Legacy private storage root for backwards lookup. | `ROOT/storage/images/` |
| `Images.variants` | Variant map passed to `ImageProcessor::process()` (see Section 4). | `['thumb' => ['fit' => [150, 150], 'format' => 'webp'], 'medium' => ['maxWidth' => 800, 'format' => 'webp'], 'webp' => ['format' => 'webp']]` |
| `Images.manipulations` | Optional manipulations applied during upload. | empty array |

- `ImageProcessor` will fall back to native GD functions if the Intervention driver cannot be instantiated, so deployments without the PHP extension still work albeit with reduced capabilities.
- Logging and error propagation rely on `ImageStorageService::getLastError()` when uploads fail; controllers should surface that message to clients.

## 7. Developer tooling & testing

- Tests targeting this pipeline live in `tests/TestCase/Service/ImageStorageServiceTest.php`, which sets up temporary directories via `Configure::read('Images.storageRoot')` and wipes them using `clearDir()`.
- The tests mock uploads using PSR-7 `UploadedFileInterface` and confirm that:
	- Validation rejects empty/unsupported files
	- Hash dedupe works
	- Variants are written with the expected metadata
	- Tagging cooperates with the service layer
- To preview storage content, drop files under `webroot/img/storage/{YYYY}/{MM}`; the database still resolves them via `resolveImagePath()`.
- When changing variant logic, update the `Images.variants` config plus any dependent UI that displays variant URLs (thumb vs medium vs original).

## 8. Operational reminders

- Because files sit under `webroot`, ensure your web server sets appropriate caching headers (`Cache-Control`, `ETag`) if you rely on `direct_url` responses.
- For sensitive uploads, continue to validate MIME/types before saving; the CSV of allowed formats is centralized in `ImageStorageService::validateUpload()`.
- When deploying a new variant or format, reprocess existing assets if backwards compatibility is required (e.g., regenerate WebP variants via a CLI script that calls `ImageProcessor::manipulateExisting()`).
