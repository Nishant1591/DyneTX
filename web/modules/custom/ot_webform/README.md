# OneTrust WebForm

Drupal implementation of the live site's public OneTrust forms. The source WordPress plugin is reference material and is not executed or installed.

Enable with `ddev drush en ot_webform -y`, then configure `/admin/config/services/ot-webform`. The module starts **disabled** in the **test** environment. The hostname never changes the environment automatically.

The October 2026 live embeds and public provider definitions identify these profiles:

| Profile | Collection point | Container |
| --- | --- | --- |
| Contact/About | `d37d89f4-02ef-4834-8033-678cd54cd22a` | `ot-container` |
| DM1 | `7b853126-3f3e-4835-913f-06dfd33301fe` | `ot-container-corpdm1` |

The public production tenant is `5c7d29d6-5208-4f7a-b1b5-b9aae9d54670`; its explicitly selected staging tenant adds `-test`. Collection point IDs, tenant IDs and DOM container IDs are different identifiers. A missing DM1 configuration never falls back to the contact form.

Existing imported content uses `<div data-dyne-onetrust-form></div>`. The behavior recognizes the adjacent `ot-container-corpdm1` container as the DM1 profile. Other content can use an explicit `data-dyne-onetrust-profile="contact"` or `"dm1"`. A configurable **OneTrust WebForm** block is also available through Drupal Block Layout. Only one provider form is initialized per page.

The small local behavior is attached through Drupal. It downloads the OneTrust script only when an enabled, configured form marker exists. Missing/disabled configuration and provider load failure leave a visible contact fallback. Loading the provider exposes the current URL/domain and browser network metadata to OneTrust. Local staging rendering was explicitly approved for `dyne-tx-workspace.ddev.site`; no personal data or submissions were sent. Submission delivery still requires a separate authorized test.

Optional attribution values are populated only into matching provider fields. Empty values never hide an unanswered required field. Analytics is off by default, and when configured sends only the form profile after the source plugin's `OneTrustWebFormSubmitted` provider event follows a submission of this form. A native `submit` event alone never shows a success message or fires analytics. Provider errors are not treated as confirmation.

Run the behavior checks with:

```sh
node --test web/modules/custom/ot_webform/tests/behavior.test.cjs
```

These isolated tests cover environment selection, default-disabled network behavior, profile-specific collection point IDs, missing DM1 configuration, provider script failure, the provider's actual `button.ot-submit` control, and confirmation gating.

`scripts/repair-audit-integrations.php` previews the verified form IDs and native consent copy by default. Apply only after reviewing its output. `DYNE_AUDIT_INTEGRATIONS_APPLY=1` saves the settings; enabling the external providers additionally requires `DYNE_AUDIT_PROVIDER_APPROVED=1` plus `DYNE_AUDIT_ENABLE_FORMS=1` and/or `DYNE_AUDIT_ENABLE_COOKIEBOT=1`. The script deliberately selects the staging forms and does not submit them.

Cookiebot is optional and disabled by default. The live banner domain group is `60486e5f-6638-432e-b626-4e9883af4245`. On 28 October 2026 its SDK explicitly rejected `DYNE-TX-WORKSPACE.DDEV.SITE` as unauthorized. This domain must be added in Cookiebot Manager before selecting Cookiebot for this deployment. Until then the native Drupal consent banner, preference tab and consent actions remain active. The Cookie Declaration loader is guarded by the same toggle. Enabling Cookiebot removes the native banner libraries to avoid competing consent interfaces; installing the SDK alone does not authorize a domain.
