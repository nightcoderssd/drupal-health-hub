<?php

declare(strict_types=1);

namespace Drupal\health_hub_help\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an accessible "Need help now?" block.
 */
#[Block(
  id: 'health_hub_help_now',
  admin_label: new TranslatableMarkup('Need help now?'),
  category: new TranslatableMarkup('Nightcoders Demo'),
)]
final class HelpNowBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'heading' => 'Need help now?',
      'phone' => '555-0104',
      'hours' => 'Every day, 8 AM to 8 PM',
      'note' => 'This is a fictional number on a demonstration site.',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Heading'),
      '#default_value' => $this->configuration['heading'],
      '#required' => TRUE,
    ];
    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone number'),
      '#default_value' => $this->configuration['phone'],
      '#required' => TRUE,
    ];
    $form['hours'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hours'),
      '#default_value' => $this->configuration['hours'],
    ];
    $form['note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Note'),
      '#default_value' => $this->configuration['note'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    if (!preg_match('/^[0-9+().\-\s]{3,20}$/', (string) $form_state->getValue('phone'))) {
      $form_state->setErrorByName('phone', $this->t('Enter a phone number using digits, spaces, dashes or parentheses.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['heading', 'phone', 'hours', 'note'] as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'health_hub_help',
      '#heading' => $this->configuration['heading'],
      '#phone' => $this->configuration['phone'],
      '#hours' => $this->configuration['hours'],
      '#note' => $this->configuration['note'],
    ];
  }

}
