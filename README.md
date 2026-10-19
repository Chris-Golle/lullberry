# Lullberry

Custom code for www.lullberry.com (WooCommerce on Twenty Twenty-Five child theme).

Tracked: `app/public/wp-content/themes/twentytwentyfive-child/`, `print-sheets/`, build/audit scripts.
Not tracked: WP core, third-party plugins, uploads, SQL dumps, wp-config, secrets.

Block templates and page content live in the database (edit in Site Editor), not in this repo.

`./sync-from-live.sh` compares the live theme with local (add `--apply` to pull).
