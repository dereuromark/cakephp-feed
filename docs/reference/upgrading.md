# Upgrading

Composer constraints define the supported PHP and CakePHP versions. Use the [version map](https://github.com/dereuromark/cakephp-feed/wiki#cakephp-version-map) when upgrading from older framework branches.

Use the view builder's `serialize` option rather than an `_serialize` view variable. Choose `Feed.Rss` or `Feed.Atom` explicitly, or use CakePHP's `viewClasses()` negotiation API. Old `rss => Feed.Rss` mapping examples are not the current controller API.

The unused RSS `subDir` property remains deprecated for compatibility. It does not select a template directory. Prefer template-free serialization and avoid depending on this property.

Native immutable dates are now accepted by RSS, and CDATA content containing `]]>` remains valid XML. Atom title text constructs use the same HTML handling as other text constructs; XHTML is rejected consistently.

Check your feed's required fields, stable IDs, absolute links, MIME type, and enclosure metadata after updating. RSS and Atom serializers do not replace format validation or the application's access rules.
