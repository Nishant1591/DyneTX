<?php

namespace Drupal\external_link_popup\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the External Link Pop-up add and edit forms.
 */
class ExternalLinkPopupForm extends EntityForm {

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t("Label for the External Link Pop-up."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['name'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];
    $form['domains'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Domains'),
      '#default_value' => $this->entity->getDomains(),
      '#description' => $this->t('Use base domain name without protocol or "www" prefix. Enter one domain per line.')
      . ' '
      . $this->t('"domain.com" matches all subdomains *.domain.com. Use "*" to show for all domains.'),
      '#required' => TRUE,
    ];

    $form['close'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show close icon'),
      '#default_value' => $this->entity->getClose(),
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->entity->getTitle(),
    ];
    $body = $this->entity->getBody();
    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body text'),
      '#default_value' => $body && isset($body['value']) ? $body['value'] : '',
      '#format' => $body && isset($body['format']) ? $body['format'] : NULL,
      '#description' => $this->t('You can use <em>[link:url]</em> and <em>[link:text]</em> tokens in body content.'),
      '#required' => TRUE,
    ];
    $form['labelyes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Yes button'),
      '#default_value' => $this->entity->getLabelyes(),
      '#required' => TRUE,
    ];
    $form['new_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open in new tab by default'),
      '#default_value' => $this->entity->getNewTab(),
      '#description' => $this->t('If a link has no target attribute it would be opened in new tab.'),
    ];
    $form['labelno'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No button'),
      '#default_value' => $this->entity->getLabelno(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $domains = trim($form_state->getValue('domains'));
    if (
      $domains != '*'
      && !preg_match('/^([^\s\/\\,+]+[^\S\n]*\n)*([^\s\/\\,+]+)$/', $domains)
    ) {
      $form_state->setErrorByName('domains', $this->t('Please match the requested format.'));
    }
    else {
      $form_state->setValue('domains', $domains);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      $this->messenger()->addStatus($this->t('Saved the %label External Link Pop-up.', [
        '%label' => $this->entity->label(),
      ]));
    }
    else {
      $this->messenger()->addError($this->t('The %label External Link Pop-up was not saved.', [
        '%label' => $this->entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.external_link_popup.collection');
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager
      ->getStorage('external_link_popup')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}
