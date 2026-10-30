---
title: "CP Locations Integration"
slug: cp-locations-integration-cp-live
section: options-and-features-cp-live
order: 6
excerpt: "Configure per-location live streams with separate schedules, credentials, and streaming providers for each location."
status: publish
---

# CP Locations Integration

CP Live integrates with CP Locations to provide per-location live stream configuration. Each location can have its own streaming service credentials, schedule, and live status.

## Prerequisites

Before setting up location-based live streaming:

- CP Locations plugin is installed and activated
- At least one location is created in CP Locations
- CP Live is installed, activated, and licensed

## Enabling Location Streams

1. Navigate to **Settings → CP Live → Advanced**
2. Set **Enable Location Streams** to **Enable**
3. Click **Save Changes**

## How the Integration Works

When location streams are enabled, each location in CP Locations gets its own set of live stream settings. CP Live checks each location independently during scheduled times, allowing different locations to be live at different times with different streaming providers.

Each location stores its streaming configuration as post metadata, separate from the global CP Live settings. This means Location A can use YouTube while Location B uses Resi.

## Configuring a Location's Live Stream

1. Navigate to your location edit screen in **CP Locations**
2. Scroll down to find the live stream metaboxes:
   - **YouTube Live** (or **Resi Live**, depending on your enabled services)
   - **Live Schedules**
3. Enter the streaming service credentials for this location
4. Configure the schedule for this location's service times

### Location Schedule Fields

| Field | Description |
| --- | --- |
| **Force Pull** | Manually trigger a live check for this location |
| **Live Time Description** | A text description of when this location streams (displayed to visitors) |
| **Schedule** | Day and time entries for this location's service schedule |

## Per-Location Body Classes

When viewing a location page, CP Live adds location-specific CSS classes:

| Class | When Applied |
| --- | --- |
| `cp-location-is-live` | This location's stream is currently live |
| `cp-location-not-live` | This location's stream is not active |

These classes are in addition to the global `cp-is-live` and `cp-not-live` classes.

## Using the Location Live Shortcode

Use the `No live feeds were found` shortcode on location pages to display the location-specific live stream. The shortcode automatically detects the current location context from the URL.

## Troubleshooting

### Location Stream Not Detected

1. Verify the streaming credentials are entered on the location edit screen (not just the global settings)
2. Confirm a schedule is configured for this specific location
3. Check that **Enable Location Streams** is turned on in **Advanced** settings
4. Use the **Force Pull** checkbox on the location edit screen to test detection

### Location Using Wrong Stream

Each location uses its own credentials. Verify you have entered the correct Channel ID, API Key, or Embed ID for the specific location.

For more help, see the [Troubleshooting](../advanced/troubleshooting.md) guide.
