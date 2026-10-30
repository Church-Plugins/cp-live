---
title: "Requirements"
slug: requirements-cp-live
section: getting-started-cp-live
order: 3
excerpt: "System requirements, streaming service prerequisites, and optional dependencies for CP Live."
status: publish
---

# Requirements

This document outlines the system requirements and dependencies for running CP Live on your WordPress site.

## System Requirements

| Requirement | Minimum Version |
| --- | --- |
| MySQL | 5.6+ |

## Streaming Service Requirements

You need an account with at least one supported streaming provider. You do not need both — choose the one your church uses.

### YouTube

To use YouTube live stream detection, you need:

- A YouTube channel with live streaming enabled
- A YouTube Data API key from the [Google Cloud Console](https://console.cloud.google.com) — see [YouTube Setup](../features/connecting-to-youtube.md) for step-by-step instructions
- Your YouTube Channel ID (found in your channel URL or YouTube Studio settings)

### Resi

To use Resi live stream detection, you need:

- An active Resi account with a web channel configured
- Your Resi Embed ID (found in the web channel embed code) — see [Resi Setup](../features/connecting-to-resi.md) for details on locating this
- Your Resi Stream URL (the URL ending in `Manifest.mpd`, found in your web channel profile)

## WordPress Cron

CP Live depends on WordPress cron (WP-Cron) to check for live streams at regular intervals. Most WordPress hosts support WP-Cron out of the box.

If your hosting environment disables WP-Cron, you need to set up a system cron job to trigger `wp-cron.php` at your desired interval:

```
*/2 * * * * wget -q -O /dev/null https://yoursite.com/wp-cron.php
```

See your hosting provider's documentation for instructions on setting up system cron jobs.

## Browser Compatibility

CP Live's frontend display works in all modern browsers. The countdown timer uses the easytimer.js library, which supports:

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## Optional Dependencies

- **CP Locations**: Required for per-location live stream configuration. Install and activate CP Locations before enabling location streams in CP Live. See [CP Locations Integration](../features/cp-locations-integration.md) for setup details.

## Next Steps

Continue to [Installation](installation.md) to install and activate CP Live.
