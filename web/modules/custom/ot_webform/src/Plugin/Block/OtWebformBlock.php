<?php

namespace Drupal\ot_webform\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Displays the configured OneTrust contact or DM1 form.
 */
#[Block(
  id: 'ot_webform_embed',
  admin_label: new TranslatableMarkup('OneTrust WebForm'),
  category: new TranslatableMarkup('Dyne'),
)]
class OtWebformBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['profile' => 'contact'] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Form'),
      '#options' => ['contact' => $this->t('Contact'), 'dm1' => $this->t('DM1 updates')],
      '#default_value' => $this->configuration['profile'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['profile'] = $form_state->getValue('profile');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'ot_webform_embed',
      '#profile' => $this->configuration['profile'],
      '#attached' => ['library' => ['ot_webform/embed']],
      '#cache' => ['tags' => ['config:ot_webform.settings']],
    ];
  }

}
