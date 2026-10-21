=== Remove Category URL - Remove 'category' base from category permalinks ===
Contributors: themeisle
Tags: permalinks, category base, slug, taxonomy, seo
Requires at least: 3.1
Tested up to: 7.1
Stable tag: 1.2.5
License: GPLv2
Donate link:

Remove Category URL strips the /category/ base from your category URLs, turning something like /category/my-category/ into simply /my-category/.

== Description ==

Remove Category URL strips the `/category/` base from your category URLs, turning something like `/category/my-category/` into simply `/my-category/`.

Just activate, and you're done. No configuration needed.

### Features
- Creates cleaner URLs like `mydomain.com/my-category/` and `mydomain.com/my-category/my-post/`
- Works out of the box with no settings to configure
- Supports multiple sub-categories
- Automatically 301 redirects old category URLs to the new structure (SEO-friendly)
- Sitemaps are automatically updated with the new URLs after activation
- Compatible with WordPress Multisite, WPML, and popular sitemap plugins

### Why remove /category/ from URLs?

The `/category/` base in WordPress URLs is there by default, but it doesn't help your visitors or search engines understand your content any better. When you remove `/category/` from URLs, you get cleaner paths like `mydomain.com/news/` instead of `mydomain.com/category/news/`. They're shorter, easier to remember and share, and more consistent with how your pages are already structured.

Shorter URLs also tend to look better in search results and make your site feel more polished overall. Removing the category base is a small change that makes your whole URL structure cleaner.

### Support

We’re here to help. Feel free to open a new thread on the [Support Forum](https://wordpress.org/support/plugin/remove-category-url/).

### Useful Resources

- If you like this plugin, you’re sure to love [our other plugins](https://themeisle.com/wordpress-plugins/) as well.
- Our blog is a great place to [learn more about WordPress](https://themeisle.com/blog/).
- Get the most out of your website with our helpful [WordPress YouTube Tutorials](https://youtube.com/playlist?list=PLmRasCVwuvpSep2MOsIoE0ncO9JE3FcKP).

== Installation ==

1. In your WordPress admin, go to **Plugins > Add New**
2. In the Search field, type **"Remove Category URL"**
3. Under "Remove Category URL" by Themeisle, click the **Install Now** link
4. Once the process is complete, click the **Activate Plugin** link

You're done! No configuration is needed.

== Frequently Asked Questions ==

= Will this break my existing links? =

No. The plugin automatically redirects your old category URLs to the new shorter ones using 301 redirects. So if someone visits `mydomain.com/category/my-category/`, they'll be seamlessly redirected to `mydomain.com/my-category/`.

= What happens if a page or other content has the same slug as a category? =

The category will take priority. So if you have both a category and a page with the slug travel, visiting `mydomain.com/travel/` will load the category archive, not the page. To avoid conflicts, make sure your categories don't share slugs with pages, posts, or other content on your site.

= Does this remove the base from other taxonomies like WooCommerce product categories? =

No, this plugin only affects the built-in WordPress category taxonomy. For WooCommerce, you'd need a different solution.

= What happens if I deactivate the plugin? =

If you deactivate the plugin, your category URLs will revert to the default WordPress structure with `/category/` in the path.

If you only deactivate the plugin without uninstalling it, you will need to flush permalinks manually. You can do this by going to **Settings > Permalinks** and clicking **Save Changes**.

If you uninstall the plugin, permalinks are flushed automatically.

Note that after deactivation, links pointing to the shortened URLs will no longer work unless you set up your own redirects.

= I uninstalled the plugin, but /category/ didn't come back. Why? =

You'll need to manually refresh your permalinks. Go to **Settings → Permalinks** and click **Save Changes**. This will restore the default `/category/` base.

== Changelog ==

#####   Version 1.2.5 (2026-10-19)

- Updated dependencies




#####   Version 1.2.4 (2026-07-29)

- Updated dependencies




#####   Version 1.2.3 (2026-05-25)

- Updated dependencies




#####   Version 1.2.2 (2026-04-09)

- Updated dependencies




#####   Version 1.2.1 (2026-01-12)

- Improved experience by flushing permalinks on uninstall
- Enhanced security




####   Version 1.2.0 (2025-11-06)

Remove Category URL plugin has been acquired by Themeisle 🎉
We’re happy to announce that Themeisle is now the new owner of Remove Category URL. This acquisition will help ensure the plugin’s continued development, better support, and exciting new updates in the future.

Your existing setup will continue to work as usual — no action is required on your part.



= 1.1.6 =
* Fixed: Minor bugs

= 1.1.4 =
* Update Fix WPML

= 1.1.3 =
* Update Fix

= 1.1.2 =
* Update

= 1.1.1 =
* Compatible with translate.wordpress.org

= 1.1 =
* Fix Erros

= 1.0.2 =
* Update Compatible with WPML.

= 0.1.1 =
* Add uninstall.

= 0.1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.1 =
* Compatible with translate.wordpress.org

= 1.0.2 =
* Update Compatible with WPML.

= 0.1.1 =
* Add uninstall.

= 0.1.0 =
* Initial release.
