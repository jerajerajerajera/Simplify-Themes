# Simplify Themes

A plugin for the [Pelican panel](https://pelican.dev): make your own look for the panel, with clear editors and a live
preview. Pick colours for light and dark, a background (plain, gradient or your own picture), fonts (free fonts that
ship with the plugin, or your own) and, with Simplify, the shapes and the console colours.

It works on its own. **Works best with [Simplify](https://github.com/jerajerajerajera/simplify)**: with Simplify
installed it opens inside Simplify, it shows up in Simplify's add-on list and a theme also sets corners, spacing,
shadows, see-through cards and the console.

## Setup

1. Install the plugin from the Pelican Hub.
2. Open **Themes** under **Simplify** in the admin menu. Four ready-made themes are there to start from.
3. Pick a theme, change it in the tabs and press **Save**. Nothing changes for anyone until you save.
4. In the **⋯** menu, choose **Make default for everyone**.

## What it adds

- **Themes**: as many as you like. New, duplicate, export as a file, import, reset to Simplify's look, delete.
- **Colours**: with Simplify ten roles (accent, background, cards, raised, text, muted text, lines, running, warning,
  danger), each with a light and a dark value, and a readability check against WCAG AA (4.5:1). Without Simplify five
  colours (accent, grey tint, running, warning, danger), from which Pelican makes its full palette. A warning never
  blocks Save.
- **Background**, for light and dark each: plain, your own gradient (2 to 6 colours with how softly they blend, linear, radial or conic, an angle
  dial, and a seamless repeat), or one of your pictures, with fade (a light veil in light mode, a dark one in dark mode), blur and fit.
- **Effect**: a moving background behind every page: shooting stars, snow, fireflies, rain, aurora or bubbles, with
  amount, speed, size, strength, glow, a direction dial and the theme's colours or your own. It pauses while the panel
  is not on screen, stays still for people who ask their device for less motion, and draws less on a phone.
- **Fonts**: a text font and a heading font from free fonts that ship with the plugin, or a font you upload.
- **Shapes & console** (with Simplify): corners, spacing, shadows, lines, console colours and console font.
- **Let people pick**: when you turn it on, everyone can choose one of the themes for themselves under **My theme** in
  their account menu. It is off by default.
- **Status page** (with a Simplify that offers add-on API 11): the public status page (/status and its own address) wears
  the default theme: colours, background, fonts and effect, light or dark as the visitor's device prefers. Title, logo,
  intro and your own CSS from Website › Status page stay as they are. It is off by default.

## Uploads

- **Fonts**: WOFF2 or TTF, up to 2 MB. The plugin checks the file itself, not its name. Only upload fonts you are
  allowed to use on your panel.
- **Pictures**: PNG, JPG or WebP, up to 5 MB. Every picture is drawn again and saved as WebP (at most 2560 pixels), so
  nothing else that was in the file is kept. This needs PHP's GD extension with WebP.
- Files are stored in the panel's private storage under a random name. A theme never holds free CSS: every value is
  checked against a fixed list.

## What it sends where

Nothing leaves your panel. Every font ships with the plugin, nothing loads from another site.

The plugin adds three read-only routes; the two for files only answer a fixed file-name pattern:

- `/simplify-themes/font/{file}`: the fonts that ship with the plugin.
- `/simplify-themes/effects.js`: the script that draws a moving background, only on pages whose theme has one.
- `/simplify-themes/file/{file}`: the fonts and pictures you uploaded (a random name of 32 characters).

Both are open without signing in, because the login page uses the default theme too.

## If something goes wrong

To show Simplify's own look to everyone again, run on the panel:

```
sudo -u www-data php artisan simplify-themes:reset
```

Your themes and files stay.

## Fonts

The fonts in `resources/fonts` are free fonts under the SIL Open Font License; see
[`resources/fonts/LICENSES.txt`](resources/fonts/LICENSES.txt).

## License

[MIT](LICENSE.md)
