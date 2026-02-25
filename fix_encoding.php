<?php
// Script to fix double-encoded UTF-8 in Moodle database.
// Upload to Moodle root and run via browser (as admin) or CLI.
//
// IMPORTANT: Make a database backup before running this!

define('CLI_SCRIPT', (php_sapi_name() === 'cli'));

require_once(__DIR__ . '/config.php');

if (!CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><meta charset="utf-8"><title>Fix Encoding</title></head><body><pre>';
}

$marker = 'Ä';
$prefix = $CFG->prefix;
$dbname = $CFG->dbname;

echo "=== Moodle Double-Encoding Fix ===\n";
echo "Database: $dbname\n";
echo "Prefix: $prefix\n\n";

// Dry run first, then fix.
$dryrun = true;
if ((CLI_SCRIPT && in_array('--fix', $argv ?? [])) ||
    (!CLI_SCRIPT && isset($_GET['fix']) && $_GET['fix'] === '1' && confirm_sesskey())) {
    $dryrun = false;
}

$stmt = $DB->get_recordset_sql(
    "SELECT TABLE_NAME AS tablename, COLUMN_NAME AS columnname
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = :dbname
        AND TABLE_NAME LIKE :prefix
        AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
   ORDER BY TABLE_NAME, COLUMN_NAME",
    ['dbname' => $dbname, 'prefix' => $prefix . '%']
);

$totalfixed = 0;
$totalrows = 0;

foreach ($stmt as $col) {
    $fulltable = $col->tablename;
    $column = $col->columnname;

    // Strip prefix — Moodle DML {table} adds it automatically.
    if (strpos($fulltable, $prefix) === 0) {
        $table = substr($fulltable, strlen($prefix));
    } else {
        $table = $fulltable;
    }

    // Count affected rows.
    $count = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {{$table}} WHERE {$column} LIKE ?",
        ['%' . $marker . '%']
    );

    if ($count == 0) {
        continue;
    }

    echo "------------------------------------------------------------\n";
    echo "TABLE: {$fulltable}.{$column} ({$count} rows)\n";
    echo "------------------------------------------------------------\n";

    // Show preview: current value vs fixed value (max 5 samples per column).
    $samples = $DB->get_records_sql(
        "SELECT id, {$column} AS val,
                CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4) AS fixed
           FROM {{$table}}
          WHERE {$column} LIKE ?
          LIMIT 5",
        ['%' . $marker . '%']
    );

    foreach ($samples as $row) {
        $before = mb_substr($row->val, 0, 120);
        $after = mb_substr($row->fixed, 0, 120);
        echo "  id={$row->id}\n";
        echo "    PRZED: {$before}\n";
        echo "    PO:    {$after}\n\n";
    }

    if ($count > 5) {
        echo "  ... i " . ($count - 5) . " wiecej\n\n";
    }

    $totalrows += $count;

    if (!$dryrun) {
        $DB->execute(
            "UPDATE {{$table}}
                SET {$column} = CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
              WHERE {$column} LIKE ?",
            ['%' . $marker . '%']
        );
        $totalfixed += $count;
        echo "  -> NAPRAWIONO {$count} rows\n\n";
    }
}

$stmt->close();

echo "\n=== Podsumowanie ===\n";
echo "Znalezionych rekordow do naprawy: {$totalrows}\n";

if ($dryrun) {
    echo "\nTo byl DRY RUN — nic nie zostalo zmienione.\n";
    echo "Przejrzyj powyzsze zmiany PRZED -> PO.\n";
    if (CLI_SCRIPT) {
        echo "Aby naprawic: php fix_encoding.php --fix\n";
    } else {
        $fixurl = new moodle_url('/fix_encoding.php', ['fix' => '1', 'sesskey' => sesskey()]);
        echo "\nAby naprawic: <a href=\"" . $fixurl->out() . "\">KLIKNIJ TUTAJ ABY NAPRAWIC</a>\n";
        echo "(Upewnij sie, ze masz backup bazy!)\n";
    }
} else {
    echo "Naprawionych rekordow: {$totalfixed}\n";
    echo "\nGotowe! Wyczysc cache: /admin/purgecaches.php\n";
}

if (!CLI_SCRIPT) {
    echo '</pre></body></html>';
}
