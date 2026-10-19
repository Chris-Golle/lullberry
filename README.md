# Lullberry

Custom code for www.lullberry.com (WooCommerce on Twenty Twenty-Five child theme).

Tracked: the child theme, print-sheet outputs, `sync-from-live.sh`, `push-all.sh`. All scripts live in the LL_scripts repo (`scripts-repo/`).
Not tracked: WP core, third-party plugins, uploads, SQL dumps, wp-config, secrets.

Block templates and page content live in the database (edit in Site Editor), not in this repo.

`./push-all.sh "message"` commits and pushes both repos in one step.

`./sync-from-live.sh` compares the live theme with local (add `--apply` to pull).
