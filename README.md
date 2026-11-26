# KW Manager

KW Manager is a WordPress plugin that lets administrators change images and text defined in a theme template from the WordPress dashboard. A template declares the editable fields and their defaults. The plugin discovers the fields when the page renders and stores edits in WordPress options.

## Try it locally

1. Copy this directory to `wp-content/plugins/kw-manager` in a local WordPress installation and activate **KW Manager** under **Plugins**.
2. Add the following markup to a page template in a theme you control. Replace the example image path with the path to an image in your local site.

   ```html
   <kw-text id="hero-heading">Welcome to the demo</kw-text>
   <kw-img id="hero-image" src="/wp-content/uploads/demo-banner.jpg" alt="Demo banner" />
   ```

3. Visit the page so the plugin can register the fields. Open **KW Manager** in the WordPress dashboard, edit the heading or pick an image from the Media Library, then save and revisit the page.

Keep field IDs unique across templates. Fields with the same prefix, such as `hero-heading` and `hero-image`, appear in the same section. The dashboard is in Spanish.

## Code overview

- `kw-manager.php` discovers fields, renders the dashboard and saves overrides.
- `kw-manager.js` handles the Media Library picker and image previews.
- `kw-manager.css` styles the dashboard.

This portfolio sample comes from a WordPress Studio prototype. I used Codex to prepare the public copy and update how image previews are rendered in the dashboard.
