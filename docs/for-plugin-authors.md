# Adding your plugin to Minn Admin

Minn Admin renders third-party plugin data through **surfaces** — declarative descriptors
registered from PHP. One filter, no JavaScript, no build step. Minn draws your data with the same
list / tabs / detail-modal / action primitives that power its built-in views.

## Quick start

```php
add_filter( 'minn_admin_surfaces', function ( $surfaces ) {
    $surfaces['my-plugin'] = array(
        'label'      => 'Submissions',          // sidebar + page title
        'sub'        => 'My Plugin',            // small badge next to the title
        'icon'       => 'inbox',                // one of Minn's icon names
        'cap'        => 'manage_options',       // checked server-side before the surface is exposed
        'collection' => array(
            'route'     => 'my-plugin/v1/submissions',   // any REST route, called with cookie + nonce
            'pageQuery' => 'per_page=25&page={page}',    // {page} is filled in by Minn
            'itemsKey'  => 'items',   // key of the item array in the response (omit if the response IS the array)
            'totalKey'  => 'total',   // key of the total count (omit to use the X-WP-Total header)
            'columns'   => array(
                array( 'key' => 'title',  'label' => 'Title',  'format' => 'title' ),
                array( 'key' => 'email',  'label' => 'Email' ),
                array( 'key' => 'status', 'label' => 'Status', 'format' => 'pill' ),
                array( 'key' => 'date',   'label' => 'Date',   'format' => 'ago' ),
            ),
        ),
    );
    return $surfaces;
} );
```

That's a working, paginated, capability-gated view in the Minn sidebar.

## Descriptor reference

### Top level

| Key | Meaning |
|---|---|
| `label` | Sidebar label and page title |
| `sub` | Subtitle badge (usually your plugin name) |
| `icon` | Icon name from Minn's set: `inbox`, `send`, `doc`, `img`, `chat`, `cart`, `users`, `gear`, `plug`, `grid`, `list` |
| `cap` | Capability required. Checked server-side; the surface is absent from the app for users without it |
| `collection` | The list definition (below) |

### `collection`

| Key | Meaning |
|---|---|
| `route` | REST route for the list. May contain `{tab}` (replaced with the active tab value) |
| `allRoute` | Route used for the "All" tab when `route` contains `{tab}` |
| `query` | Extra query string appended to every request (sorting etc.) |
| `pageQuery` | Pagination template, default `per_page=25&page={page}`. Use your API's own style, e.g. Gravity Forms' `paging[page_size]=25&paging[current_page]={page}` |
| `itemsKey` / `totalKey` | Where items/total live in the response body. Omit both for standard WP collections (plain array + `X-WP-Total` header) |
| `tabs` | Either `{ "route": "...", "valueKey": "id", "labelKey": "title" }` to build tabs from a REST call, or `{ "param": "status", "static": [["sent","Sent"],["failed","Failed"]] }` for fixed tabs sent as a query param. `allLabel` names the first tab |
| `columns` | Array of `{ key, label, format }`. Formats: `title`, `text` (default), `pill`, `ago`, `mono`, `entry-summary` (first scalar values of numeric keys — useful for form entries) |
| `detail` | Detail modal config: `detailRoute` (fetch full item by `{id}`), `labels` (resolve field keys to human labels from another route), `messageKey` (render one field as a large text block), `skip` (keys to hide) |
| `actions` | Buttons in the detail modal: `{ label, method, route, body, confirm, danger }`. `{id}` in the route is replaced with the item id |

## No REST API? Ship a shim

If your data lives in custom tables, register a small read-only REST collection and point the
descriptor at it. Minn's bundled Gravity SMTP adapter
(`includes/adapters/gravity-smtp.php`) is the reference implementation — ~60 lines of SQL-to-REST
plus a descriptor. Rules of the road: check capabilities in `permission_callback`, use
`$wpdb->prepare`, and never `unserialize()` stored blobs (extract what you need with regex or
`json_decode`).

## Notes

- All requests are same-origin with the logged-in user's cookie + `X-WP-Nonce` — your existing
  REST permission checks keep working.
- Escape nothing yourself — Minn escapes every value it renders.
- Bundled adapters live in `includes/adapters/` and are guarded by `class_exists`/`defined`
  checks; PRs adding adapters for widely-used plugins are welcome.
