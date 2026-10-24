# User Navigation (by Cedric Kassen)

Lets every user reorder and hide the items of the server navigation. A new "Customize navigation" entry is added to the user menu inside a server. Admins can configure the default navigation for all users in the plugin settings.

## Features

- Drag the navigation items into the order you like
- Hide items you don't use
- Admins set the default order and visibility in the plugin settings, e.g. to sort the pages of many plugins
- "Reset to default" restores the admin default
- Changes to the default apply to all users, except for what they changed themselves
- The preferences are stored per user and apply to all servers

## How it works

Filament's navigation manager is replaced with an extended version that sorts and filters the navigation items after the panel built them. Pages and resources are identified by their class, other items by their label.

The admin default is applied on top of the panel's order, and the user's changes on top of that. Only what a user changed compared to the default is stored:

- If a user didn't change the order, they follow the admin's order, including later changes to it. If they did, their order is kept; items it doesn't contain yet, e.g. from a newly installed plugin, are placed as in the default.
- Only items a user hid or showed differently than the default are stored, everything else follows the admin default.

Admins can enable "Reset the order users chose themselves" in the plugin settings to apply a new order to all users.

Items only some servers have (e.g. from plugins that only show up for certain eggs) keep their position and visibility when you save the navigation of a server that doesn't have them.

## Limitations

- Only top level items are reordered, child items keep their order.
- Items are only reordered within their navigation group.
- Hiding an item only removes it from the navigation, the page itself can still be opened.
- The plugin settings list all items of the server navigation, including items that are only shown for certain servers. Items whose label or icon can't be read without a server are not listed there.
- The plugin replaces Filament's navigation manager, so it is not compatible with other plugins that replace it.
