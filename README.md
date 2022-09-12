## Additionally Required Modules:

* [CiviMRF Core](https://drupal.org/project/cmrf_core)
* [Advanced Newsletter Manager (CiviCRM extension)](https://github.com/systopia/de.systopia.newsletter)
* [Extended Contact Matcher, XCM (CiviCRM extension)](https://github.com/systopia/de.systopia.xcm)

## Basic Configuration

* Install module and dependencies (see above)
* Add, edit or check CiviMRF Profile (`/admin/config/cmrf_profile`)
* Add CiviMRF Connector at `/admin/config/cmrf_connector`:
  * system name: `civicrm_newsletter`
  * Connecting Module: `civicrm_newsletter`
  * Profile: `default`
* Select defined CiviMRF Connector at
  `/admin/config/services/civicrm_newsletter`
* Configure your Advanced Newsletter Management profiles in CiviCRM

You should then be able to access your default subscription page:
`/civicrm_newsletter/subscribe/<profile_name>`
