<?php

namespace Drupal\custom_design\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

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
    // $custom_design_config = $this->config('custom_design.settings');
    $config = \Drupal::service('config.factory')->getEditable('custom_design.settings');

    $description = t('Upload an image file. Available extensions (.png .jpg .jpeg)');

    if ($fid = $config->get('fid')) {
      $file = File::load($fid);
      $uri = $file->getFileUri();
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
      $description .= '<p><strong>Existing image:</strong><br><img src="'. $style->buildUrl($uri) . '"></p>';
    }

    $form['bg_image_file'] = array(
      '#title' => t('Background image'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#description' => $description,
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
        $file->setPermanent();
        $file->save();

        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($file, 'custom_design', 'custom_design', \Drupal::currentUser()->id());

        if ($file->url()) {
          $this->config('custom_design.settings')
            ->set('fid', $fid)
            ->save();
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
