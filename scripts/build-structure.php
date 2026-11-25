<?php

/**
 * @file
 * One-time script used to create the demo's site structure, which was then
 * exported to config/sync. Run: drush php:script scripts/build-structure.php
 * (Not needed for a fresh install: import config/sync instead.)
 */

use Drupal\block\Entity\Block;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\views\Entity\View;

// Vocabulary.
if (!Vocabulary::load('resource_category')) {
  Vocabulary::create(['vid' => 'resource_category', 'name' => 'Resource category', 'description' => 'Type of service, such as Testing or Vaccines.'])->save();
}

// Content type.
if (!NodeType::load('resource')) {
  $type = NodeType::create(['type' => 'resource', 'name' => 'Resource', 'description' => 'A health service or program listed in the resource directory.']);
  $type->setDisplaySubmitted(FALSE);
  $type->save();
}

// Basic page type (the Drupal 11.4 standard profile does not create one).
if (!NodeType::load('page')) {
  $page = NodeType::create(['type' => 'page', 'name' => 'Basic page', 'description' => 'Plain-language information pages.']);
  $page->setDisplaySubmitted(FALSE);
  $page->save();
  FieldConfig::create(['field_name' => 'body', 'entity_type' => 'node', 'bundle' => 'page', 'label' => 'Body', 'settings' => ['display_summary' => FALSE]])->save();
  \Drupal::service('entity_display.repository')->getFormDisplay('node', 'page', 'default')->setComponent('body', ['type' => 'text_textarea_with_summary'])->save();
  \Drupal::service('entity_display.repository')->getViewDisplay('node', 'page', 'default')->setComponent('body', ['type' => 'text_default', 'label' => 'hidden'])->removeComponent('links')->save();
}

$fields = [
  'body' => [
    'storage' => ['type' => 'text_with_summary', 'cardinality' => 1],
    'field' => ['label' => 'Plain-language description', 'required' => TRUE, 'settings' => ['display_summary' => TRUE]],
    'widget' => 'text_textarea_with_summary', 'formatter' => 'text_default', 'fsettings' => [],
  ],
  'field_resource_category' => [
    'storage' => ['type' => 'entity_reference', 'settings' => ['target_type' => 'taxonomy_term'], 'cardinality' => 1],
    'field' => ['label' => 'Category', 'required' => TRUE, 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['resource_category' => 'resource_category']]]],
    'widget' => 'options_select', 'formatter' => 'entity_reference_label', 'fsettings' => ['link' => FALSE],
  ],
  'field_languages' => [
    'storage' => ['type' => 'list_string', 'cardinality' => -1, 'settings' => ['allowed_values' => ['en' => 'English', 'es' => 'Spanish', 'vi' => 'Vietnamese', 'tl' => 'Tagalog', 'zh' => 'Chinese']]],
    'field' => ['label' => 'Languages offered', 'required' => TRUE],
    'widget' => 'options_buttons', 'formatter' => 'list_default', 'fsettings' => [],
  ],
  'field_cost' => [
    'storage' => ['type' => 'list_string', 'cardinality' => 1, 'settings' => ['allowed_values' => ['free' => 'Free', 'low' => 'Low cost', 'sliding' => 'Sliding scale']]],
    'field' => ['label' => 'Cost', 'required' => TRUE],
    'widget' => 'options_select', 'formatter' => 'list_default', 'fsettings' => [],
  ],
  'field_hours' => [
    'storage' => ['type' => 'string', 'cardinality' => 1, 'settings' => ['max_length' => 255]],
    'field' => ['label' => 'Hours', 'required' => FALSE, 'description' => 'Write hours in plain words, for example "Mon–Fri, 9 AM–5 PM".'],
    'widget' => 'string_textfield', 'formatter' => 'string', 'fsettings' => [],
  ],
  'field_phone' => [
    'storage' => ['type' => 'telephone', 'cardinality' => 1],
    'field' => ['label' => 'Phone', 'required' => FALSE],
    'widget' => 'telephone_default', 'formatter' => 'telephone_link', 'fsettings' => [],
  ],
];

$repo = \Drupal::service('entity_display.repository');
$form = $repo->getFormDisplay('node', 'resource', 'default');
$display = $repo->getViewDisplay('node', 'resource', 'default');
$teaser = $repo->getViewDisplay('node', 'resource', 'teaser');
$weight = 1;
foreach ($fields as $name => $def) {
  if (!FieldStorageConfig::loadByName('node', $name)) {
    FieldStorageConfig::create(['field_name' => $name, 'entity_type' => 'node'] + $def['storage'])->save();
  }
  if (!FieldConfig::loadByName('node', 'resource', $name)) {
    FieldConfig::create(['field_name' => $name, 'entity_type' => 'node', 'bundle' => 'resource'] + $def['field'])->save();
  }
  $form->setComponent($name, ['type' => $def['widget'], 'weight' => $weight]);
  $display->setComponent($name, ['type' => $def['formatter'], 'label' => 'inline', 'weight' => $weight, 'settings' => $def['fsettings']]);
  $weight++;
}
$display->setComponent('body', ['type' => 'text_default', 'label' => 'hidden', 'weight' => 0]);
$form->save();
$display->save();
$teaser->removeComponent('links')->save();

// View: /resources with exposed filters.
if (!View::load('resources')) {
  $field = fn($id, $table, $label, $type, $settings = []) => [
    'id' => $id, 'table' => $table, 'field' => $id, 'plugin_id' => 'field', 'label' => $label,
    'element_label_colon' => TRUE, 'type' => $type, 'settings' => $settings,
    'entity_type' => 'node', 'entity_field' => $id,
  ];
  $view = View::create([
    'id' => 'resources',
    'label' => 'Resources',
    'description' => 'Searchable resource directory with exposed filters.',
    'base_table' => 'node_field_data',
    'base_field' => 'nid',
    'display' => [
      'default' => [
        'id' => 'default', 'display_plugin' => 'default', 'position' => 0, 'display_title' => 'Default',
        'display_options' => [
          'title' => 'Find services',
          'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
          'cache' => ['type' => 'tag'],
          'query' => ['type' => 'views_query'],
          'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Show services', 'reset_button' => TRUE, 'reset_button_label' => 'Clear filters']],
          'pager' => ['type' => 'full', 'options' => ['items_per_page' => 10]],
          'style' => ['type' => 'default'],
          'row' => ['type' => 'fields'],
          'empty' => [
            'area_text_custom' => ['id' => 'area_text_custom', 'table' => 'views', 'field' => 'area_text_custom', 'plugin_id' => 'text_custom', 'empty' => TRUE, 'content' => 'No services match these filters. Try clearing a filter.'],
          ],
          'header' => [
            'result' => ['id' => 'result', 'table' => 'views', 'field' => 'result', 'plugin_id' => 'result', 'content' => 'Showing @start–@end of @total services.'],
          ],
          'fields' => [
            'title' => ['element_type' => 'h2', 'element_label_colon' => FALSE, 'label' => '', 'settings' => ['link_to_entity' => TRUE]] + $field('title', 'node_field_data', '', 'string'),
            'body' => ['element_label_colon' => FALSE, 'label' => '', 'type' => 'text_summary_or_trimmed', 'settings' => ['trim_length' => 300]] + $field('body', 'node__body', '', 'text_summary_or_trimmed'),
            'field_resource_category' => $field('field_resource_category', 'node__field_resource_category', 'Category', 'entity_reference_label', ['link' => FALSE]),
            'field_hours' => $field('field_hours', 'node__field_hours', 'Hours', 'string'),
            'field_cost' => $field('field_cost', 'node__field_cost', 'Cost', 'list_default'),
            'field_languages' => ['group_column' => 'value', 'group_rows' => TRUE, 'delta_limit' => 0, 'multi_type' => 'separator', 'separator' => ', '] + $field('field_languages', 'node__field_languages', 'Languages', 'list_default'),
            'field_phone' => $field('field_phone', 'node__field_phone', 'Phone', 'telephone_link'),
          ],
          'filters' => [
            'status' => ['id' => 'status', 'table' => 'node_field_data', 'field' => 'status', 'plugin_id' => 'boolean', 'value' => '1', 'entity_type' => 'node', 'entity_field' => 'status', 'expose' => ['operator' => ''], 'group' => 1],
            'type' => ['id' => 'type', 'table' => 'node_field_data', 'field' => 'type', 'plugin_id' => 'bundle', 'value' => ['resource' => 'resource'], 'entity_type' => 'node', 'entity_field' => 'type', 'group' => 1],
            'title' => [
              'id' => 'title', 'table' => 'node_field_data', 'field' => 'title', 'plugin_id' => 'string', 'operator' => 'contains', 'exposed' => TRUE,
              'entity_type' => 'node', 'entity_field' => 'title', 'group' => 1,
              'expose' => ['operator_id' => 'title_op', 'label' => 'Service name contains', 'identifier' => 'keyword', 'remember_roles' => ['authenticated' => 'authenticated']],
            ],
            'field_resource_category_target_id' => [
              'id' => 'field_resource_category_target_id', 'table' => 'node__field_resource_category', 'field' => 'field_resource_category_target_id',
              'plugin_id' => 'taxonomy_index_tid', 'operator' => 'or', 'exposed' => TRUE, 'type' => 'select', 'vid' => 'resource_category', 'limit' => TRUE, 'group' => 1,
              'expose' => ['operator_id' => 'category_op', 'label' => 'Type of service', 'identifier' => 'category', 'remember_roles' => ['authenticated' => 'authenticated']],
            ],
            'field_languages_value' => [
              'id' => 'field_languages_value', 'table' => 'node__field_languages', 'field' => 'field_languages_value',
              'plugin_id' => 'list_field', 'operator' => 'or', 'exposed' => TRUE, 'group' => 1,
              'expose' => ['operator_id' => 'language_op', 'label' => 'Language spoken', 'identifier' => 'language', 'remember_roles' => ['authenticated' => 'authenticated']],
            ],
          ],
          'sorts' => [
            'title' => ['id' => 'title', 'table' => 'node_field_data', 'field' => 'title', 'plugin_id' => 'standard', 'order' => 'ASC', 'entity_type' => 'node', 'entity_field' => 'title'],
          ],
        ],
      ],
      'page_1' => [
        'id' => 'page_1', 'display_plugin' => 'page', 'position' => 1, 'display_title' => 'Page',
        'display_options' => ['path' => 'resources', 'menu' => ['type' => 'normal', 'title' => 'Find services', 'menu_name' => 'main', 'weight' => 0]],
      ],
    ],
  ]);
  $view->save();
}

// Place the custom block in the Riverbend sidebar (an <aside> landmark).
if (!Block::load('riverbend_help_now')) {
  Block::create([
    'id' => 'riverbend_help_now', 'theme' => 'riverbend', 'region' => 'sidebar', 'weight' => 0,
    'plugin' => 'health_hub_help_now', 'settings' => ['label' => 'Need help now?', 'label_display' => '0', 'provider' => 'health_hub_help'],
  ])->save();
}
echo "Structure created.\n";
