INTRODUCTION
------------

External Link Pop-up module provides confirmation pop-up on external
link click, that follows out of the site. The module supports multiple
pop-ups and have options to allow some links don't show pop-ups.

The module uses `core/drupal.dialog` library, which is build on
`jquery.ui.dialog` library.

REQUIREMENTS
------------

This module requires the following modules:

 * Filter (Drupal core module)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------

The configuration page is in "Administation->Configuration->Content Authoring"
menu. The path is `/admin/config/content/external_link_popup`.

Global configuration includes domains whitelist field. Whitelist configuration
is described below in "Excluding Links" section.

Each of pop-ups has next options:

- "Show close icon" checkbox, check to chow close ([X]) icon in pop-up header.
- "Title" - fill with text displayed in pop-up title area.
- "Body" - pop-up main text.
- "Yes button" - text on button which allows user follow the link.
- "No button" - text on button which cancels jump and closes pop-up.
- "Domains" - white-space separated list of top-level domains
to show this pop-up. Domain 'domain.com' matches 'domain.com'
and '*.domain.com'.
- "Open in new tab by default" - check to open links having NO TARGET attribute
specified in new tab. If a link has target attribute it is used to detect how
to open it.

ATTENTION: It recommended to configure target attribute on the link for the
cases when JS is disabled or broken on the page, to open a link in new tab even
if external link popup isn't shown.

Excluding Links
=============
The module provides ability to exlude external domains from pop-up show.
To do it fill "Trusted domains (whitelist)" field in configuration
with white-space separated list of domains to excude. Each domain you defined
also matches for it subdomains, e.g. 'domain.com' matches 'domain.com'
and '*.domain.com'.

The module provide ability to exclude specific link from pop-up show. Just
add to the link `external-link-popup-disabled` CSS class.

Force Pop-up show
=============
The module provide ability to define which popup to show on a link with
`data-external-link-popup-id` attribute. It overrides any settings for the link
except `external-link-popup-disabled` CSS class. It also works for local links.
Use Popup machine name as a value.

Theming
=============
The pop-ups are based on `jquery.ui.dialog` and uses JQuery UI styling.
The module pop-up has the next additional classes on a dialog wrapper:
- `external-link-popup` - global class for all dialogs;
- `external-link-popup-id-%id%` - class for particular pop-up, where %id% is
it's machine name, e.g. `external-link-popup-id-default` for default pop-up;
- `external-link-popup-body` - class for Body section inside of the pop-up
content;

See jQuery UI styling framework for full information, here are some typical
cases:
- Width. JQuery Dialog requires pop-up width on creation and it equeals to 85%
of document width for responsive purposes. You can configure the default width
in module settings. You can control `min-width` and `max-width` of the pop-up
with CSS, e.g.
```css
.external-link-popup {
  min-width: 320px;
  max-width: 600px;
}
```
- Header. To style header use `.external-link-popup .ui-dialog-titlebar`
CSS rule.
- Content. To style content use `.external-link-popup .ui-dialog-content`
CSS rule.
- Buttons Area. To style the area use
`.external-link-popup .ui-dialog-buttonpane` CSS rule.
- Button. To style button use
`.external-link-popup .ui-dialog-buttonpane .ui-button` CSS rule.
Use `:first-child` and `:last-child` pseudo-classes to style specific button.

Events
=============
The external link popup triggers custom events based on
actions taken on the popup.

Events list:
- `externalLinkPopup:yes` - Popup Ok / Yes button clicked, popup is closed.
- `externalLinkPopup:no` - Popup is closed without going external site.
- `externalLinkPopup:notFound` - Popup not configured.
- `externalLinkPopup:skipped` - No popup shown due to whitelisted domain
or the link has "external-link-popup-disabled" class.

General example:
```
$(window).on('externalLinkPopup:{ACTION}', function(event) {
  console.log(event.popupId, event.domain, event.target);
});
```

Bootstrap support
=============

REFERENCES
-----------

* http://api.jqueryui.com/dialog/ - JQuery UI Dialog documentation.
* http://api.jqueryui.com/theming/css-framework/ - JQuery UI styling framework.

MAINTAINERS
-----------

Current maintainers:
 * Denis Rudoi <den.rudoi@gmail.com> - https://www.drupal.org/u/dewalt

This project has been sponsored by:
 * EPAM Systems <https://www.epam.com/>
