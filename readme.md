# Easy Digital Download Version History

This is an [Easy Digital Downloads](https://wpfusion.com/go/easy-digital-downloads) addon which allows you to link to previous versions of a plugin, based on tagged git releases.

It requires the [EDD Git Download Updater](https://easydigitaldownloads.com/downloads/git-download-updater/?ref=4978) plugin.

<img width="1144" alt="image" src="https://github.com/verygoodplugins/edd-version-history/assets/13076544/d5585ac7-4644-490c-a6d3-39bdb536cc4d">


## How it works

* When a version download link is clicked, the plugin will first check your EDD uploads folder to see if a matching file already exists
* If not, it will connect to either Bitbucket or GitHub (depending on your configuration) and download the tagged .zip file to the uploads directory
* The .zip file will be served to the user via a secure EDD download link

## Shortcode parameters

The shortcode is `[edd_version_history]`. It accepts the following parameters:

* `download_id`: (required) The download ID
* `limit`: The number of tags to show (default `10`)
* `include_current`: Whether or not to include the current plugin version in the list. Default `false`

## Changelog

### 1.0.0 on January 13th, 2024

- Initial release

--------------------

## Installation

1. Download the [latest tagged release](https://github.com/verygoodplugins/edd-version-history/tags).

2. Navigate to Plugins » Add New in the WordPress admin and upload the file.

3. Activate the plugin.

4. Add the shortcode to a page, following the instructions above.
