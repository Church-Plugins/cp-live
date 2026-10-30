---
title: "Frequently Asked Questions"
slug: faq-cp-live
section: faq
order: 2
excerpt: "Answers to common questions about CP Live setup, configuration, streaming behavior, and multi-location support."
status: publish
---

# Frequently Asked Questions

## General

### What is CP Live?

CP Live is a WordPress plugin that automatically detects and displays live video streams from YouTube and Resi on your church website. It uses schedule-based detection to check for streams only when you expect them.

### Which streaming services does CP Live support?

CP Live supports YouTube and Resi. Developers can add additional services using the `cp_live_available_services` filter. See the [Developer Guide](../advanced/developer-guide.md) for details.

### Does CP Live work with any WordPress theme?

Yes, CP Live works with any WordPress theme. It uses shortcodes and standard WordPress embeds to display content. You can further customize the display using the CSS body classes `cp-is-live` and `cp-not-live`.

## Setup and Configuration

### How do I get a YouTube API key?

Create an API key in the [Google Cloud Console](https://console.cloud.google.com) with YouTube Data API v3 enabled. See the [YouTube Setup](../features/connecting-to-youtube.md) guide for step-by-step instructions.

### How do I find my YouTube Channel ID?

Your Channel ID appears in your YouTube channel URL as `youtube.com/channel/YOUR_ID`. You can also find it in YouTube Studio under **Settings → Channel → Advanced settings**. See [YouTube Setup](../features/connecting-to-youtube.md) for details.

### What does "Force Live" do in the schedule settings?

When Force Live is checked for a schedule, CP Live automatically sets your site to "live" status at the scheduled time, even if no stream is detected from your provider. This is useful if you want your site to enter live mode regardless of API detection.

### Can I have multiple service times per day?

Yes. When adding a schedule, you can add multiple times for the same day by clicking the **+** button next to the time field.

## Live Stream Behavior

### How quickly does CP Live detect a live stream?

Detection speed depends on the **Request Interval** setting (default: 2 minutes) and the **Buffer Before** setting (default: 8 minutes). CP Live starts checking 8 minutes before your scheduled time and checks every 2 minutes.

### What happens when my stream ends?

CP Live continues to show the stream as live for the duration set in **Live Video Duration** (default: 6 hours). After that period, the site returns to its not-live display mode.

### Can I manually set the site to "live" or "not live"?

Yes. Go to the streaming service tab (YouTube or Resi) and change the **Channel Status** to **Live** or **Not Live**, then save. You can also use **Force Pull** in the Advanced tab to reset and re-check.

## Multi-Location

### Can different locations have different streaming services?

Yes. When CP Locations integration is enabled, each location gets its own set of streaming service credentials and schedule. One location can use YouTube while another uses Resi. See [CP Locations Integration](../features/cp-locations-integration.md).

### Do I need CP Locations for CP Live to work?

No. CP Locations is optional. CP Live works as a standalone plugin for single-location churches. CP Locations is only needed if you want per-location stream configuration.

## Troubleshooting

### My stream is not being detected. What should I do?

Check your service credentials, verify your schedule covers the correct time, and try using **Force Pull** in Advanced settings. See the [Troubleshooting](../advanced/troubleshooting.md) guide for detailed steps.

### Will CP Live affect my site performance?

No. CP Live only makes API calls during scheduled windows and uses WordPress cron for background processing. It does not add any load to your frontend pages.
