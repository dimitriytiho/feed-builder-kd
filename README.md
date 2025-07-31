### Get feed content:
```php
<?php

// Example data, is managed from array
$name = 'Feed Php';
$company = 'Php';
$url = 'https://php.ru';
$categories = [
    [
        'id' => 1,
        'name' => 'cat1',
    ],
    [
        'id' => 2,
        'name' => 'cat2',
        'parent_id' => 1
     ],
];
$offers = [
    [
        // Offer
        'id' => 77,
        'attrs' => ['available' => true],
        'attrsOnlyKey' => ['name'],
        // Tags
        'tags' => [
            [
                'tag' => 'barcode',
                'value' => '123',
                'attrs' => ['one' => 1],
                'attrsOnlyKey' => ['data_name'],
                'end' => true,
                'endSlash' => false,
            ],
            [
                'tag' => 'price',
                'value' => '777',
            ],
        ],
        // Params
        'params' => [
            [
                'name' => 'weight',
                'value' => '11',
                'unit' => 'kg',
                'attrs' => ['two' => 2],
                'attrsOnlyKey' => ['data_name'],
            ],
            [
                'name' => 'height',
                'value' => '11',
                'unit' => 'kg',
            ],
        ],
    ],
];

// Get content feed
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
