<?php

namespace Drupal\external_link_popup\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\external_link_popup\ExternalLinkPopupInterface;

/**
 * Represents an External Link Pop-up entity.
 *
 * @ConfigEntityType(
 *   id = "external_link_popup",
 *   label = @Translation("External link pop-up"),
 *   label_collection = @Translation("External link pop-ups"),
 *   label_singular = @Translation("external link pop-up"),
 *   label_plural = @Translation("external link pop-ups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count external link pop-up",
 *     plural = "@count external link pop-ups"
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\external_link_popup\Form\ExternalLinkPopupForm",
 *       "edit" = "Drupal\external_link_popup\Form\ExternalLinkPopupForm",
 *       "delete" = "Drupal\external_link_popup\Form\ExternalLinkPopupDeleteForm"
 *     },
 *     "list_builder" = "Drupal\external_link_popup\Controller\ExternalLinkPopupListBuilder"
 *   },
 *   config_prefix = "external_link_popup",
 *   admin_permission = "administer external link popup",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "status",
 *     "weight",
 *     "close",
 *     "title",
 *     "body",
 *     "labelyes",
 *     "labelno",
 *     "domains",
 *     "new_tab"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/external_link_popup/{external_link_popup}",
 *     "delete-form" = "/admin/config/system/external_link_popup/{external_link_popup}/delete",
 *     "enable" = "/admin/config/system/external_link_popup/{external_link_popup}/enable",
 *     "disable" = "/admin/config/system/external_link_popup/{external_link_popup}/disable",
 *     "collection" = "/admin/config/system/external_link_popup",
 *   }
 * )
 */
class ExternalLinkPopup extends ConfigEntityBase implements ExternalLinkPopupInterface, \JsonSerializable {

  /**
   * The ID of the pop-up.
   *
   * @var string
   */
  protected $id;

  /**
   * The pop-up label.
   *
   * @var string
   */
  protected $name;

  /**
   * The weight of the pop-up.
   *
   * @var int
   */
  protected $weight;

  /**
   * Close icon flag of the pop-up.
   *
   * @var bool
   */
  protected $close;

  /**
   * The title of the pop-up.
   *
   * @var string
   */
  protected $title;

  /**
   * The formatted text of the pop-up.
   *
   * @var array
   */
  protected $body;

  /**
   * The "Yes" button label of the pop-up.
   *
   * @var string
   */
  protected $labelyes;

  /**
   * The "No" button label of the pop-up.
   *
   * @var string
   */
  protected $labelno;

  /**
   * The domains restriction of the pop-up.
   *
   * @var string
   */
  protected $domains;

  /**
   * Link open in new tab.
   *
   * @var bool
   */
  protected $new_tab;

  /**
   * {@inheritdoc}
   */
  public function getClose() {
    return $this->close;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelyes() {
    return $this->labelyes;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabelno() {
    return $this->labelno;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomains() {
    return $this->domains;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewTab() {
    return $this->new_tab;
  }

  /**
   * {@inheritdoc}
   */
  public function setClose($close) {
    $this->close = $close;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody($body) {
    $this->body = $body;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelyes($labelyes) {
    $this->labelyes = $labelyes;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabelno($labelno) {
    $this->labelno = $labelno;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDomains($domains) {
    $this->domains = $domains;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNewTab($new_tab) {
    $this->new_tab = $new_tab;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    $body = isset($this->body['value']) ? check_markup(
      $this->body['value'],
      isset($this->body['format']) ? $this->body['format'] : NULL
    ) : '';

    return [
      'id' => $this->id(),
      'name' => $this->label(),
      'status' => $this->status(),
      'weight' => $this->weight,
      'close' => $this->close,
      'title' => $this->title,
      'body' => $body,
      'labelyes' => $this->labelyes,
      'labelno' => $this->labelno,
      'domains' => $this->domains,
      'target' => $this->new_tab ? '_blank' : '_self',
    ];
  }

}
