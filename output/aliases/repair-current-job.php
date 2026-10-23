<?php
$node = \Drupal\node\Entity\Node::load(334);
if (!$node || $node->bundle() !== 'job' || (int) $node->get('path')->pathauto !== \Drupal\pathauto\PathautoState::CREATE) throw new \RuntimeException('Expected automatic Job alias unavailable.');
$manager = \Drupal::service('path_alias.manager');
$source = '/node/' . $node->id();
$before = json_decode(file_get_contents(dirname(DRUPAL_ROOT) . '/output/aliases/before.json'), TRUE, 512, JSON_THROW_ON_ERROR)['nodes']['334']['alias'];
$generator = \Drupal::service('pathauto.generator');
$expected = $generator->createEntityAlias(clone $node, 'return');
if (!$expected || !str_starts_with($expected, '/jobs/')) throw new \RuntimeException('Expected Job URL pattern unavailable.');
$revision = $node->getRevisionId();
$generator->updateEntityAlias($node, 'update');
$manager->cacheClear($source);
$after = $manager->getAliasByPath($source);
if ($after !== $expected) throw new \RuntimeException('Current title alias repair failed.');
$redirect = $before !== $after ? \Drupal::service('redirect.repository')->findMatchingRedirect(ltrim($before, '/'), [], $node->language()->getId()) : NULL;
if ($before !== $after && (!$redirect || (int) $redirect->getStatusCode() !== 301 || $redirect->getRedirectUrl()->getInternalPath() !== ltrim($source, '/'))) throw new \RuntimeException('Previous Job URL redirect missing.');
$report = ['node_id' => (int) $node->id(), 'title' => $node->label(), 'before' => $before, 'after' => $after, 'old_url_status' => $redirect ? (int) $redirect->getStatusCode() : NULL, 'node_revision_unchanged' => \Drupal\node\Entity\Node::load(334)->getRevisionId() === $revision];
file_put_contents(dirname(DRUPAL_ROOT) . '/output/aliases/current-job-repair.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
