---
title: "Connecting to Resi"
slug: connecting-to-resi
section: options-and-features-cp-live
order: 2
excerpt: "Set up Resi live stream detection, including finding your Embed ID and Stream URL."
status: publish
---

# Connecting to Resi

CP Live integrates with Resi to detect active live streams by checking the status of your Resi web channel's stream manifest. This guide walks you through configuring Resi as your streaming service.

## Prerequisites

Before configuring Resi in CP Live:

- An active Resi account with a web channel configured
- Access to your Resi web channel's embed code and stream profile

## Enabling Resi

Resi is disabled by default. To enable it:

1. Navigate to **Settings → CP Live → Advanced**
2. Set **Enable Resi Live** to **Enable**
3. Click **Save Changes**
4. A new **Resi** tab appears in the CP Live settings

## Finding Your Resi Credentials

### Embed ID

1. Log in to your Resi dashboard
2. Navigate to your web channel
3. Open the embed code for your channel
4. Find the `data-embed-id` value — this is your Embed ID

### Stream URL

1. In your Resi dashboard, navigate to your web channel profile
2. Find the Stream URL — it ends in `Manifest.mpd`
3. Copy the full URL

## Configuring Resi in CP Live

1. Navigate to **Settings → CP Live**
2. Click the **Resi** tab
3. Configure the following settings:

| Setting | Description |
| --- | --- |
| **Embed ID** | Your Resi web channel embed ID |
| **Stream URL** | The stream URL ending in `Manifest.mpd` — used to check stream status |
| **Stream Status** | Displays the current stream status (read-only). A status of `dynamic` indicates an active stream |

4. Click **Save Changes**

## How Resi Detection Works

CP Live checks your Resi stream by loading the `Manifest.mpd` URL and inspecting the manifest type:

- **dynamic**: The stream is currently active — CP Live sets the site to live
- **static** or empty: No active stream detected

This check runs during your configured schedule windows at the interval set in [Advanced Settings](advanced-settings.md).

## Testing Your Configuration

1. Navigate to **Settings → CP Live → Advanced**
2. Check the **Force Pull** checkbox
3. Click **Save Changes**
4. Return to the **Resi** tab to check the **Stream Status** field

## Troubleshooting

### Stream Not Detected

1. Verify the **Stream URL** is correct and ends in `Manifest.mpd`
2. Confirm the **Embed ID** matches your Resi web channel
3. Check that your schedule window covers when you go live
4. Use **Force Pull** to test detection outside of scheduled times

### Embed Not Displaying

1. Verify the Embed ID is entered correctly
2. Check that the Resi web player script is not blocked by your site's security headers or content security policy
3. Clear any page caches on your site

For more help, see the [Troubleshooting](../advanced/troubleshooting.md) guide.
