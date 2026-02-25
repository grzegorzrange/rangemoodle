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

// Double-encoded UTF-8 markers — binary sequences that indicate broken encoding.
// These are the raw byte sequences for common double-encoded Polish characters.
// Using COLLATE utf8mb4_bin ensures exact binary matching (no accent/case folding).
$markers = [
    'Ä…',  // ą double-encoded
    'Ä™',  // ę double-encoded
    'Å›',  // ś double-encoded
    'Å‚',  // ł double-encoded
    'Å¼',  // ż double-encoded
    'Åº',  // ź double-encoded
    'Ä‡',  // ć double-encoded
    'Å„',  // ń double-encoded
    'Ã³',  // ó double-encoded
    'Ä„',  // Ą double-encoded
    'Ä˜',  // Ę double-encoded
    'Åš',  // Ś double-encoded
    'Å',   // Ł double-encoded (Å + next char)
    'Å»',  // Ż double-encoded
    'Å¹',  // Ź double-encoded
    'Ä†',  // Ć double-encoded
    'Å�',  // Ń double-encoded
    'Ã"',  // Ó double-encoded
];

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

// Build the WHERE condition: match any of the double-encoded markers using BINARY comparison.
$markerconditions = [];
$markerparams = [];
$i = 0;
foreach ($markers as $m) {
    $markerconditions[] = "COLUMN_PLACEHOLDER LIKE :marker{$i} COLLATE utf8mb4_bin";
    $markerparams["marker{$i}"] = '%' . $m . '%';
    $i++;
}

foreach ($stmt as $col) {
    $fulltable = $col->tablename;
    $column = $col->columnname;

    // Strip prefix — Moodle DML {table} adds it automatically.
    if (strpos($fulltable, $prefix) === 0) {
        $table = substr($fulltable, strlen($prefix));
    } else {
        $table = $fulltable;
    }

    // Build WHERE clause with binary collation for this column.
    $where = str_replace('COLUMN_PLACEHOLDER', $column, implode(' OR ', $markerconditions));

    // Count affected rows.
    $count = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {{$table}} WHERE {$where}",
        $markerparams
    );

    if ($count == 0) {
        continue;
    }

    echo "------------------------------------------------------------\n";
    echo "TABLE: {$fulltable}.{$column} ({$count} rows)\n";
    echo "------------------------------------------------------------\n";

    // Show preview: current value vs fixed value (max 5 samples per column).
    // Only show rows where the fix actually changes the value.
    $samples = $DB->get_records_sql(
        "SELECT id, {$column} AS val,
                CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4) AS fixed
           FROM {{$table}}
          WHERE ({$where})
            AND {$column} != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
          LIMIT 5",
        $markerparams
    );

    if (empty($samples)) {
        echo "  (All rows match marker but conversion produces no change — skipping)\n\n";
        continue;
    }

    // Recount only rows that actually change.
    $realcount = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {{$table}}
          WHERE ({$where})
            AND {$column} != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)",
        $markerparams
    );

    if ($realcount == 0) {
        echo "  (All rows match marker but conversion produces no change — skipping)\n\n";
        continue;
    }

    echo "  (Rows that actually change: {$realcount})\n\n";

    foreach ($samples as $row) {
        $before = mb_substr($row->val, 0, 120);
        $after = mb_substr($row->fixed, 0, 120);
        echo "  id={$row->id}\n";
        echo "    PRZED: {$before}\n";
        echo "    PO:    {$after}\n\n";
    }

    if ($realcount > 5) {
        echo "  ... i " . ($realcount - 5) . " wiecej\n\n";
    }

    $totalrows += $realcount;

    if (!$dryrun) {
        // Only update rows where the value actually changes.
        $DB->execute(
            "UPDATE {{$table}}
                SET {$column} = CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
              WHERE ({$where})
                AND {$column} != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)",
            $markerparams
        );
        $totalfixed += $realcount;
        echo "  -> NAPRAWIONO {$realcount} rows\n\n";
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
