#!/usr/bin/env bash
# Replaces the vendored tarteaucitron.js with an upstream tag, file set included: nobody picks
# by hand which files to copy (a missing lang/ or advertising file silently kills the banner).
#
# Usage: bin/update-tarteaucitron.sh <tag> [<expected-sha256>]
#   bin/update-tarteaucitron.sh v1.35.0
#   bin/update-tarteaucitron.sh v1.35.0 b383795e…   # refuses the archive if the checksum differs
#
# Needs bash, curl, tar and GNU sha256sum.
set -euo pipefail

if [[ $# -lt 1 || $# -gt 2 ]]; then
    echo "Usage: $0 <tag> [<expected-sha256>]" >&2
    exit 64
fi

tag="$1"
expected_sha="${2:-}"
version="${tag#v}"
repo="AmauriC/tarteaucitron.js"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
target="$root/public/tarteaucitron"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT

archive_url="https://codeload.github.com/$repo/tar.gz/refs/tags/$tag"
echo "Downloading $archive_url"
curl -fsSL -o "$work/archive.tar.gz" "$archive_url"

sha="$(sha256sum "$work/archive.tar.gz" | cut -d' ' -f1)"
if [[ -n "$expected_sha" && "$sha" != "$expected_sha" ]]; then
    echo "Checksum mismatch: expected $expected_sha, got $sha" >&2
    exit 1
fi

tar -xzf "$work/archive.tar.gz" -C "$work"
src="$(find "$work" -mindepth 1 -maxdepth 1 -type d)"

for required in tarteaucitron.min.js tarteaucitron.services.min.js advertising.min.js css/tarteaucitron.min.css lang LICENSE; do
    if [[ ! -e "$src/$required" ]]; then
        echo "Upstream $tag has no $required: the layout changed, update this script first." >&2
        exit 1
    fi
done

embedded="$(grep -o 'version:"[^"]*"' "$src/tarteaucitron.min.js" | head -n1 | cut -d'"' -f2)"
if [[ "$embedded" != "$version" ]]; then
    echo "tarteaucitron.min.js declares version \"$embedded\", tag is $tag." >&2
    exit 1
fi

rm -rf "$target/lang"
rm -f "$target"/tarteaucitron*.js "$target"/advertising*.js "$target/css/tarteaucitron.css" "$target/css/tarteaucitron.min.css"

cp "$src"/tarteaucitron.js "$src"/tarteaucitron.min.js \
   "$src"/tarteaucitron.services.js "$src"/tarteaucitron.services.min.js \
   "$src"/advertising.js "$src"/advertising.min.js \
   "$src"/LICENSE "$target/"
cp "$src"/css/tarteaucitron.css "$src"/css/tarteaucitron.min.css "$target/css/"
cp -R "$src/lang" "$target/lang"

printf '%s\n' "$version" > "$target/VERSION"
cat > "$target/SOURCE" <<EOF
repository: https://github.com/$repo
tag: $tag
archive: $archive_url
sha256: $sha
EOF

echo "tarteaucitron.js $version vendored (archive sha256 $sha)."
echo "Next: vendor/bin/phpunit --testsuite=unit (VendoredAssetsTest, VendorLibraryParityTest, VendorInitOptionsParityTest)."
