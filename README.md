# Social Link Previews for WordPress

A bare bones WordPress plugin that generates social cards meta data for articles. For sites like Twitter, Facebook, LinkedIn, etc to create pretty sharing previews.


## Usage

Install by dropping the `social-link-previews` folder in your sites `wp-content/plugins` folder. Activate the plugin from WP Admin.

The plugin will use the **Primary Image** of a post for the image. You can provide a default primary image from the plugins settings.

The plugin adds a custom image size of 1200x628 for the sharing image. You will need to regenerate the image sizes on your site, with **wp-cli** like so:

```bash
$ wp media regenerate
```