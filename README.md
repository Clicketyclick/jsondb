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

## Example

Runìng `php example_jsondb.php` should give:

```console
Example for jsondb.php
// Save structure in database
// Read data as array/mix
Array
(
    [display] => Array
        (
            [footer] => Array
                (
                    [title] => Footer
                )

            [header] => Array
                (
                    [title] => Title
                )

            [main] => Array
                (
                    [button] => Button
                    [title] => Main title
                )

        )

)
// Read data as breadcrumb/value list
Array
(
    [display:footer:title] => Footer
    [display:header:title] => Title
    [display:main:button] => Button
    [display:main:title] => Main title
)
```

and a database `json.db` with:

```
sqlite> select * from jtable;
+---------+----------+----------------------+------------+
| section | language |         key          |   value    |
+---------+----------+----------------------+------------+
| mix     | en       | display:header:title | Title      |
| mix     | en       | display:main:title   | Main title |
| mix     | en       | display:main:button  | Button     |
| mix     | en       | display:footer:title | Footer     |
+---------+----------+----------------------+------------+
```
