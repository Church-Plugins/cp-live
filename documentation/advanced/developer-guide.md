---
title: "Developer Guide"
slug: developer-guide-cp-live
section: advanced-cp-live
order: 1
excerpt: "Hooks, filters, custom service creation, template overrides, and architecture reference for CP Live developers."
status: publish
---

# Developer Guide

This guide covers CP Live's architecture, hooks, filters, and extension points for developers who want to customize or extend the plugin's behavior.

## Architecture Overview

CP Live follows a service-based architecture. The core plugin manages scheduling and display, while individual service classes handle the specifics of each streaming provider.

### Key Components

- **Services System** (`includes/Services/`): Abstract `Service` base class with YouTube and Resi implementations
- **Schedule Manager** (`includes/_Init.php`): Manages cron-based schedule checking via `schedule_is_now()`
- **Settings** (`includes/Admin/Settings.php`): CMB2-based settings with tabbed interface
- **Integrations** (`includes/Integrations/`): CP Locations integration for per-location streams
- **Templates** (`templates/`): Frontend display templates for shortcodes

### Service Contexts

Each service operates in one of two contexts:

- **Global**: Settings stored as WordPress options (`cp_live_{service}_options`)
- **Location**: Settings stored as post meta on `cploc_location` posts (requires CP Locations)

## Available Hooks

### Actions

| Hook | Description | Parameters |
| --- | --- | --- |
| `cp_live_check` | Cron action that triggers live stream checks | None |
| `cp_live_service_set_live` | Fires when a service is set to live | `$service` (Service instance) |
| `cp_live_settings_advanced` | Add fields to the Advanced settings tab | `$cmb` (CMB2 box) |
| `cp_live_load_integrations` | Fires after integrations are loaded | None |

### Filters

| Filter | Description | Parameters |
| --- | --- | --- |
| `cp_live_is_live` | Filter the global live status | `$is_live` (bool) |
| `cp_live_get_live_embed` | Filter the embed output | `$embed` (string) |
| `cp_live_schedule_is_now` | Filter schedule matching result | `$schedule` (array or false) |
| `cp_live_available_services` | Modify the list of available services | `$services` (array) |
| `cp_live_active_services` | Modify the list of active services | `$services` (array) |
| `cp_live_service_is_live` | Filter a specific service's live status | `$is_live` (bool), `$service` (Service) |
| `cp_live_service_get` | Filter a service setting value | `$value`, `$key`, `$context`, `$service` |
| `cp_live_service_update` | Filter a service setting before save | `$value`, `$key`, `$context`, `$service` |
| `cp_live_settings_get` | Filter any settings value | `$value`, `$key`, `$group` |
| `cp_live_settings_update` | Filter any settings value before save | `$value`, `$key`, `$group` |
| `cp_live_body_class_is_live` | Customize the "is live" body class | `$class` (default: `cp-is-live`) |
| `cp_live_body_class_is_not_live` | Customize the "not live" body class | `$class` (default: `cp-not-live`) |
| `cp_live_video_location_id_default` | Filter the default location ID for video display | `$location_id` (int) |

## Creating a Custom Service

You can add a new streaming service by extending the `Service` base class and registering it via the `cp_live_available_services` filter.

### Step 1: Create the Service Class

```
namespace My_Plugin\Services;

use CP_Live\Services\Service;

class MyService extends Service {

    public $id = 'my_service';

    public function check() {
        // Query your streaming API
        // Call $this->set_live() if a stream is detected
        // Use $this->get() and $this->update() for settings
    }

    public function get_embed() {
        // Return the HTML embed for the live stream
        return '<iframe src="..."></iframe>';
    }

    public function settings( $cmb ) {
        $prefix = 'global' != $this->context ? $this->id . '_' : '';

        $cmb->add_field( [
            'name' => 'My Service API Key',
            'id'   => $prefix . 'api_key',
            'type' => 'text',
        ] );

        parent::settings( $cmb );
    }
}
```

### Step 2: Register the Service

```
add_filter( 'cp_live_available_services', function( $services ) {
    $services['my_service'] = [
        'label'   => 'My Service',
        'class'   => \My_Plugin\Services\MyService::class,
        'enabled' => 0,
    ];
    return $services;
} );
```

## Template Overrides

CP Live templates can be overridden in your theme. Copy the template file from `wp-content/plugins/cp-live/templates/` to `wp-content/themes/your-theme/cp-live/` and modify as needed.

Available templates:

- `shortcodes/live.php` — Main `[cp-live]` shortcode output
- `shortcodes/live-video.php` — Location-based `No live feeds were found` shortcode output
- `elements/countdown.php` — Countdown timer display

## Option Groups

CP Live stores settings in three WordPress option groups:

| Option Key | Contents |
| --- | --- |
| `cp_live_main_options` | Display mode, schedule configuration |
| `cp_live_{service}_options` | Per-service settings (e.g., `cp_live_youtube_options`) |
| `cp_live_advanced_options` | Cron interval, buffers, duration, service toggles |

## Additional Notes

### REST API

CP Live registers a REST API namespace at `cp-live/v1`. You can use this for custom integrations with external systems.

### Performance

- CP Live only makes API calls during scheduled windows, minimizing external requests
- Location data is cached using site transients (`cp_sites_to_check`)
- The cron interval controls how frequently checks run — balance detection speed against API quota
