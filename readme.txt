=== WPVN - Thumbnailer ===
Contributors: Minh-Quan Tran (aka link2caro - a member of WordPressVN)
Donate link: http://link2caro.net/donate/
Tags: link2caro, wpvn, thumbnail, generator, thumbnail generator, thumbnailer, feature
Requires at least: 2.0
Tested up to: 2.9-rare
Stable tag: 0.6.7

This plugin helps you know how hooks are called and let you unload actions/filters (this is useful when you want to unload, not deactive, plugins based on user-agent, themes or whatever you want)

== Description ==

*This plugin is destined to intermediate users.*

It helps you find image that is attached to a post in this priority:
- Custom field (name can be one of these: img, image, thumbnail)
- Uploaded/Attached image

*NOTE*
If you have allow_url_fopen you can get image from an URL
Path for Custom Field to your uploaded files is: /year/month/image or /image  (depends on your settings for upload directory)

Custom fields use these sets
 * (img, img_title, img_alt, img_class) -> CSS: img-[img_class]
 * (image, image_title, image_alt, image_class) -> CSS: image-[image_class]
 * (thumbnail, thumbnail_title, thumbnail_alt, thumbnail_class) -> CSS: thumbnail-[image_class]
 * The value of img_class or image_class or thumbnail_class is case non-sensitve (Ex: CSSClass will be cssclass)
 * case sensitive and non-permutable

*Example*

To be used best in the Loop
caro_the_image($post->ID); shows the custom-size image.
caro_get_the_image($post->ID); returns the custom-size image HTML code.
caro_the_image_url($post->ID); shows the URL of the custom-size image.
caro_get_the_image_url($post->ID); returns the URL of the custom-size image.

== Installation ==

1. Search for WPVN or Thumbnailer in Plugin Installer and install this plugin.
2. Activate the plugin through the 'Plugins' menu in WordPress

[More about WPVN - Thumbnailer](http://link2caro.net/read/wpvn-thumbnailer/ "WPVN - Thumbnailer ")