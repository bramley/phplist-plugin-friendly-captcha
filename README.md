# Friendly Captcha Plugin #

## Description ##

This plugin provides a captcha field on subscribe forms. See https://docs.friendlycaptcha.com/ for information on how Friendly Captcha works.

## Installation ##

### Dependencies ###

The plugin requires phplist version 3.3.0 or later and php version 5.4 or later.

The plugin also requires the php curl extension to be enabled and CommonPlugin to be enabled.
That plugin is now included with phplist so you should only need to enable it.

You must also create a site on friendlycaptcha.com, then enter the site key and the API key into the plugin's settings.

### Install through phplist ###

Install on the Manage Plugins page (menu Config > Plugins) using the package URL
`https://github.com/bramley/phplist-plugin-friendly-captcha/archive/main.zip`

### Usage ###

For guidance on configuring and using the plugin see the documentation page https://resources.phplist.com/plugin/friendly-captcha

## Version history ##

    version         Description
    1.0.1+20221231  Minor changes following change of ownership of the repository
                    Fixes #1
    1.0.0+20221223  First release based on hCaptcha plugin by Duncan Cameron