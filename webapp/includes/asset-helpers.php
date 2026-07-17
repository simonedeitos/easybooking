<?php
// asset-helpers.php – cache-busting helper for local asset files.
//
// Usage example:
// require_once __DIR__ . '/includes/asset-helpers.php';
// then append ?v=<filemtime> to local assets, e.g. assets/js/cloud.js?v=1234567890
//
// Returns the file modification time (filemtime) as a version string so that
// browsers and CDN/reverse-proxies always fetch the latest file after a
// deployment, not a stale cached copy.
//
// $rel must be a hard-coded path relative to the webapp root directory
// (e.g. 'assets/js/main.js').  It is never user-supplied.
$getAssetVersion = function(string $rel): string {
    // Resolve the webapp root regardless of which file includes this helper.
    $webappRoot = realpath(__DIR__ . '/..');
    $base       = $webappRoot !== false ? realpath($webappRoot . '/assets') : false;
    $abs        = $webappRoot !== false ? realpath($webappRoot . '/' . $rel)  : false;
    // Reject any path that escapes the assets/ directory.
    if ($abs === false || $base === false) {
        return '1';
    }
    $basePath = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (substr($abs, 0, strlen($basePath)) !== $basePath) {
        return '1';
    }
    return (string)filemtime($abs);
};
