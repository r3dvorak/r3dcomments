## 6.1.9 (2026-05-09)
- Fixed frontend JS breakage in quote/reply flow caused by runtime-mangled </> regex literals in inline script output.
- Replaced HTML escaping logic with character-code based implementation to keep quote/reply handlers executable in guest and logged-in views.
## 6.1.8 (2026-05-09)
- Fixed frontend save controller to use validated JForm payload as canonical data.
- Preserved guest anti-spam helper fields (orm_started_at and configured honeypot field) after validation.
- Stabilized guest reply/quote persistence (parent_id / quoted_comment_id) in save flow.
## 6.1.7 (2026-05-09)
- Split quote/reply frontend behavior by intent: reply uses reply-preview, quote inserts directly into editor/textarea.
- Removed quote preview duplication (no duplicate quote in preview + editor).
- Ensured guest quote always writes visibly into guest textarea.
## 6.1.6 (2026-05-08)
- Fixed frontend reply/quote persistence: added missing hidden form fields parent_id and quoted_comment_id to site comment forms.
- Replies and quotes are now no longer dropped during form validation.
## 6.1.5 (2026-05-08)
- Replaced outdated component frontend comments template with the current module-equivalent logic.
- Fixed quote/reply interactions by using button-based actions and unified JS handlers.
- Aligned component comment timestamp rendering with Joomla/user timezone formatting.
## [6.1.3]
### Fixed
- Comment dates in module output are now rendered in Joomla/user timezone instead of raw UTC DB timestamps.
- Comment editor is rendered via Joomla editor API with explicit `readonly=false` to avoid locked input field in frontend.
All notable changes to this package are documented here.

## [6.1.2]
### Fixed
- Quote action buttons switched from anchor links to real buttons to prevent unwanted page jump-to-top behavior.
- Guest form labels normalized to readable text (no `JGLOBAL_NAME` key leakage in output).
- Guest field order corrected: Name and E-Mail now render above the comment field.

### Changed
- Frontend comment field switched back to editor field via site form definition (`comment_site.xml`) with collision-safe loading.

## [6.1.1]
### Fixed
- Frontend comment field is now rendered as a plain textarea in module templates to avoid readonly JCE editor lock in site context.
- Frontend `CommentModel` now loads a dedicated site form (`comment_site.xml`) to avoid admin-form collisions.

### Changed
- Synced and updated project documentation (`README.md`, `CHANGELOG.md`).
- Updated README to the standardized `_tools` build/package workflow and current repository layout.
- Added local project `AGENTS.md` governance file (kept local, excluded from GitHub).

## [6.1.0] (2026-05-08)
### Changed
- Standardized project structure and build workflow.

## [6.0.25]
### Fixed
- Preserved language on comment form POST (`lang` is now passed to `comment.save`).
- Success and error messages now follow the active page language more reliably.

## [6.0.24]
### Fixed
- Added multilingual category-association support to module category filtering.
- Comment block now renders correctly on translated article categories.

## [6.0.23]
### Added
- Added Greek language support (`el-GR`) for component and module.

### Changed
- Merged provided Greek translations and filled missing keys with EN fallback.
- Updated README files with language support notes.

## [6.0.22]
### Fixed
- Reworked `ip_hash` migration SQL for broader MySQL/MariaDB compatibility.
- Resolved update failures on hosts not supporting `ADD COLUMN IF NOT EXISTS`.

## [6.0.21]
### Changed
- Improved visual quote highlighting (`blockquote`) in frontend comments.
- Enhanced quote format with clearer author reference.

### Fixed
- Additional redirect stability improvements after comment submit.

## [6.0.20]
### Fixed
- Made `ip_hash` schema migration idempotent.
- Prevented duplicate-column errors on repeated updates.

## [6.0.19]
### Added
- Added quote button in module output (selected text or full comment).
- Inserted quote content directly into editor with quote reference metadata.

## [6.0.18]
### Changed
- Rolled out anti-spam and security feature set in package release line.
- Synced package build with updated component/module artifacts.

## [6.0.17]
### Changed
- Improved template-neutral default rendering behavior.
- Refined frontend form output for standard Joomla template positions.

## [6.0.16]
### Changed
- Frontend UI refinements (reply/submit buttons, spacing, visual separation).
- Improved nested comment presentation.

## [6.0.15]
### Fixed
- Improved localization fallback behavior for unresolved keys.
- Additional module output and label consistency fixes.

## [6.0.14]
### Fixed
- Improved frontend routing and context stability.
- Better reliability across module positions and article detection.

## [6.0.13]
### Changed
- Continued package structure cleanup for safer install/update paths.

### Fixed
- Additional Joomla 6 compatibility adjustments.

## [6.0.12]
### Fixed
- Frontend/backend comment-processing fixes.
- Minor stability and compatibility improvements.

## [6.0.11]
### Fixed
- Additional package integration and deployment-flow fixes.
- Better update consistency across component/module/package.

## [6.0.10]
### Fixed
- Maintenance release with build and stability fixes.
- More consistent install/update behavior.

## [6.0.9]
### Fixed
- Minor maintenance fixes and cleanup.

## [6.0.8]
### Fixed
- Fixed Joomla ACL asset corruption on uninstall.
- Stabilized install/update SQL and merged update scripts into installer flow.
- Fixed missing table creation on some hosting environments.
- Added Joomla 6 router and dispatcher compatibility fixes.

### Changed
- Improved module-component communication.
- Added help system (toolbar button + dedicated Help view).
- Removed deprecated manifest entries.

## [6.0.7]
### Fixed
- Fixed missing translation strings in admin interface.
- Stabilized category/subcategory filtering.

### Changed
- Improved moderation UI and article context detection in module flow.

## [6.0.6]
### Added
- Introduced subscription table `#__r3dcomments_subscriptions`.
- Added frontend “Subscribe to comments” button.
- Added `toggleSubscription` controller task.

### Fixed
- Fixed module output for YOOtheme positions.

## [6.0.5]
### Fixed
- Fixed asset creation for modules.
- Improved routing for comment actions.

### Changed
- Unified component and module versioning.

## [6.0.4]
### Fixed
- Corrected installer XML for Joomla 6.
- Minor backend list view fixes.

### Changed
- Enhanced UCM-independent storage model.

## [6.0.3]
### Changed
- Refactored component MVC structure.
- Added admin search tools and filters.
- Improved reply threading logic.

## [6.0.2]
### Added
- Clean installer and update-safe structure.
- Joomla 5/6 compatibility baseline.
- Basic ACL rules.
- YOOtheme-compatible module output.
- Approval workflow (unpublished → published).
- Admin email notifications for new comments.
- Comment editing by author.
- Two-level commenting (comment + reply).
- Category-based comment assignment.
- Core component and frontend module packaging.

### Note
- Initial package release line.







