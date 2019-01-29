<?php

/**
 * @file
 * Contains \Drupal\user_avatar\Form\AvatarConfigurationForm.
 */

namespace Drupal\sf_drupal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SFConfigurationForm extends ConfigFormBase
{
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return [
            'sf_drupal.adminsettings',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'sf_drupal_configuration_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $configuration = $this->config('sf_drupal.adminsettings');

        $form['sf_api_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('SalesForce API Login/Token URL'),
            '#description' => $this->t('SalesForce Api URL where we would get token after authentication, like https://login.salesforce.com/services/oauth2/token. Don\'t include slashes'),
            '#default_value' => $configuration->get('sf_api_url'),
            '#required' => true,
        ];

        $form['sf_consumer_key'] = [
            '#type' => 'textfield',
            '#title' => $this->t('SalesForce API Client ID/ Consumer Key'),
            '#description' => $this->t('SalesForce Api Consumer key'),
            '#default_value' => $configuration->get('sf_consumer_key'),
            '#required' => true,
        ];

        $form['sf_consumer_secret'] = [
            '#type' => 'textfield',
            '#title' => $this->t('SalesForce API Client Secret Key/ Consumer Secret'),
            '#description' => $this->t('SalesForce API Consumer Secret'),
            '#default_value' => $configuration->get('sf_consumer_secret'),
            '#required' => true,
        ];

        $form['sf_username'] = [
            '#type' => 'textfield',
            '#title' => $this->t('SalesForce User name'),
            '#description' => $this->t('SalesForce Username like slsahani@curious-bear-32849.com'),
            '#default_value' => $configuration->get('sf_username'),
            '#required' => true,
        ];

        $form['sf_password'] = [
            '#type' => 'textfield',
            '#title' => $this->t('SalesForce Password'),
            '#description' => $this->t('SalesForce Password used for login appended with security token. Like if password is "test@123" and security token is "xxxxxx" then Salesforce Password should be "test@123xxxxxx"'),
            '#default_value' => $configuration->get('sf_password'),
            '#required' => true,
        ];

        $form['sf_instance_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Your SalesForce Instance Url'),
            '#description' => $this->t('SalesForce Instance URL where we have to push data, like https://curious-bear-32849-dev-ed.my.salesforce.com. Don\'t include slashes'),
            '#default_value' => $configuration->get('sf_instance_url'),
            '#required' => true,
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Validate Settings'),
            '#name' => 'validate_settings',
            '#submit' => array([$this, 'validateSettings']),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        parent::submitForm($form, $form_state);

        $this->config('sf_drupal.adminsettings')
            ->set('sf_api_url', $form_state->getValue('sf_api_url'))
            ->set('sf_consumer_key', $form_state->getValue('sf_consumer_key'))
            ->set('sf_consumer_secret', $form_state->getValue('sf_consumer_secret'))
            ->set('sf_username', $form_state->getValue('sf_username'))
            ->set('sf_password', $form_state->getValue('sf_password'))
            ->set('sf_instance_url', $form_state->getValue('sf_instance_url'))
            ->save();
    }

    /**
     * {@inheritdoc}
     */
    public function validateSettings(array &$form, FormStateInterface $form_state)
    {
        $rawUrl = '%s?grant_type=password&client_id=%s&client_secret=%s&username=%s&password=%s';
        $tokenUrl = sprintf(
                        $rawUrl,
                        $form_state->getValue('sf_api_url'),
                        $form_state->getValue('sf_consumer_key'),
                        $form_state->getValue('sf_consumer_secret'),
                        $form_state->getValue('sf_username'),
                        $form_state->getValue('sf_password')
                    );

        $data_push_service = \Drupal::service('sf_drupal.data_push');
        $response = $data_push_service->getApiToken($tokenUrl);

        if (isset($response->access_token)) {
            $message = 'Configuration are correct. You may proceed to save them.';
            drupal_set_message(t($message), 'status');
        } else {
            $message = 'Configuration are not correct. Please verify them.';
            drupal_set_message(t($message), 'error');
        }
    }
}
