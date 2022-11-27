# jsondb
Read / write JSON and mixed data to SQL database

Converting mixed data to and from breadcrumb lists in a database.

A mixed structure like:
```php
$mixed = [
    "display" => [
        "header" => [
          "title" =>    "Title",
        ],
        "main" => [
          "title" =>    "Main title",
          "button" =>   "Button"
        ],
        "footer" => [
          "title" =>     "Footer"
        ]
    ]
];
```
Could be stored in a database like:
```
+----------+----------+----------------------+------------+
| section  | language |         key          |   value    |
+----------+----------+----------------------+------------+
| mixed    | en       | display:footer:title | Title      |
| mixed    | en       | display:header:title | Main title |
| mixed    | en       | display:main:button  | Button     |
| mixed    | en       | display:main:title   | Footer     |
+----------+----------+----------------------+------------+
```
using a breadcrump path to each value as a path.

