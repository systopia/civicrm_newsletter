<?php

namespace Drupal\civicrm_newsletter\Controller;

class NewsletterController
{
    public function configServices()
    {
        // TODO: Implement.

        return;
    }

    public function subscribe()
    {
        return drupal_get_form('Drupal\civicrm_newsletter\Form\SubscriptionForm'); // TODO: Test!
    }

    public function preferences()
    {
        // TODO: Implement.

        return;
    }

    public function requestLink()
    {
        // TODO: Implement.

        return;
    }
}
