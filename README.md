# Church Plugins Live
Auto embed livestreams into your site

##### First-time installation  #####

- Copy or clone the code into `wp-content/plugins/cp-live/`
- Run these commands
```
composer install
npm install
cd app
npm install
npm run build
```

##### Dev updates  #####

- There is currently no watcher that will update the React app in the WordPress context, so changes are executed through `npm run build` which can be run from either the `cp-live`

### Change Log

#### 1.0.4
* Allow for manually setting the site to live
* If multiple YouTube broadcasts are detected. Use the one that starts next.

#### 1.0.3
* Add control to force a schedule to go live even if no broadcast is detected.
* Prevent extra API calls on YouTube when broadcast is already live.

#### 1.0.2
* Fix bug that was causing a service check every cron, even when there wasn't a schedule set.

#### 1.0.1
* Update Church Plugins core

#### 1.0.0
* Initial release
