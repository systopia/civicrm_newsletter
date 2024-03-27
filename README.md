## Additionally Required Modules:

* [CiviMRF Core](https://drupal.org/project/cmrf_core)

## Basic Configuration

* Install module and dependencies (see above)

### in Drupal 9/10

* Add, edit or check CiviMRF Profile (`/admin/config/cmrf/profiles`)
* Add CiviMRF Connector at `/admin/config/cmrf/connectors`:
  * system name: `civicrm_newsletter`
  * Connecting Module: `civicrm_newsletter`
  * CiviMRF Profile: as configured in the previous step
* Select defined CiviMRF Connector at
  `/admin/config/services/civicrm_newsletter`

### in Drupal 7

* Add, edit or check CiviMRF Profile (`/admin/config/civimrf/profiles`)
* select CiviMRF-profile (`/admin/config/services/civicrm_newsletter`)
* there is no separate option for CiviMRF Connector in Drupal 7. only the
  profile has to be selected.

### in CiviCRM

* Configure your Advanced Newsletter Management profiles
  * Install and configure CiviCRM extensions:
    * [Extended Contact Matcher, XCM (CiviCRM extension)](https://github.com/systopia/de.systopia.xcm)
    * [Advanced Newsletter Management (CiviCRM extension)](https://github.com/systopia/de.systopia.newsletter)

Back in Drupal you should then be able to access your default subscription page:
`/civicrm_newsletter/subscribe/<profile_name>`

## We need your support

This Drupal module is provided as Free and Open Source Software, and we are
happy if you find it useful. However, we have put a lot of work into it (and
continue to do so), much of it unpaid for. So if you benefit from our software,
please consider making a financial contribution so we can continue to maintain
and develop it further.

If you are willing to support us in developing this Drupal module, please send
an email to info@systopia.de to get an invoice or agree a different payment
method. Thank you!
