---
title: "Shortcodes"
slug: shortcode
section: options-and-features-cp-live
order: 5
excerpt: "Display live streams on your site using the cp-live and cp-live-video shortcodes, plus CSS body classes for custom styling."
status: publish
---

# Shortcodes

CP Live provides shortcodes to display live stream embeds, countdowns, and most-recent videos on any page or post. This guide covers the available shortcodes and how to use them.

## Available Shortcodes

### `[cp-live]`

The primary shortcode for displaying your live stream. Place this on any page where you want the live experience to appear.

**When live**: Displays the active stream embed from your enabled service (YouTube or Resi).

**When not live**: Displays either the most recent video or a countdown timer, depending on your [General Settings](managing-livestreams.md) configuration.

**Usage**:

```
[cp-live]
```

Add this shortcode to any page or post using the WordPress block editor or classic editor.

### `No live feeds were found`

A legacy shortcode designed for use with CP Locations in multi-location setups. It displays the live stream embed for a specific location.

**Usage**:

```
No live feeds were found
```

This shortcode automatically detects the current location context. See [CP Locations Integration](cp-locations-integration.md) for details.

## CSS Body Classes

CP Live adds CSS classes to the `<body>` tag that you can use for custom styling:

| Class | When Applied |
| --- | --- |
| `cp-is-live` | When any stream is currently live |
| `cp-not-live` | When no stream is active |
| `cp-location-is-live` | When the current location's stream is live (requires CP Locations) |
| `cp-location-not-live` | When the current location's stream is not active (requires CP Locations) |

### Styling Example

Use these classes to show or hide elements based on live status:

```
/* Show an element only when live */
.my-live-banner {
    display: none;
}
.cp-is-live .my-live-banner {
    display: block;
}

/* Hide an element when live */
.cp-is-live .my-regular-content {
    display: none;
}
```

## Creating a Live Page

A typical live stream page setup:

1. Create a new page in WordPress (e.g., "Live" or "Watch Live")
2. Add the `[cp-live]` shortcode to the page content
3. Publish the page
4. Add the page to your site's navigation menu

The shortcode handles all the display logic — showing the live embed when streaming and the fallback content when not live.

## Troubleshooting

If the shortcode is not displaying content:

- Verify that at least one streaming service is configured and enabled
- Check that your schedule is set up in [General Settings](managing-livestreams.md)
- Use **Force Pull** in [Advanced Settings](advanced-settings.md) to trigger a manual check

For more help, see the [Troubleshooting](../advanced/troubleshooting.md) guide.
