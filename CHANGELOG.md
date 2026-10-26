# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

Menus may share a handle, so an editor can rename or copy a menu without being refused.

### Changed

- A menu's handle need not be unique. The migration `Version20261026000100ShareNavigationHandles` replaces the unique
  index on `navigation.handle` with a plain `navigation_lookup` index, and `saveNavigation()` no longer throws when
  another menu has the handle.
- `navigationByHandle()` and `liveRevisionId()` answer the menu the site shows for a handle: the first published menu
  with it, in menu order (sequence, then title). A draft never takes over. With none published, `navigationByHandle()`
  answers the first in menu order and `liveRevisionId()` null.

### Added

- `NavigationRepositoryInterface::navigationsByHandle()` lists every menu with a handle, in menu order, so the CMS can
  warn when a handle is shared. Implementations must add it.

## [0.1.0] - 2026-10-25

### Added

- Menus in a `navigation` table, their draft items in `navigation_item` and their published revisions in
  `navigation_revision`, on MySQL or SQLite, created by peptolab/phpdb-migration migrations in `schema/`.
- `Menu\Navigation`, `Menu\Item` (record, URL and text items, with their label, fragment, HTML, display, icon token,
  target, classes and active switch) and `Menu\Tree`, which nests a menu's rows and flattens them back, keeping an item
  whose parent is missing or in a loop at the top rather than losing it.
- `Link\Url`, which reads a path, a full link, a fragment, an email address or a phone number as an editor typed it,
  holding the last two as mailto: and tel: links, and refuses addresses that run code. `Link\RecordTarget` names a
  record by section and id.
- `Html\HtmlSanitiser`, keeping a text item's HTML to a small inline set.
- `Storage\PhpDbNavigationRepository`: menus, items with moving and deleting, and revisions, each change bumping its
  menu's updated time. `Storage\Transaction` joins a transaction already open on the connection.
- `Menu\PublishedBy` records who published a revision: their user id, held as text, and their name as it was then.
- `Publishing\Publisher`: publish a menu's draft as a revision, discard changes, restore a revision, count unpublished
  changes, and read the live menu for the site.
- `ConfigProvider`, with settings under `contenir_navigation`.
