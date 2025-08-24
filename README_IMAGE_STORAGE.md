Public Image Storage Change
===========================

Images are now stored under `webroot/img/storage/{YYYY}/{MM}/UUID.ext` instead of the previous non-public `storage/images` directory.

Details:

1. Controller logic (`ImagesController::persistNewImage` and `resolveImagePath`) updated to write/read from `WWW_ROOT . 'img/storage'`.
2. JSON responses now include `direct_url` alongside the existing `url` (serve action) so the client can choose either the protected route or the direct public file path.
3. Existing database rows remain valid; files previously written to the old private path will not be moved automatically. A migration/one-off script would be needed to relocate legacy files if required.
4. New uploads will only be written to the public path.
5. The `serve` action still works (and can be extended later for access control, transformations, or signed URLs) but is no longer strictly required to access the raw bytes.

Security Considerations:
Because files are now directly web-accessible, do not store sensitive or user-uploaded executable content. MIME validation remains in place (restricted to common image types). If stricter sanitization is desired, add additional checks in `validateUpload()` or post-process images via a re-encode step.

Next Steps (Optional):

- Add a CLI command to migrate old images from `storage/images` into the new public directory.
- Set appropriate cache headers (e.g., long-lived max-age) when serving through the controller, or rely on web server static file caching for `direct_url`.
- Generate and store a content hash in filenames for stronger cache busting.
