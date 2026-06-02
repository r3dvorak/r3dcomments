# R3D Comments (`pkg_r3dcomments`)

R3D Comments is a privacy-friendly Joomla comments solution with a package that installs:
- `com_r3dcomments` (component)
- `mod_r3dcomments` (frontend module)

The project is standardized to the shared R3D Joomla workflow (`D:/1DEV/_tools`).

Current release: `6.1.12`.

## Compatibility

- Joomla `6.x` (primary target)
- Joomla `5.x` (legacy compatibility)
- PHP `8.2+` recommended
- MySQL/MariaDB

## Included Languages

- `en-GB`
- `de-DE`
- `el-GR`

## Main Features

- Two-level comments (comment + reply)
- Quote support in frontend flow (guest + logged-in)
- Category-based module filtering
- Moderation/publication workflow
- Notification hooks for administrators
- Rendering mode options for standard templates and UIkit contexts

## Frontend Behavior (Current)

- `Reply`:
  - sets `parent_id` / `quoted_comment_id`
  - shows reply preview box
  - does not auto-insert quote text into editor/textarea
- `Quote`:
  - sets `parent_id` / `quoted_comment_id`
  - inserts quote text directly into input field
  - guest: inserts into plain textarea
  - logged-in: inserts into active editor
  - no duplicate quote preview + editor insertion

## Standard Project Structure

```text
r3dcomments/
├── 01_src/        # extension sources (component/module/package manifest/script)
├── 02_build/      # local build workspace (ignored)
├── 03_docs/       # local docs/logs (ignored)
├── 04_dist/       # generated ZIP artifacts (ignored)
├── 05_updates/    # local update/publish workspace (ignored)
├── project.json   # metadata source of truth
├── VERSION
├── README.md
└── CHANGELOG.md
```

## Build Workflow

Run from project root `D:/1DEV/pkgs/r3dcomments`:

1. Build extension ZIPs:
```powershell
D:/1DEV/_tools/04-build-extension.ps1
```

2. Build package ZIP:
```powershell
D:/1DEV/_tools/05-build-package.ps1
```

Expected output:
- `04_dist/com_r3dcomments-<version>.zip`
- `04_dist/mod_r3dcomments-<version>.zip`
- `04_dist/pkg_r3dcomments-<version>.zip`

## Installation

1. Open Joomla backend: `Extensions -> Manage -> Install`.
2. Upload `pkg_r3dcomments-<version>.zip` from `04_dist`.
3. Verify both extensions are installed:
- component: `com_r3dcomments`
- module: `mod_r3dcomments`

## Update Server

Configured package feed:
- `https://www.r3d.de/joomlaextensions/updates/pkg_r3dcomments.xml`

## Notes

- `scripts/` and `05_updates/` are intentionally local-only and not synced to GitHub.
- Joomla manifests and XML references use forward slashes `/` only.
- If quote/reply appears non-responsive after update, clear Joomla cache and browser cache to ensure latest inline JS is loaded.

## Changelog

See [CHANGELOG.md](./CHANGELOG.md).
