<?php

/**
 * @file
 * Creates fictional sample content for the demo.
 * Run: drush php:script scripts/sample-content.php
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

$terms = [];
foreach (['Testing', 'Prevention', 'Vaccines', 'Support', 'Care'] as $w => $name) {
  $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'resource_category', 'name' => $name]);
  $term = $existing ? reset($existing) : Term::create(['vid' => 'resource_category', 'name' => $name, 'weight' => $w]);
  $term->save();
  $terms[$name] = $term->id();
}

$resources = [
  ['Free Walk-In Testing Clinic', 'Testing', 'Confidential testing with same-day results. No appointment or insurance needed. You can use a first name only.', 'Mon–Fri, 9 AM–5 PM', '555-0100', ['en', 'es'], 'free'],
  ['Prevention Medication Navigator', 'Prevention', 'A navigator helps you understand prevention options and how to pay for them.', 'Mon–Thu, 10 AM–6 PM', '555-0101', ['en', 'es', 'vi'], 'free'],
  ['Hepatitis Vaccine Days', 'Vaccines', 'Hepatitis A and B vaccines for adults at community sites.', 'First Saturday of each month, 9 AM–1 PM', '555-0102', ['en'], 'low'],
  ['Peer Support Group', 'Support', 'A private, judgment-free group led by trained peers. Join in person or online.', 'Wednesdays, 6–7:30 PM', '555-0103', ['en', 'es'], 'free'],
  ['Care Coordination Line', 'Care', 'Help finding a doctor, getting medicine and keeping appointments.', 'Every day, 8 AM–8 PM', '555-0104', ['en', 'es', 'tl'], 'free'],
  ['Mobile Testing Van', 'Testing', 'Free testing at community events and parks. Check the weekly schedule by phone.', 'Varies by week', '555-0105', ['en', 'es', 'zh'], 'free'],
  ['Sliding-Scale Sexual Health Clinic', 'Care', 'Exams, testing and treatment. You pay based on income.', 'Tue and Thu, 1–7 PM', '555-0106', ['en', 'vi'], 'sliding'],
];
foreach ($resources as [$title, $cat, $body, $hours, $phone, $langs, $cost]) {
  if (\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'resource', 'title' => $title])) {
    continue;
  }
  Node::create([
    'type' => 'resource', 'title' => $title, 'status' => 1, 'uid' => 1,
    'body' => ['value' => "<p>$body</p><p><em>Fictional service on a demonstration site.</em></p>", 'format' => 'basic_html'],
    'field_resource_category' => $terms[$cat], 'field_hours' => $hours, 'field_phone' => $phone,
    'field_languages' => array_map(fn($l) => ['value' => $l], $langs), 'field_cost' => $cost,
  ])->save();
}

$pages = [
  ['Health services in Riverbend County', '<p>Find free and low-cost testing, prevention, vaccines and support. You do not need insurance for most services.</p><h2>What you can do here</h2><ul><li><a href="/resources">Find a service</a> by type or language.</li><li><a href="/about-testing">Learn what to expect at a testing visit</a>.</li></ul>', '/home'],
  ['What to expect at a testing visit', '<ol><li><strong>Check in.</strong> You can use a first name only.</li><li><strong>Talk with a staff member.</strong> They explain the test and answer questions.</li><li><strong>Get tested.</strong> Most tests take a few minutes.</li><li><strong>Get your result.</strong> Staff tell you what it means and what to do next.</li></ol><p>Your result is private.</p>', '/about-testing'],
];
foreach ($pages as [$title, $body, $alias]) {
  if (\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'page', 'title' => $title])) {
    continue;
  }
  $node = Node::create(['type' => 'page', 'title' => $title, 'status' => 1, 'uid' => 1, 'body' => ['value' => $body, 'format' => 'basic_html'], 'path' => ['alias' => $alias]]);
  $node->save();
  if ($alias === '/home') {
    \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/node/' . $node->id())->save();
  }
  else {
    MenuLinkContent::create(['title' => 'About testing', 'link' => ['uri' => 'internal:' . $alias], 'menu_name' => 'main', 'weight' => 5])->save();
  }
}
echo "Sample content created.\n";
