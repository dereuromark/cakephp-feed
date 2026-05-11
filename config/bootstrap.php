<?php

use Cake\Http\MimeType;
use Cake\Routing\Router;

Router::extensions(['rss', 'atom']);

// Cake ships `application/rss+xml` as a built-in MIME mapping but not Atom.
// Register the `atom` shorthand so `$response->withType('atom')` (used in
// AtomView's constructor) and route extension dispatch both resolve.
MimeType::setMimeTypes('atom', 'application/atom+xml');
