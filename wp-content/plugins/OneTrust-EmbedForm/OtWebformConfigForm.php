<?php

namespace Drupal\ot_webform\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * OneTrust WebForm admin configuration form.
 * Route: /admin/config/services/ot-webform
 */
class OtWebformConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ot_webform.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ot_webform_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ot_webform.settings');

    // ── Section 1: OneTrust IDs ──────────────────
    $form['ids'] = [
      '#type'  => 'details',
      '#title' => $this->t('1. OneTrust Collection Point IDs'),
      '#open'  => TRUE,
      '#description' => $this->t('Paste the GUIDs from your OneTrust portal — one per environment.'),
    ];
    $form['ids']['prod_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Production GUID'),
      '#description'   => $this->t('The live site <code>data-id</code> from the OneTrust Collection Point.'),
      '#default_value' => $config->get('prod_id') ?? '',
      '#placeholder'   => '5c7d29d6-0000-0000-0000-xxxxxxxxxxxx',
      '#required'      => TRUE,
    ];
    $form['ids']['staging_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Staging GUID'),
      '#description'   => $this->t('The test/staging <code>data-id</code> — usually ends in <code>-test</code>.'),
      '#default_value' => $config->get('staging_id') ?? '',
      '#placeholder'   => '5c7d29d6-0000-0000-0000-xxxxxxxxxxxx-test',
    ];
    $form['ids']['staging_keywords'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Staging Hostname Keywords (comma-separated)'),
      '#description'   => $this->t('If the hostname contains any of these words, the staging GUID is used automatically.'),
      '#default_value' => $config->get('staging_keywords') ?? 'dev,stage,staging,test,demo,uat,localhost',
    ];

    // ── Section 2: Hidden Form Field Values ──────
    $form['fields'] = [
      '#type'        => 'details',
      '#title'       => $this->t('2. Hidden Form Field Values'),
      '#open'        => TRUE,
      '#description' => $this->t(
        'These 7 fields are silently injected into the OT form and hidden from visitors: '
        . '<code>sourcePlatform</code>, <code>dataSource</code>, and all 5 UTM fields.'
      ),
    ];
    $form['fields']['source_platform'] = [
      '#type'          => 'url',
      '#title'         => $this->t('Source Platform (URL)'),
      '#description'   => $this->t('Injected into the <code>sourcePlatform</code> OT field — identifies the website.'),
      '#default_value' => $config->get('source_platform') ?? \Drupal::request()->getSchemeAndHttpHost(),
      '#required'      => TRUE,
    ];
    $form['fields']['data_source_prod'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Data Source — Production'),
      '#description'   => $this->t('Value for the <code>dataSource</code> OT field on production environments.'),
      '#default_value' => $config->get('data_source_prod') ?? '',
      '#placeholder'   => 'Conference March 2026',
    ];
    $form['fields']['data_source_stage'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Data Source — Staging'),
      '#description'   => $this->t('Value for the <code>dataSource</code> OT field on staging/test environments.'),
      '#default_value' => $config->get('data_source_stage') ?? '',
      '#placeholder'   => 'Conference March 2026 test',
    ];

    // ── Section 3: Advanced ──────────────────────
    $form['advanced'] = [
      '#type'  => 'details',
      '#title' => $this->t('3. Advanced Settings'),
      '#open'  => FALSE,
    ];
    $form['advanced']['gtm_event'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('GTM Event Name'),
      '#description'   => $this->t('Event pushed to <code>window.dataLayer</code> on form submission.'),
      '#default_value' => $config->get('gtm_event') ?? 'onetrust_form_submitted',
    ];
    $form['advanced']['thank_you_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Thank-You Element ID'),
      '#description'   => $this->t('HTML element ID shown after successful submission. Leave blank to disable.'),
      '#default_value' => $config->get('thank_you_id') ?? 'thank-you-message',
    ];
    $form['advanced']['debug'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Debug Mode'),
      '#description'   => $this->t('Logs <code>[OT-Embed]</code> messages to browser console. Disable on production.'),
      '#default_value' => (bool) $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ot_webform.settings')
      ->set('prod_id',           $form_state->getValue('prod_id'))
      ->set('staging_id',        $form_state->getValue('staging_id'))
      ->set('staging_keywords',  $form_state->getValue('staging_keywords'))
      ->set('source_platform',   $form_state->getValue('source_platform'))
      ->set('data_source_prod',  $form_state->getValue('data_source_prod'))
      ->set('data_source_stage', $form_state->getValue('data_source_stage'))
      ->set('gtm_event',         $form_state->getValue('gtm_event'))
      ->set('thank_you_id',      $form_state->getValue('thank_you_id'))
      ->set('debug',             (bool) $form_state->getValue('debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
