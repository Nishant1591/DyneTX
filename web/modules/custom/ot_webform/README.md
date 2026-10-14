# OneTrust WebForm

Drupal implementation of the legacy site's unfinished OneTrust module. The source WordPress plugin is reference material and is not executed or installed.

Enable with `ddev drush en ot_webform -y`, then configure `/admin/config/services/ot-webform`. The module starts **disabled** in the **test** environment. The hostname never changes the environment automatically.

The contact form's public data IDs and collection point element ID come from the archived theme `functions.php`. Verify them in the provider portal before enabling. The archive contains the DM1 container but does not identify its collection point; its three configuration fields are intentionally empty. A missing DM1 configuration never falls back to the contact form.

Existing imported content uses `<div data-dyne-onetrust-form></div>`. The behavior recognizes the adjacent `ot-container-corpdm1` container as the DM1 profile. Other content can use an explicit `data-dyne-onetrust-profile="contact"` or `"dm1"`. A configurable **OneTrust WebForm** block is also available through Drupal Block Layout. Only one provider form is initialized per page.

The small local behavior is attached through Drupal. It downloads the OneTrust script only when an enabled, configured form marker exists. Missing/disabled configuration and provider load failure leave a visible contact fallback. Script execution and actual submissions must be verified in the selected OneTrust environment; local checks do not submit personal information.

Optional attribution values are populated only into matching provider fields. Empty values never hide an unanswered required field. Analytics is off by default, and when configured sends only the form profile after the source plugin's `OneTrustWebFormSubmitted` provider event follows a submission of this form. A native `submit` event alone never shows a success message or fires analytics. Provider errors are not treated as confirmation.

Run the behavior checks with:

```sh
node --test web/modules/custom/ot_webform/tests/behavior.test.cjs
```

These isolated tests cover environment selection, default-disabled network behavior, missing DM1 configuration, provider script failure, the provider's actual `button.ot-submit` control, and confirmation gating.

The source test contact form was also loaded in the local Drupal site: First name, Last name and Email remained visible and required; the blank Sign up button remained disabled. No fields were entered and nothing was submitted. The integration was then restored to disabled/test. DM1 rendering and end-to-end submission remain unverified.
