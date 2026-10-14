<?php

namespace Drupal\ot_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the source site's OneTrust integration without guessing its host.
 */
class OtWebformConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ot_webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ot_webform_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ot_webform.settings');
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable OneTrust forms'),
      '#description' => $this->t('Enable after checking the collection point in the OneTrust portal. Disabled forms do not contact OneTrust.'),
      '#default_value' => $config->get('enabled'),
    ];
    $form['environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Environment'),
      '#options' => ['staging' => $this->t('Test'), 'production' => $this->t('Production')],
      '#default_value' => $config->get('environment') ?: 'staging',
      '#description' => $this->t('Choose explicitly. The hostname never switches a test form to production.'),
    ];
    foreach (['contact' => $this->t('Contact form'), 'dm1' => $this->t('DM1 updates form')] as $profile => $title) {
      $prefix = $profile === 'dm1' ? 'dm1_' : '';
      $form[$profile] = ['#type' => 'details', '#title' => $title, '#open' => TRUE];
      foreach (['prod_id' => $this->t('Production data ID'), 'staging_id' => $this->t('Test data ID'), 'collection_point_id' => $this->t('Collection point element ID')] as $key => $label) {
        $name = $prefix . $key;
        $form[$profile][$name] = [
          '#type' => 'textfield',
          '#title' => $label,
          '#default_value' => $config->get($name) ?: '',
          '#maxlength' => 128,
        ];
      }
    }
    $form['attribution'] = ['#type' => 'details', '#title' => $this->t('Optional form attribution'), '#open' => FALSE];
    $form['attribution']['source_platform'] = [
      '#type' => 'url',
      '#title' => $this->t('Source platform'),
      '#default_value' => $config->get('source_platform'),
    ];
    foreach (['data_source_prod' => $this->t('Production data source'), 'data_source_stage' => $this->t('Test data source'), 'gtm_event' => $this->t('Analytics event after provider confirmation')] as $key => $label) {
      $form['attribution'][$key] = [
        '#type' => 'textfield',
        '#title' => $label,
        '#default_value' => $config->get($key),
      ];
    }
    $form['thank_you_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message after provider confirmation'),
      '#default_value' => $config->get('thank_you_text'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    foreach (['prod_id', 'staging_id', 'collection_point_id', 'dm1_prod_id', 'dm1_staging_id', 'dm1_collection_point_id'] as $key) {
      $value = trim((string) $form_state->getValue($key));
      if ($value !== '' && !preg_match('/^[A-Za-z0-9_-]+$/D', $value)) {
        $form_state->setErrorByName($key, $this->t('Use only letters, numbers, underscores and hyphens in OneTrust IDs.'));
      }
    }
    if ($form_state->getValue('enabled')) {
      $key = $form_state->getValue('environment') === 'production' ? 'prod_id' : 'staging_id';
      if (!$form_state->getValue($key) && !$form_state->getValue('dm1_' . $key)) {
        $form_state->setErrorByName($key, $this->t('Provide a data ID for at least one form in the selected environment.'));
      }
      foreach (['', 'dm1_'] as $prefix) {
        if ($form_state->getValue($prefix . $key) && !$form_state->getValue($prefix . 'collection_point_id')) {
          $form_state->setErrorByName($prefix . 'collection_point_id', $this->t('Provide the collection point element ID for this configured form.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('ot_webform.settings');
    foreach (['environment', 'prod_id', 'staging_id', 'collection_point_id', 'dm1_prod_id', 'dm1_staging_id', 'dm1_collection_point_id', 'source_platform', 'data_source_prod', 'data_source_stage', 'gtm_event', 'thank_you_text'] as $key) {
      $config->set($key, trim((string) $form_state->getValue($key)));
    }
    $config->set('enabled', (bool) $form_state->getValue('enabled'))->save();
    parent::submitForm($form, $form_state);
  }

}
