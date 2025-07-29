### Get feed content:
```php
<?php

$feedTemplate = new FeedTemplate($name, $company, $url, $categories, $offers);
$feed = $feedTemplate->content('main');

```

### Generate feed and save:
If Laravel, then save Storage helper
```php
<?php

GenerateFeed::run(
    $name,
    $company,
    $url,
    $categories,
    $offers,
    $methodName,
    $putPath
);

```
