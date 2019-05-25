=== Custom Post Types Block ===
Contributors: ronalfy, chrislogan
Tags: custom post types, archive, mapping, gutenberg, block, taxonomy, terms
Requires at least: 5.0
Tested up to: 5.2
Stable tag: 2.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://mediaron.com/give/

A WordPress plugin for mapping post type archives to pages with a Gutenberb block for showing recent posts.

== Description ==

A WordPress plugin for mapping post type archives to pages. Plays well with Gutenberg and Gutenberg is highly recommended for use with this plugin.

Ensure your post types have <code>has_archive</code> set to true.

The plugin has a new Gutenberg block based on Atomic Blocks that will allow you to select which posts to display on your archive page. It even supports pagination.

See below for an overview of how Post Type Archive Mapping works:

https://www.youtube.com/watch?v=QT0QIdTBrdk?rel=0

<ul>
<li>Select a Public page to use as your post type archive page.</li>
<li>View the archive and you will see the page content instead of the archive content.</li>
<li>Use page templates on your pages for flexibility.</li>
<li>Custom Gutenberg block for showing your posts.</li>
</ul>

<h2>Gutenberg Block</h2>

This 5-minute video covers how the Gutenberg block works.

https://www.youtube.com/watch?v=Kozrc-1rSJY&rel=0


== Installation ==

1. Just unzip and upload the "post-type-archive-mapping" folder to your '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a custom post type that has an archive
4. Create a page with a custom template
5. Go to Settings->Reading and assign the page to the custom post type archive
6. Go to the post type archive and observe the page content is now the archive

== Frequently Asked Questions ==

= I don't see the post types. What's wrong? =

Make sure your post type has <code>has_archive</code> set to true.

= Can you do posts instead of pages? =

No, this plugin only works with pages.

== Screenshots ==

1. Editing a page using Gutenberg
2. The resulting archive page
3. Settings->Reading option

== Changelog ==

= 2.0.7 =
* Released 2019-05-24
* Added the ability to trim the excerpt length.
* Reduce the file size of the Gutenberg block script using a new build technique.

= 2.0.5 =
* Released 2019-04-21
* Conditional term filtering

= 2.0.4 =
* Released 2019-04-18
* Adding support for six columns
* Fixing undefined index error

= 2.0.3 =
* Released 2019-03-29
* Fixed term not being saved when displaying posts

= 2.0.1 =
* Released 2019-01-17
* Fixed pagination when using a page with just the block

= 2.0.1 =
* Released 2019-01-17
* Fixing bug where arguments weren't an array when switching reading types in Settings->Reading.

= 2.0.0 =
* Released 2019-01-06
* Numerous enhancements to the Gutenberg block including showing taxonomies, setting the image type (Avatar vs Regular), setting where the featured image is displayed, selecting the image size, and much more.

= 1.0.1 =
* Released 2018-11-07
* WordPress 5.0 compatibility

= 1.0.0 =
* Released 2018-09-24
* Initial release.

== Upgrade Notice ==

= 2.0.7 =
Added the ability to trim the excerpt length. Reduce the file size of the Gutenberg block script using a new build technique.

= 2.0.5 =
Conditional term filtering

= 2.0.4 =
Adding support for six columns. Fixing undefined index error.
