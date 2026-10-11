# Routing and negotiation

The plugin bootstrap enables `rss` and `atom` extensions. In `config/routes.php`, an explicit application route can also declare its extensions:

```php
$routes->setExtensions(['rss', 'atom']);
$routes->connect('/feeds', ['controller' => 'Feeds', 'action' => 'index']);
```

This gives `/feeds.rss` and `/feeds.atom` the same action. Your application chooses which feed data to provide. Do not overwrite an existing extension list without retaining the extensions your other routes need.

## Explicit view selection

The beginner example selects `Feed.Rss` directly. It always renders RSS, even if its URL has an Atom extension. To select by extension, check the request parameter and build the corresponding data:

```php
$isAtom = $this->getRequest()->getParam('_ext') === 'atom';
$this->viewBuilder()->setClassName($isAtom ? 'Feed.Atom' : 'Feed.Rss');
```

## Content negotiation

CakePHP also supports controller `viewClasses()`:

```php
public function viewClasses(): array {
    return [\Feed\View\RssView::class, \Feed\View\AtomView::class];
}
```

Requests can negotiate through their extension or `Accept` header. The two view classes expose their respective content types. Keep the action's data in the shape expected by the selected view, and preserve other view classes your controller already supports.

Set `App.fullBaseUrl` to your site's public origin when rendering feeds from a CLI or when request host information cannot represent the public URL. Both views resolve CakePHP URL arrays and relative links to absolute URLs through `Router::url(..., true)`.
