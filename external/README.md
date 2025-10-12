# External Reference Material

This directory contains local-only reference materials that are **not** part of the application runtime and are ignored by git (see `.gitignore`).

## CakePHP Documentation
(Shallow clone, English only)
Path: `external/cakephp-docs/en`
Update:
```bash
cd external/cakephp-docs
git fetch --depth 1 origin 5.x && git reset --hard origin/5.x
```

## Bootstrap 5.3.2 Documentation (Sparse)
Path: `external/bootstrap-docs/site/content/docs/5.3`
Update:
```bash
cd external/bootstrap-docs
git fetch --depth 1 origin v5.3.2
# (detached tag; re-clone if wanting a different version)
```
To switch to another version (e.g. 5.3.3):
```bash
rm -rf external/bootstrap-docs
# re-run sparse clone with tag v5.3.3
```

## MariaDB Curated Pages
Path: `external/mariadb-docs/*.html`
Update:
```bash
cd external/mariadb-docs
./update.sh
```

## DataTables Manual (Curated)
Path: `external/datatables-docs/*.html`
Update:
```bash
cd external/datatables-docs
./update.sh
```
Add or remove topics by editing `update.sh` page list.

## Maintenance Summary
- All external doc directories are git-ignored.
- Safe to delete any directory and recreate via the commands above.
- Keep size small by limiting pages / sparse checkout.
