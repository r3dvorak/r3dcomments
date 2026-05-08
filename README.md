# R3D Comments (pkg_r3dcomments)

R3D Comments is a lightweight, fast, and privacy-friendly comment extension for Joomla 5 and Joomla 6.
It offers basic commenting features without external services and is fully integrable with Joomla.

The package includes:
- a standalone Joomla component (`com_r3dcomments`)
- a frontend module (`mod_r3dcomments`) with two render modes:
  - `Standard / Neutral` (recommended for plain Joomla templates)
  - `YOOtheme / UIkit` (for sites that use UIkit styling)

Ideal for websites that need simple, clear, and GDPR-friendly comments.

---

## Features

- **Joomla 5 & Joomla 6 compatible**
- **Language packs included**: `en-GB`, `de-DE`, `el-GR`
- **Two levels** (comment + reply)
- **Quote**: Selected comment text is automatically copied
- **Category filter** (comments only in defined categories)
- **Works with standard Joomla template positions** (for example `bottom-a`)
- **Frontend editing** (author can edit their comment)
- **Notification to administrators**
- **Publication workflow** (comments optionally visible only after approval)
- **Template-neutral output path** (`Standard / Neutral`) for template flexibility
- **Integrated update server** (for automatic updates in the Joomla backend)

---

## System requirements

- PHP 8.1 or higher
- Joomla 5 or Joomla 6
- MySQL/MariaDB
- Optional: YOOtheme Pro for module embedding in the builder

---

## Installation

1. Download the latest ZIP file from the `/04_dist/` folder.  
2. In the Joomla backend: *Extensions → Manage → Install*.  
3. The package installs:
   - Component  
   - Module  

4. Activate the **mod_r3dcomments** module  
5. Place it in a template position (for example `bottom-a`) or in YOOtheme Pro as a **Module Element**.
6. In module options:
   - keep **Categories** empty to show comments in all article categories
   - choose **Rendering Mode**:
     - `Standard / Neutral` for plain Joomla templates
     - `YOOtheme / UIkit` for UIkit-based output

---

## Usage

### Via module (recommended)
Display the **mod_r3dcomments** module on article pages via normal Joomla module assignment.
The module works without YOOtheme and can optionally render in a YOOtheme/UIkit style.

### Current scope
- Primary context: `com_content` article detail pages.
- Category filtering is optional. If no category is selected in module options, all article categories are allowed.

---

## Update server

The package contains its own update server entry.  
Updates appear automatically in the Joomla backend.

The update feed is located at:  
`https://www.r3d.de/joomlaextensions/updates/pkg_r3dcomments.xml`

---

## Downloads

All official releases are available in the folder:

`/04_dist/`

or via GitHub Releases.

---

## Development

### Repository structure

pkg_r3dcomments/
├── 01_src/ # Installable Joomla package
├── 02_build/ # Build scripts (not part of the repo)
├── 03_docs/ # Documentation, screenshots
├── 04_dist/ # Build artifacts (ZIPs)
└── 05_updates/ # Update feed generator

The folders `02_build` and `05_updates` are excluded for security reasons.

---

## Troubleshooting

- Module shows nothing on frontend:
  - Ensure you are on a `com_content` article detail page.
  - Check module assignment (`Menu Assignment` + published status).
  - If category filter is active, ensure the current article category is included.
- Style looks broken on non-UIkit template:
  - Set module `Rendering Mode` to `Standard / Neutral`.
- Strings appear as language keys:
  - Reinstall the latest package ZIP and clear Joomla cache (`System -> Clear Cache`).
  - Check that your site language has installed files for this extension (`en-GB`, `de-DE`, `el-GR`).

---

## License

GNU General Public License v2 or later  
Further information can be found in the `LICENSE` file.

## Support

Website: https://r3d.de  
Email: dev@r3d.de  
Author: Richard Dvořák / R3D Internet Services

---

## Changelog

See `CHANGELOG.md` in the repository.
