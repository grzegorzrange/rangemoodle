<?php
// Fix double-encoded UTF-8 — one table.column at a time.
// Usage: ?table=config&column=value&sesskey=xxx
//
// Each call fixes ONE column, then shows result + links to fix remaining columns.

define('CLI_SCRIPT', (php_sapi_name() === 'cli'));

require_once(__DIR__ . '/config.php');

if (!CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><meta charset="utf-8"><title>Fix Encoding - Step</title></head><body><pre>';
}

$prefix = $CFG->prefix;
$dbname = $CFG->dbname;

$markers = [
    'Ä…', 'Ä™', 'Å›', 'Å‚', 'Å¼', 'Åº', 'Ä‡', 'Å„', 'Ã³',
    'Ä„', 'Ä˜', 'Åš', 'Å»', 'Å¹', 'Ä†', 'Ã"',
];

$likeParts = [];
$likeParams = [];
$i = 0;
foreach ($markers as $m) {
    $likeParts[] = "COLUMN_PH LIKE :m{$i} COLLATE utf8mb4_bin";
    $likeParams["m{$i}"] = '%' . $m . '%';
    $i++;
}
$likeTemplate = implode(' OR ', $likeParts);

$skiptables = ['local_mail_history'];
$skipcolumns = ['notifications.fullmessagehtml'];

// If table+column given — fix that one.
$fixtable = isset($_GET['table']) ? clean_param($_GET['table'], PARAM_ALPHANUMEXT) : '';
$fixcolumn = isset($_GET['column']) ? clean_param($_GET['column'], PARAM_ALPHANUMEXT) : '';

if (!empty($fixtable) && !empty($fixcolumn) && confirm_sesskey()) {
    $where = str_replace('COLUMN_PH', $fixcolumn, $likeTemplate);
    $changeWhere = "({$where}) AND {$fixcolumn} COLLATE utf8mb4_bin != CONVERT(CAST(CONVERT({$fixcolumn} USING latin1) AS BINARY) USING utf8mb4)";

    $count = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {{$fixtable}} WHERE {$changeWhere}",
        $likeParams
    );

    echo "Naprawiam: {$prefix}{$fixtable}.{$fixcolumn} ({$count} rows)...\n";

    if ($count > 0) {
        try {
            $DB->execute(
                "UPDATE {{$fixtable}}
                    SET {$fixcolumn} = CONVERT(CAST(CONVERT({$fixcolumn} USING latin1) AS BINARY) USING utf8mb4)
                  WHERE {$changeWhere}",
                $likeParams
            );
            echo "NAPRAWIONO {$count} rows!\n\n";
        } catch (Exception $e) {
            echo "BLAD: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "Brak rekordow do naprawy.\n\n";
    }
}

// Show remaining columns to fix.
echo "=== Pozostale kolumny do naprawy ===\n\n";

$cols = $DB->get_recordset_sql(
    "SELECT TABLE_NAME AS tablename, COLUMN_NAME AS columnname
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = :dbname
        AND TABLE_NAME LIKE :prefix
        AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
   ORDER BY TABLE_NAME, COLUMN_NAME",
    ['dbname' => $dbname, 'prefix' => $prefix . '%']
);

$remaining = 0;

foreach ($cols as $col) {
    $fulltable = $col->tablename;
    $column = $col->columnname;

    if (strpos($fulltable, $prefix) === 0) {
        $table = substr($fulltable, strlen($prefix));
    } else {
        $table = $fulltable;
    }

    if (in_array($table, $skiptables)) {
        continue;
    }
    if (in_array($table . '.' . $column, $skipcolumns)) {
        continue;
    }

    $where = str_replace('COLUMN_PH', $column, $likeTemplate);
    $changeWhere = "({$where}) AND {$column} COLLATE utf8mb4_bin != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)";

    try {
        $count = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {{$table}} WHERE {$changeWhere}",
            $likeParams
        );
    } catch (Exception $e) {
        continue;
    }

    if ($count == 0) {
        continue;
    }

    $remaining += $count;
    $url = new moodle_url('/fix_encoding_step.php', [
        'table' => $table,
        'column' => $column,
        'sesskey' => sesskey(),
    ]);
    echo "{$prefix}{$table}.{$column} — {$count} rows — <a href=\"" . $url->out() . "\">NAPRAW</a>\n";
}

$cols->close();

if ($remaining == 0) {
    echo "Wszystko naprawione! Brak kolumn do poprawy.\n";
    echo "\nWyczysc cache: /admin/purgecaches.php\n";
} else {
    echo "\nPozostalo: {$remaining} rows w powyzszych kolumnach.\n";
}

if (!CLI_SCRIPT) {
    echo '</pre></body></html>';
}
