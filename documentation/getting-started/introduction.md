---
title: "Introduction to CP Live"
slug: introduction-cp-live
section: getting-started-cp-live
order: 2
excerpt: "CP Live automatically detects and displays live video feeds from YouTube and Resi on your church website using schedule-based detection."
status: publish
---

# Introduction to CP Live

CP Live is a WordPress plugin that automatically detects and displays live video feeds from streaming providers on your church website. It uses schedule-based detection to check for live streams only when you expect them, minimizing API calls and keeping your site efficient.

## What CP Live Does

CP Live monitors your streaming services and automatically switches your site to display the live feed when a broadcast begins. When no stream is active, it shows either the most recent video or a countdown to your next scheduled service.

Key capabilities include:

- **Automatic Live Detection**: Detects live streams from YouTube and Resi without manual intervention
- **Schedule-Based Checking**: Only checks for live streams during your configured service times, reducing API usage
- **Countdown Timer**: Displays a countdown to your next service when you are not live
- **Most Recent Video**: Shows the last detected video when no stream is active
- **Force Live Mode**: Automatically set your site to "live" at scheduled times, even without an active stream
- **Multi-Location Support**: Configure separate live streams for each location when used with CP Locations

## Supported Streaming Services

CP Live supports the following streaming providers:

- **YouTube**: Connects to the YouTube Data API to detect live or upcoming broadcasts from your channel
- **Resi**: Checks your Resi stream URL to detect active broadcasts

## Key Concepts

Understanding a few core concepts helps you get the most out of CP Live:

- **Schedules**: Define when your services happen (e.g., Sunday at 9:00 AM and 11:00 AM). CP Live only checks for live streams during these windows, saving API quota.
- **Buffers**: Time padding around your schedule. The **Buffer Before** (default: 8 minutes) starts checking early so the stream is detected before your service begins. The **Buffer After** (default: 12 minutes) keeps checking in case the stream starts late.
- **Live Video Duration**: How long CP Live continues to show the stream as live after detection (default: 6 hours). This prevents the site from flipping back to "not live" mid-service.
- **Force Live**: A per-schedule option that sets your site to "live" at the scheduled time regardless of whether a stream is detected from your provider.
- **Service Context**: Each streaming service can operate globally (site-wide settings) or per-location (when used with CP Locations), allowing different locations to use different providers or credentials.

## How It Works

CP Live uses WordPress cron to periodically check your streaming services for live broadcasts. The checking process follows this flow:

1. A cron job runs at a configurable interval (default: every 2 minutes)
2. CP Live checks if the current time falls within a scheduled service window (including configurable buffers)
3. If within a schedule window, it queries your enabled streaming services for active broadcasts
4. When a live stream is detected, your site automatically displays the live embed
5. After the configured live duration expires, the site returns to its not-live state

## Next Steps

Continue to [Installation](installation.md) to get CP Live set up on your site.
