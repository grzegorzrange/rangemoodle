<?php
// Script to fix double-encoded UTF-8 in Moodle database.
// Upload to Moodle root and run via browser (as admin) or CLI.
//
// Double-encoding happens when UTF-8 data is inserted into a UTF-8 database
// through a connection that was set to latin1. The fix:
// CONVERT(CAST(CONVERT(column USING latin1) AS BINARY) USING utf8mb4)
//
// Usage:
//   Browser: https://your-site/fix_encoding.php
//   CLI:     php fix_encoding.php
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

// Marker pattern: double-encoded "ą" = 0xC3 0x84 in latin1 interpretation.
// In double-encoded UTF-8, Polish chars like ą (C4 85) become (C3 84 C2 85).
// We detect this by looking for "Ä" (0xC384) which is the telltale sign.
$marker = 'Ä';

// Only fix text/varchar columns in mdl_ tables.
$prefix = $CFG->prefix;
$dbname = $CFG->dbname;

echo "=== Moodle Double-Encoding Fix ===\n";
echo "Database: $dbname\n";
echo "Prefix: $prefix\n";
echo "Looking for double-encoded UTF-8...\n\n";

// Dry run first, then fix.
$dryrun = true;
if ((CLI_SCRIPT && in_array('--fix', $argv ?? [])) ||
    (!CLI_SCRIPT && isset($_GET['fix']) && $_GET['fix'] === '1' && confirm_sesskey())) {
    $dryrun = false;
}

// Get all text/varchar columns from Moodle tables.
$sql = "SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = ?
        AND TABLE_NAME LIKE ?
        AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
        ORDER BY TABLE_NAME, COLUMN_NAME";

$stmt = $DB->get_recordset_sql(
    "SELECT TABLE_NAME AS tablename, COLUMN_NAME AS columnname, DATA_TYPE AS datatype
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = :dbname
        AND TABLE_NAME LIKE :prefix
        AND DATA_TYPE IN ('varchar', 'char', 'text', 'mediumtext', 'longtext')
   ORDER BY TABLE_NAME, COLUMN_NAME",
    ['dbname' => $dbname, 'prefix' => $prefix . '%']
);

$totalfixed = 0;
$affectedtables = [];

foreach ($stmt as $col) {
    $table = $col->tablename;
    $column = $col->columnname;

    // Count rows with the double-encoding marker.
    $count = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {{$table}} WHERE {$column} LIKE ?",
        ['%' . $marker . '%']
    );

    if ($count == 0) {
        continue;
    }

    echo "FOUND: {$table}.{$column} — {$count} rows with broken encoding\n";
    $affectedtables[$table][] = $column;

    if (!$dryrun) {
        // Fix the double encoding.
        $DB->execute(
            "UPDATE {{$table}}
                SET {$column} = CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
              WHERE {$column} LIKE ?",
            ['%' . $marker . '%']
        );
        $totalfixed += $count;
        echo "  -> FIXED {$count} rows\n";
    }
}

$stmt->close();

echo "\n=== Summary ===\n";
echo "Tables affected: " . count($affectedtables) . "\n";

if ($dryrun) {
    echo "\nThis was a DRY RUN. No data was changed.\n";
    if (CLI_SCRIPT) {
        echo "To apply fixes, run: php fix_encoding.php --fix\n";
    } else {
        $fixurl = new moodle_url('/fix_encoding.php', ['fix' => '1', 'sesskey' => sesskey()]);
        echo "\nTo apply fixes: <a href=\"" . $fixurl->out() . "\">CLICK HERE TO FIX</a>\n";
        echo "(Make sure you have a database backup first!)\n";
    }
} else {
    echo "Total rows fixed: {$totalfixed}\n";
    echo "\nDone! Purge Moodle caches now: /admin/purgecaches.php\n";
}

if (!CLI_SCRIPT) {
    echo '</pre></body></html>';
}
