<?php

use Cake\Http\Response;
use Cake\Routing\Router;

Router::extensions(['rss', 'atom']);

// Cake ships `application/rss+xml` as a built-in MIME mapping but not Atom.
// Register the `atom` shorthand so `$response->withType('atom')` (used in
// AtomView's constructor) and route extension dispatch both resolve. We go
// through Response::setTypeMap so this works on both Cake 5.1 and 5.2+;
// the dedicated MimeType class only landed in 5.2.
(new Response())->setTypeMap('atom', 'application/atom+xml');
