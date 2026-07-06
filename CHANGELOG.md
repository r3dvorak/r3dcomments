# Changelog

All notable changes to this package are documented in this file.

## 6.1.16 (2026-07-06)
- Fixed Joomla 5 package upgrade SQL by removing prepared-statement execution from the migration.
- Kept the package, component, module, and update feed metadata aligned with the new release.

## 6.1.15 (2026-06-02)
- Release uptick to keep the package, component, module, and documentation metadata aligned.
- Preserved the current JED compliance baseline and Joomla 4.4/5 compatibility behavior.

## 6.1.14 (2026-06-02)
- Release uptick to keep the package, component, module, and documentation metadata aligned.
- Preserved the current JED compliance baseline and Joomla 4.4/5 compatibility behavior.

## 6.1.13 (2026-06-02)
- Release uptick to keep the package, component, module, and documentation metadata aligned.
- Preserved the current JED compliance baseline and redirect handling behavior.

## 6.1.12 (2026-06-02)
- Bumped the package release to keep the component, module, and package metadata in sync.
- Kept the JED compliance fixes from the previous release as the baseline for this build.

## 6.1.11 (2026-05-09)
- Fixed frontend preview modal script output that could break markup and leak JS as visible text.
- Replaced string-template preview rendering with DOM-based rendering for safer guest/logged-in behavior.
- Restored guest quote/reply click handling after preview-script break.
- Fixed nested guest quote parsing in preview (no trailing `[/quote]` outside blockquote).
- Replaced problematic `&mdash;` rendering in quote cite output with a plain `-` fallback to avoid entity text leakage.
- Updated release metadata source so changelog links point to this repository/branch.

## 6.1.10 (2026-05-09)
- Added frontend comment preview modal (Preview) before submit.
- Reworked message handling to render R3D-related Joomla messages as toast overlays more reliably.

## 6.1.9 (2026-05-09)
- Fixed frontend JS breakage in quote/reply flow caused by runtime-mangled `<`/`>` regex literals in inline script output.
- Replaced HTML escaping logic with a character-code based implementation so quote/reply handlers execute reliably for guests and logged-in users.

## 6.1.8 (2026-05-09)
- Fixed frontend save controller to use validated JForm payload as canonical data.
- Preserved guest anti-spam helper fields (`form_started_at` and configured honeypot field) after validation.
- Stabilized guest reply/quote persistence (`parent_id` / `quoted_comment_id`) in save flow.

## 6.1.7 (2026-05-09)
- Split quote/reply frontend behavior by intent: reply uses reply-preview, quote inserts directly into editor/textarea.
- Removed quote preview duplication (no duplicate quote in preview + editor).
- Ensured guest quote always writes visibly into guest textarea.

## 6.1.6 (2026-05-08)
- Fixed frontend reply/quote persistence: added missing hidden form fields `parent_id` and `quoted_comment_id` to site comment forms.
- Replies and quotes are now no longer dropped during form validation.

## 6.1.5 (2026-05-08)
- Replaced outdated component frontend comments template with the current module-equivalent logic.
- Fixed quote/reply interactions by using button-based actions and unified JS handlers.
- Aligned component comment timestamp rendering with Joomla/user timezone formatting.

## 6.1.3 (2026-05-08)
- Comment dates in module output are now rendered in Joomla/user timezone instead of raw UTC DB timestamps.
- Comment editor is rendered via Joomla editor API with explicit `readonly=false` to avoid locked input fields in frontend.

## 6.1.2 (2026-05-08)
- Quote action buttons switched from anchor links to real buttons to prevent unwanted page jump-to-top behavior.
- Guest form labels normalized to readable text (no `JGLOBAL_NAME` key leakage in output).
- Guest field order corrected: Name and E-Mail now render above the comment field.

## 6.1.1 (2026-05-08)
- Frontend comment field rendered as plain textarea in module templates to avoid readonly JCE lock in site context.
- Frontend `CommentModel` now loads a dedicated site form (`comment_site.xml`) to avoid admin-form collisions.
- Documentation updated for standardized `_tools` workflow.

## 6.1.0 (2026-05-08)
- Standardized project structure and build workflow.

## Older Releases
- Full history for `6.0.x` is preserved in Git history and prior changelog commits.
