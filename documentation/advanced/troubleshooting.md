---
title: "Troubleshooting"
slug: troubleshooting-cp-live
section: advanced-cp-live
order: 2
excerpt: "Solutions for common CP Live issues including stream detection problems, cron issues, and display problems."
status: publish
---

# Troubleshooting

This guide covers common issues with CP Live and how to resolve them.

## Live Stream Detection

### Stream Not Being Detected

1. **Check your schedule**: Verify that your schedule in **Settings → CP Live → Main** covers the time you are going live. CP Live only checks for streams during scheduled windows.
2. **Verify buffer settings**: In **Settings → CP Live → Advanced**, confirm the Buffer Before and Buffer After values give enough time for your stream to start.
3. **Test with Force Pull**: Go to **Settings → CP Live → Advanced**, check **Force Pull**, and click **Save Changes** to trigger an immediate check.
4. **Check service credentials**: Verify your YouTube API key and Channel ID, or your Resi Embed ID and Stream URL, are entered correctly.

### Site Shows as Live When It Should Not

1. **Check Live Video Duration**: In **Settings → CP Live → Advanced**, verify the **Live Video Duration** is not set too high. The default is 6 hours.
2. **Manually reset status**: Go to your streaming service tab (YouTube or Resi) and set **Channel Status** to **Not Live**, then click **Save Changes**.

### Stream Detected Late

1. **Reduce the check interval**: In **Settings → CP Live → Advanced**, lower the **Request Interval** value (minimum: 1 minute).
2. **Increase Buffer Before**: Increase the **Buffer Before** value so CP Live starts checking earlier.

## WordPress Cron Issues

### Cron Not Running

CP Live depends on WordPress cron to check for live streams. If cron is not running:

1. **Check WP-Cron**: Install and activate the WP Crontrol plugin to verify that the `cp_live_check` event is scheduled.
2. **Alternative cron**: If your host disables WP-Cron, set up a system cron job to call `wp-cron.php` at your desired interval:

```
*/2 * * * * wget -q -O /dev/null https://yoursite.com/wp-cron.php
```

3. **Verify interval**: After saving settings, check that the cron event is rescheduled with the correct interval.

## YouTube-Specific Issues

### API Quota Exceeded

YouTube Data API has a daily quota. If you exhaust your quota:

1. Check your API usage in the [Google Cloud Console](https://console.cloud.google.com)
2. Increase the **Request Interval** in Advanced settings
3. Reduce buffer times to narrow the checking window
4. Request a quota increase from Google if needed

### Invalid API Key

1. Verify the key is copied correctly with no extra spaces
2. Confirm YouTube Data API v3 is enabled in your Google Cloud project
3. Check that API key restrictions allow your domain

## Resi-Specific Issues

### Stream URL Not Working

1. Confirm the URL ends in `Manifest.mpd`
2. Test the URL directly in your browser — it should return an XML response
3. Check that your server can make outbound HTTP requests to `control.resi.io`

## Display Issues

### Shortcode Shows Nothing

1. Verify at least one streaming service is enabled in **Advanced** settings
2. Check that a schedule is configured in **Main** settings
3. Confirm the `[cp-live]` shortcode is placed correctly on your page
4. Clear any page cache or CDN cache

### Countdown Not Displaying

1. Verify **When Not Live** is set to **Show countdown to the next stream** in **Main** settings
2. Check that at least one future schedule exists
3. Verify JavaScript is not blocked on your site

For additional help, see the [Getting Help](../faq/getting-help.md) guide.
