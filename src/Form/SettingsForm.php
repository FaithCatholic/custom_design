<?php

namespace Drupal\custom_design\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures custom_design settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_design_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_design.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $custom_design_config = $this->config('custom_design.settings');

    $form['bg_image_file'] = array(
      '#title' => t('Background image'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#description' => t('Upload an image file. Available extensions (.png .jpg .jpeg)'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('png jpg jpeg'),
      ),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($values && array_key_exists('bg_image_file', $values)) {
      if (is_array($values['bg_image_file'])) {
        $fid = $values['bg_image_file'][0];
        $file = \Drupal\file\Entity\File::load($fid);
        if ($file->url()) {
          $this->config('custom_design.settings')
            ->set('bg_image_url', $file->url())
            ->save();
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
