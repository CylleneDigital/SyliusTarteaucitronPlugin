# Upgrade

After any upgrade of the Composer package:

```bash
bin/console assets:install
bin/console doctrine:migrations:migrate -n
```

`assets:install` matters even without a note below: the vendored tarteaucitron.js and the plugin
stylesheet are served from the copy in `public/bundles/`, while their URLs carry a fingerprint of
the package files. Without it, browsers fetch the new URL and still get the old file.

If you override `templates/shop/tarteaucitron.html.twig` or the admin templates, compare them with
the new version of the plugin.

## 1.0.0

First public release: nothing to upgrade. Follow the installation steps of the
[README](README.md#installation).
