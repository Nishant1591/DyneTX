<?php
$db = \Drupal::database();
$tables = array_filter($db->schema()->findTables('%'), static fn($table) => preg_match('/^(node|paragraphs?|media|taxonomy_term|path_alias|menu_link_content)(_|$)|^file_managed$/', $table));
sort($tables);
$report = [];
foreach ($tables as $table) {
  $rows = $db->select($table, 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC);
  $serialized = array_map(static fn($row) => json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $rows);
  sort($serialized, SORT_STRING);
  $report[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $serialized))];
}
print json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
