## Additionally Required Modules:

* [CiviMRF Core](https://drupal.org/project/cmrf_core)

## Basic Configuration

* Install module and dependencies (see above)
### in Drupal9
* Add, edit or check CiviMRF Profile (`/admin/config/cmrf_profile`)
* Add CiviMRF Connector at `/admin/config/cmrf_connector`:
  * system name: `civicrm_newsletter`
  * Connecting Module: `civicrm_newsletter`
  * Profile: `default`
* Select defined CiviMRF Connector at
  `/admin/config/services/civicrm_newsletter`
### in Drupal 7
* Add, edit or check CiviMRF Profile (`/admin/config/civimrf/profiles`)
* select CiviMRF-profile (`/admin/config/services/civicrm_newsletter`)
* there is no separate option for CiviMRF Connector in D7. only the profile has to be selected.
### in CiviCRM
* Configure your Advanced Newsletter Management profiles
  * Install and configure CiviCRM extensions:
    * [Extended Contact Matcher, XCM (CiviCRM extension)](https://github.com/systopia/de.systopia.xcm)
    * [Advanced Newsletter Management (CiviCRM extension)](https://github.com/systopia/de.systopia.newsletter)

Back in Drupal you should then be able to access your default subscription page:
`/civicrm_newsletter/subscribe/<profile_name>`