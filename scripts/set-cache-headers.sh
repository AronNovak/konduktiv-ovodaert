#!/usr/bin/env bash
#
# Give static assets a cache lifetime on the home.pl server.
#
# Run after every deploy, and after any manual `drush cache:rebuild` on the
# server — a cache rebuild deletes everything in files/css and files/js,
# including the .htaccess files this writes there.
#
# Why this is done per directory rather than once in web/.htaccess:
# home.pl serves through IdeaWebServer, not Apache. It honours `Header set`
# from mod_headers and matches <IfModule>, but silently ignores mod_expires
# (which is why Drupal's own ExpiresDefault block never took effect), and it
# also ignores <FilesMatch> and `env=` conditions — a `Header set` applies to
# every response in scope regardless of them. That leaves the containing
# directory as the only reliable scope, so these files may only ever be placed
# in directories that hold nothing but static assets. Putting the same line in
# web/.htaccess would attach a long cache lifetime to every HTML response.
#
# Theme assets under themes/custom/server_theme/dist are covered separately:
# that .htaccess is emitted by the theme build (scripts/build.js) and ships
# with the rsync, so it needs nothing here.
#
# Optional env vars (defaults shown):
#   NOVAAK_SSH=tarhely
#   NOVAAK_TARGET=/home/serwer1365505/public_html/novaak.net/novaak.net/konduktiv_ovodaert

set -euo pipefail

NOVAAK_SSH="${NOVAAK_SSH:-tarhely}"
NOVAAK_TARGET="${NOVAAK_TARGET:-/home/serwer1365505/public_html/novaak.net/novaak.net/konduktiv_ovodaert}"

# Two weeks. Long enough to spare repeat visitors a re-download, short enough
# that a replaced image or font recovers on its own — the paths under styles/
# are stable, so there is no cache-busting rename to rely on.
MAX_AGE=1209600

FILES_DIR="$NOVAAK_TARGET/web/sites/default/files"

echo "==> Target: $NOVAAK_SSH:$FILES_DIR"

ssh "$NOVAAK_SSH" "bash -s" <<EOF
set -euo pipefail
for dir in css js styles; do
  target="$FILES_DIR/\$dir"
  if [ ! -d "\$target" ]; then
    echo "  skip  \$dir (not present yet)"
    continue
  fi
  cat > "\$target/.htaccess" <<'HTEOF'
# Written by scripts/set-cache-headers.sh - do not edit.
#
# mod_headers, because this host ignores mod_expires. Scope is the directory:
# FilesMatch and env= are ignored here, so this must only sit alongside static
# assets.
Header set Cache-Control "max-age=${MAX_AGE}, public"
HTEOF
  echo "  wrote \$dir/.htaccess"
done
EOF

echo "==> Done. Verify with:"
echo "    curl -sI https://petoovodaert.hu/sites/default/files/css/<file>.css | grep -i cache-control"
