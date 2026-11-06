#!/usr/bin/env bash
# Throwaway WordPress with Twenty Nineteen and this theme; checks for 1.11.0 (lazy cover photos,
# https rewrite, footnote colours in Deluminator's dark mode, pager link names on phones).
set -euo pipefail
HERE=$(cd "$(dirname "$0")" && pwd)
REPO=$(cd "$HERE/../.." && pwd)
OUT=/tmp/img-out; mkdir -p "$OUT"
W=/tmp/wp; mkdir -p "$W"; cd "$W"
wp core download --quiet
wp config create --dbname=wp --dbuser=root --dbpass=root --dbhost=127.0.0.1 --skip-check --quiet
wp core install --url=http://127.0.0.1:8080 --title=Test --admin_user=admin --admin_password="$(openssl rand -hex 16)" --admin_email=admin@example.test --skip-email --quiet
wp option update permalink_structure '/%postname%/' --quiet && wp rewrite flush --quiet
wp option update posts_per_page 3 --quiet
wp theme install twentynineteen --quiet
rsync -a --exclude .git --exclude tests "$REPO/" wp-content/themes/aardling/
wp theme activate aardling --quiet
echo "WordPress $(wp core version), PHP $(php -r 'echo PHP_VERSION;'), theme $(wp theme get aardling --field=version)"
mkdir -p wp-content/uploads
for n in 1 2 3 4; do php -r '$w=1600;$h=1000;$i=imagecreatetruecolor($w,$h);mt_srand((int)$argv[2]);for($y=0;$y<$h;$y+=4)for($x=0;$x<$w;$x+=4)imagefilledrectangle($i,$x,$y,$x+3,$y+3,imagecolorallocate($i,mt_rand(0,255),mt_rand(0,255),mt_rand(0,255)));imagejpeg($i,$argv[1],80);' "wp-content/uploads/cover-$n.jpg" "$n"; done
ls -la wp-content/uploads | head
wp --user=admin post create "$HERE/covers.html" --post_title="Covers" --post_name=covers --post_status=publish --porcelain > /dev/null
wp --user=admin post create "$HERE/notes.html" --post_title="Notes" --post_name=notes --post_status=publish --porcelain > /dev/null
printf '%s\n' '<!-- wp:paragraph -->' '<p>Een pagina met een foto.</p>' '<!-- /wp:paragraph -->' '' '<!-- wp:image -->' '<figure class="wp-block-image"><img src="http://127.0.0.1:8080/wp-content/uploads/cover-1.jpg" alt="Foto"/></figure>' '<!-- /wp:image -->' > /tmp/ga.html
wp --user=admin post create /tmp/ga.html --post_title="GA" --post_name=ga --post_status=publish --porcelain > /dev/null
# ?cfloader=1 stands in for Cloudflare's Google tag gateway, which puts its own loader at the top of <head>.
mkdir -p wp-content/mu-plugins
printf '%s\n' '<?php' 'add_action( "wp_head", function () { if ( isset( $_GET["cfloader"] ) ) { echo "<script async src=\"/ptp3/\"></script>\n"; } }, 0 );' > wp-content/mu-plugins/cf-loader.php
for i in 1 2 3 4 5 6 7; do wp post create --post_title="Vulling $i" --post_content="Tekst $i" --post_status=publish --quiet; done
echo "== preload taxonomies"
wp eval '
$default = array( "post_tag" => "tag", "category" => "category" );
$a = apply_filters( "wp_cache_preload_taxonomies", $default );
echo $a === $default ? "ok   without the W taxonomies WP Super Cache keeps its own list\n" : "FAIL list without W taxonomies: " . json_encode( $a ) . "\n";
foreach ( array( "wie", "wat", "waar", "waardoor", "waarmee", "wanneer", "hoe" ) as $t ) { register_taxonomy( $t, "post", array( "public" => true ) ); }
$b = apply_filters( "wp_cache_preload_taxonomies", $default );
echo array_keys( $b ) === array( "category", "wie", "wat", "waar", "waardoor", "waarmee", "wanneer", "hoe" ) ? "ok   categories and the seven W taxonomies, no tags\n" : "FAIL list: " . json_encode( $b ) . "\n";
' | tee "$OUT/preload.txt"
grep -q FAIL "$OUT/preload.txt" && exit 1
echo "== https rewrite"
wp eval '
add_filter( "home_url", function () { return "https://aardling.com"; } );
$in  = "<img src=\"http://aardling.com/wp-content/uploads/a.jpg\"> <a href=\"http://www.aardling.com/x/\">x</a> url(http://aardling.com/b.jpg) http://aardling.community/ http://example.com/c.jpg";
$out = aardling_theme_https_content( $in );
$want = "<img src=\"https://aardling.com/wp-content/uploads/a.jpg\"> <a href=\"https://aardling.com/x/\">x</a> url(https://aardling.com/b.jpg) http://aardling.community/ http://example.com/c.jpg";
echo $out === $want ? "ok   own http addresses become https, others stay\n" : "FAIL https rewrite: $out\n";
remove_all_filters( "home_url" );
echo aardling_theme_https_content( "<img src=\"http://aardling.com/a.jpg\">" ) === "<img src=\"http://aardling.com/a.jpg\">" ? "ok   nothing changes on an http site\n" : "FAIL rewrite on http site\n";
' | tee "$OUT/https.txt"
grep -q FAIL "$OUT/https.txt" && exit 1
PHP_CLI_SERVER_WORKERS=4 nohup wp server --host=127.0.0.1 --port=8080 > "$OUT/server.log" 2>&1 &
for i in $(seq 1 30); do curl -sf -o /dev/null http://127.0.0.1:8080/ && break; sleep 1; done
curl -s http://127.0.0.1:8080/covers/ > "$OUT/covers.html"
grep -c 'aardling-lazy-bg' "$OUT/covers.html" | sed 's/^/     lazy markers in the HTML: /'
if grep -q 'background-image:url(' "$OUT/covers.html"; then echo "FAIL a cover photo is still a plain background"; exit 1; else echo "ok   no cover photo left as a plain background"; fi
mkdir -p /tmp/node && cd /tmp/node && npm init -y >/dev/null && npm i -s puppeteer-core@23 >/dev/null && cp "$HERE/browser.mjs" /tmp/node/
node /tmp/node/browser.mjs
if grep -iE 'PHP (Fatal|Warning|Deprecated|Notice)' "$OUT/server.log" | grep -qv 'JIT'; then grep -iE 'PHP (Fatal|Warning|Deprecated|Notice)' "$OUT/server.log" | head; echo "FAIL PHP warnings"; exit 1; fi
echo "ALL DONE"
