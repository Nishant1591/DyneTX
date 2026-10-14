<?php

namespace Drupal\external_link_popup\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the external_link_popup.settings route.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['external_link_popup.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_link_popup_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function config($name = NULL) {
    return parent::config($name ?: 'external_link_popup.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Trusted domains (whitelist)'),
      '#default_value' => $this->config()->get('whitelist'),
      '#description' => $this->t('Links to these external domain(s) will work normally, without pop-up warning.')
      . '<br />'
      . $this->t('Use base domain name without protocol or "www" prefix. Enter one domain per line.'),
    ];

    $form['show_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on administration pages too.'),
      '#default_value' => $this->config()->get('show_admin'),
    ];

    $form['width'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default width'),
      '#attributes' => ['class' => ['container-inline']],
      '#tree' => TRUE,
      '#required' => TRUE,
    ];

    $form['width']['value'] = [
      '#type' => 'number',
      '#title' => $this->t('Default width'),
      '#title_display' => 'invisible',
      '#min' => 1,
      '#max' => 4096,
      '#default_value' => $this->config()->get('width.value'),
      '#required' => TRUE,
    ];

    $form['width']['units'] = [
      '#type' => 'select',
      '#options' => ['%' => '%', 'px' => 'px'],
      '#default_value' => $this->config()->get('width.units'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $whitelist = trim($form_state->getValue('whitelist'));
    if (
      $whitelist
      && !preg_match('/^([^\s\/\\,+]+[^\S\n]*\n)*([^\s\/\\,+]+)$/', $whitelist)
    ) {
      $form_state->setErrorByName('whitelist', $this->t('Please match the requested format.'));
    }
    else {
      $form_state->setValue('whitelist', $whitelist);
    }

    if (
      $form_state->getValue(['width', 'value'])
      && $form_state->getValue(['width', 'units']) === '%'
      && $form_state->getValue(['width', 'value']) > 100
    ) {
      $form_state->setErrorByName('width][value', $this->t('%name must be higher than or equal to %min.', [
        '%name' => $form['width']['#title'],
        '%min' => 100,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config()
      ->set('whitelist', $form_state->getValue('whitelist'))
      ->set('show_admin', $form_state->getValue('show_admin'))
      ->set('width', $form_state->getValue('width'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
