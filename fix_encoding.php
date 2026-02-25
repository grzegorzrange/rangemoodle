<?php
// Script to fix double-encoded UTF-8 in Moodle database.
// Upload to Moodle root and run via browser (as admin) or CLI.
//
// Modes:
//   default (no params)  = count only — shows affected tables/columns with row counts
//   ?preview=1           = show PRZED/PO samples (max 5 per column)
//   ?fix=1&sesskey=xxx   = apply the fix
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

$prefix = $CFG->prefix;
$dbname = $CFG->dbname;

// Determine mode.
$mode = 'count'; // default: just count
if ((CLI_SCRIPT && in_array('--preview', $argv ?? [])) ||
    (!CLI_SCRIPT && !empty($_GET['preview']))) {
    $mode = 'preview';
}
if ((CLI_SCRIPT && in_array('--fix', $argv ?? [])) ||
    (!CLI_SCRIPT && isset($_GET['fix']) && $_GET['fix'] === '1' && confirm_sesskey())) {
    $mode = 'fix';
}

echo "=== Moodle Double-Encoding Fix ===\n";
echo "Database: $dbname\n";
echo "Prefix: $prefix\n";
echo "Mode: $mode\n\n";

// Double-encoded UTF-8 markers for Polish characters (binary sequences).
$markers = [
    'Ä…', 'Ä™', 'Å›', 'Å‚', 'Å¼', 'Åº', 'Ä‡', 'Å„', 'Ã³',
    'Ä„', 'Ä˜', 'Åš', 'Å»', 'Å¹', 'Ä†', 'Ã"',
];

// Build WHERE fragments.
$likeParts = [];
$likeParams = [];
$i = 0;
foreach ($markers as $m) {
    $likeParts[] = "COLUMN_PH LIKE :m{$i} COLLATE utf8mb4_bin";
    $likeParams["m{$i}"] = '%' . $m . '%';
    $i++;
}
$likeTemplate = implode(' OR ', $likeParts);

// Get all text columns.
$cols = $DB->get_recordset_sql(
    "SELECT TABLE_NAME AS tablename, COLUMN_NAME AS columnname
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = :dbname
        AND TABLE_NAME LIKE :prefix
        AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
   ORDER BY TABLE_NAME, COLUMN_NAME",
    ['dbname' => $dbname, 'prefix' => $prefix . '%']
);

$totalrows = 0;
$totalfixed = 0;

// Tables to skip.
$skiptables = [
    'local_mail_history',
];

foreach ($cols as $col) {
    $fulltable = $col->tablename;
    $column = $col->columnname;

    if (strpos($fulltable, $prefix) === 0) {
        $table = substr($fulltable, strlen($prefix));
    } else {
        $table = $fulltable;
    }

    // Skip excluded tables.
    if (in_array($table, $skiptables)) {
        continue;
    }

    $where = str_replace('COLUMN_PH', $column, $likeTemplate);
    $changeWhere = "({$where}) AND {$column} COLLATE utf8mb4_bin != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)";

    // Count rows that actually change.
    try {
        $count = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {{$table}} WHERE {$changeWhere}",
            $likeParams
        );
    } catch (Exception $e) {
        // Skip tables that cause errors (e.g. views, temp tables).
        continue;
    }

    if ($count == 0) {
        continue;
    }

    $totalrows += $count;

    if ($mode === 'count') {
        echo "{$fulltable}.{$column} — {$count} rows\n";
        continue;
    }

    // Preview or fix mode.
    echo "------------------------------------------------------------\n";
    echo "TABLE: {$fulltable}.{$column} ({$count} rows)\n";
    echo "------------------------------------------------------------\n";

    if ($mode === 'preview') {
        try {
            $samples = $DB->get_records_sql(
                "SELECT id, {$column} AS val,
                        CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4) AS fixed
                   FROM {{$table}}
                  WHERE {$changeWhere}
                  LIMIT 5",
                $likeParams
            );
        } catch (Exception $e) {
            echo "  BLAD przy odczycie probek: " . $e->getMessage() . "\n";
            echo "  Pomijam te kolumne.\n\n";
            continue;
        }

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
    }

    if ($mode === 'fix') {
        try {
            $DB->execute(
                "UPDATE {{$table}}
                    SET {$column} = CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
                  WHERE {$changeWhere}",
                $likeParams
            );
            $totalfixed += $count;
            echo "  -> NAPRAWIONO {$count} rows\n\n";
        } catch (Exception $e) {
            echo "  BLAD przy UPDATE: " . $e->getMessage() . "\n\n";
        }
    }
}

$cols->close();

echo "\n=== Podsumowanie ===\n";
echo "Rekordow do naprawy: {$totalrows}\n";

if ($mode === 'count') {
    echo "\nTo bylo tylko ZLICZANIE.\n";
    if (CLI_SCRIPT) {
        echo "Preview: php fix_encoding.php --preview\n";
        echo "Fix:     php fix_encoding.php --fix\n";
    } else {
        echo "\n<a href=\"fix_encoding.php?preview=1\">POKAZ PODGLAD PRZED/PO</a>\n";
        $fixurl = new moodle_url('/fix_encoding.php', ['fix' => '1', 'sesskey' => sesskey()]);
        echo "<a href=\"" . $fixurl->out() . "\">NAPRAW (po przejrzeniu podgladu!)</a>\n";
    }
} else if ($mode === 'preview') {
    echo "\nTo byl PODGLAD — nic nie zostalo zmienione.\n";
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
