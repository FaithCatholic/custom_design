<?php

namespace Drupal\custom_design\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

use Drupal\media\Entity\Media;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('custom_design.settings');
    $description = t('Upload an image file. Available extensions (.png .jpg .jpeg)');
    $type = $config->get('type');
    $fid = $config->get('fid');

    $form['type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Type of background'),
      '#options' => array(
        'image' => $this->t('Image'),
        'video' => $this->t('Video'),
      ),
      '#default_value' => $config->get('type') ? $config->get('type') : 'image',
      '#required' => TRUE,
    );

    $form['image'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Image'),
      '#states' => array(
        'visible' => array(
          ':input[name="type"]' => ['value' => 'image'],
        ),
      ),
    );

    if ($type == 'image' && $fid) {
      $file = File::load($fid);
      $uri = $file->getFileUri();
      $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('thumbnail');
      $description .= '<p><strong>Existing image:</strong><br><img src="'. $style->buildUrl($uri) . '"></p>';
    }

    $form['image']['bg_image_file'] = array(
      '#title' => t('Background image'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#description' => $description,
      '#upload_validators' => array(
        'file_validate_extensions' => array('png jpg jpeg'),
      ),
    );

    $form['video'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Video'),
      '#states' => array(
        'visible' => array(
          ':input[name="type"]' => ['value' => 'video'],
        ),
      ),
    );

    if ($type == 'video' && $fid) {
      $mid = $config->get('mid');
      $entity = Media::load($mid);
    }

    $options = ['absolute' => TRUE, 'query' => ['destination' => 'admin/appearance/custom-design']];
    $url = \Drupal\Core\Url::fromRoute('entity.media.add_form', ['media_type' => 'remote_video'], $options);
    $url = $url->toString();

    $form['video']['bg_video_file'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#title' => $this->t('Video'),
      '#description' => ($this->t('Type a video title to make a selection.'). ' <a href="'. $url .'">You can also upload a new video.</a>'),
      '#tags' => TRUE,
      '#selection_settings' => array(
        'target_bundles' => array('video'),
      ),
      '#weight' => '0',
    ];

    if (isset($entity)) {
      $form['video']['bg_video_file']['#default_value'] = $entity;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $type = $values['type'];

    $this->config('custom_design.settings')->set('type', $type)->save();

    if ($type == 'image') {
      if (array_key_exists('bg_image_file', $values)) {
        if (is_array($values['bg_image_file'])) {
          $fid = $values['bg_image_file'][0];
          $file = \Drupal\file\Entity\File::load($fid);
          $file->setPermanent();
          $file->save();

          $file_usage = \Drupal::service('file.usage');
          $file_usage->add($file, 'custom_design', 'custom_design', \Drupal::currentUser()->id());

          if ($file->url()) {
            $this->config('custom_design.settings')->set('mid', '')->save();
            $this->config('custom_design.settings')->set('fid', $fid)->save();
          }
        }
      }
    } else if ($type == 'video') {
      $media_id = $values['bg_video_file'][0]['target_id'];
      $entity = Media::load($media_id);
      $fid = $entity->field_media_video_file->target_id;

      $this->config('custom_design.settings')->set('mid', $media_id)->save();
      $this->config('custom_design.settings')->set('fid', $fid)->save();
    }

    parent::submitForm($form, $form_state);
  }
}
