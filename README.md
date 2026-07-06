# R3D Comments (`pkg_r3dcomments`)

R3D Comments is a privacy-friendly Joomla comments solution with a package that installs:
- `com_r3dcomments` (component)
- `mod_r3dcomments` (frontend module)

Current release: `6.1.26`.

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

## Installation

1. Open Joomla backend: `Extensions -> Manage -> Install`.
2. Upload the `pkg_r3dcomments-<version>.zip` package archive.
3. Verify both extensions are installed:
- component: `com_r3dcomments`
- module: `mod_r3dcomments`

## Update Server

Configured package feed:
- `https://extensions.r3d.de/joomlaextensions/updates/pkg_r3dcomments.xml`

Package ZIP download:
- `https://extensions.r3d.de/phocadownload/pkg_r3dcomments-<version>.zip`

## Notes

- Joomla manifests and XML references use forward slashes `/` only.
- If quote/reply appears non-responsive after update, clear Joomla cache and browser cache to ensure latest inline JS is loaded.

## Changelog

See [CHANGELOG.md](./CHANGELOG.md).
