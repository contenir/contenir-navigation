# contenir/contenir-navigation

[![Continuous Integration](https://github.com/contenir/contenir-navigation/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-navigation/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-navigation/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-navigation)

Managed site navigation for [Contenir](https://github.com/contenir) CMS. A site has any number of menus, such as
header and footer, which editors build and publish instead of the menus being derived from the page tree.

- **Menus** hold nested items. An item links to a record, such as a page or a news article, or to an address, or it is
  text alone, which may hold a little HTML.
- **Links** are read from what an editor types: a path, a full link, a fragment, an email address or a phone number,
  with no mailto: or tel: to type. Addresses that run code, such as `javascript:`, are refused.
- **Drafts and revisions**: editors change a menu's items freely. Publishing writes the whole tree into a revision and
  makes it live, so the site only ever reads a published menu. Changes can be discarded, and any revision restored.
- **Drawing**: each item has a label, an icon token or both, a new-tab switch, extra classes and an active switch. Each
  site maps icon tokens to its own icons.

It is framework free: storage is [php-db](https://github.com/php-db/phpdb) on MySQL or SQLite, time is a PSR-20 clock,
and the services suit any PSR-11 container that reads Laminas-style configuration. The CMS edits menus through the
`contenir-extension/navigation` add-on, and a site renders them through an adapter such as
`contenir/contenir-navigation-mezzio`, which turns record items into URLs with the site's own routes.

## Requirements

- PHP 8.3, 8.4 or 8.5, with the DOM, JSON and mbstring extensions
- php-db/phpdb 0.6 with its MySQL or SQLite driver
- peptolab/phpdb-migration 0.2, to create the tables

## Install

```bash
composer require contenir/contenir-navigation
```

php-db 0.6 has no stable release yet, so the site's `composer.json` needs `"minimum-stability": "dev"` with
`"prefer-stable": true`, or `"php-db/phpdb": "0.6.x-dev@dev"` in its own `require`.

### Tables

The migrations in `schema/` create three tables in the site's database. `ConfigProvider` lists them under
`contenir.schema_migrations` for the host's migration runner.

| Table | Holds |
|---|---|
| `navigation` | One row per menu: its handle, title, description, order, live revision and when its draft last changed |
| `navigation_item` | One row per item of a menu's draft: its parent, position, link, label, HTML, drawing and active switch |
| `navigation_revision` | One row per publish: the whole item tree as JSON, a note, when and by whom |

## Usage

`Storage\PhpDbNavigationRepository` keeps menus and their items, and `Publishing\Publisher` publishes them:

```php
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;

$menu = $repository->saveNavigation(new Navigation('header', 'Header', 'Across the top of every page'));

$about = $repository->saveItem(Item::record(new RecordTarget('page', 12))->withPlacement($menu->id, null, 0));
$repository->saveItem(Item::url(Url::parse('hello@example.com'), 'Email us')->withPlacement($menu->id, $about->id, 0));
$repository->saveItem(Item::text('Hours', '<strong>Open</strong> daily')->withPlacement($menu->id, null, 1));

$publisher->unpublishedChanges($menu->id);              // 3
$publisher->publish($menu->id, $userId, 'First menu');  // a Menu\Revision, now live
```

The site reads only the live revision:

```php
$live = $publisher->live('header');                     // null until the menu is published
foreach ($live?->nodes() ?? [] as $node) {
    // $node->item, and $node->children nested under it
}

$repository->liveRevisionId('header');                  // one small query, for keying a cache
```

`discard()` returns a draft to its live revision, and `restore()` copies an older revision into the draft to publish
again. Moving and deleting items go through the repository (`moveItem()`, `deleteItem()`); deleting an item deletes
what is nested under it, never the record it links to.

### Links

`Link\Url::parse()` reads an address as an editor typed it:

| Typed | Held as | Kind |
|---|---|---|
| `/about/our-team?tab=2#staff` | as typed | `Path` |
| `https://shop.example.com`, `shop.example.com` | `https://shop.example.com` | `External`, suggesting a new tab |
| `#booking` | as typed | `Fragment`, a spot on the page the menu is on |
| `hello@example.com` | `mailto:hello@example.com` | `Email` |
| `03 5555 1234` | `tel:0355551234` | `Phone` |

`display()` shows an email address or a phone number without its prefix again. Anything else is refused with an
`Exception\InvalidLinkException` saying why, including any scheme but http, https, mailto and tel.

### Text items

A text item's HTML is sanitised as it is saved, by `Html\HtmlSanitiser`: by default it keeps `strong`, `em`, `b`, `i`,
`span` with a class, `br`, and `a` with an `href`, `title` and `target`. Other elements are unwrapped, keeping their
text, while `script`, `style`, `iframe` and the like go with everything in them. A link's `href` must be one a menu
item could hold, and one opening a new tab is given `rel="noopener noreferrer"`.

### Transactions

Changes that touch several rows, and publishing, discarding and restoring, each run in one transaction. When one is
already open on the connection, whether php-db opened it or something sharing its PDO did, such as the CMS saving a
record, the work joins it, so a new page and the menu item linking to it are saved together or not at all.

## Configuration

`ConfigProvider` registers the repository, the publisher and the sanitiser. Its settings live under
`contenir_navigation`:

| Setting | Default | |
|---|---|---|
| `adapter` | `PhpDb\Adapter\AdapterInterface` | The container entry for the site's database |
| `clock` | `Psr\Clock\ClockInterface` | The container entry for a clock, or the system clock in UTC when there is none |
| `html` | `HtmlSanitiser::INLINE` | The elements a text item's HTML may hold, each with the attributes it may keep |

## Development

```bash
composer check               # format, lint, static analysis, both suites and mutation testing
composer test                # the unit suite
composer test-integration    # the integration suite on SQLite, then on MySQL
```

The MySQL run reads its connection from `CONTENIR_TEST_DB_HOST`, `_PORT`, `_NAME`, `_USER` and `_PASSWORD`, and skips
when MySQL can't be reached.
