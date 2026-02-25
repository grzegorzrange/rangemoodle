<?php
// Fix double-encoded UTF-8 in selected core tables.
// Upload to Moodle root and run via browser (as admin) or CLI.
//
// Modes:
//   default    = dry run (preview)
//   ?fix=1     = apply fix
//   --fix      = apply fix (CLI)

define('CLI_SCRIPT', (php_sapi_name() === 'cli'));

require_once(__DIR__ . '/config.php');

if (!CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><head><meta charset="utf-8"><title>Fix Encoding - Core</title></head><body><pre>';
}

$dryrun = true;
if ((CLI_SCRIPT && in_array('--fix', $argv ?? [])) ||
    (!CLI_SCRIPT && isset($_GET['fix']) && $_GET['fix'] === '1' && confirm_sesskey())) {
    $dryrun = false;
}

echo "=== Fix Encoding: Core Tables ===\n";
echo "Mode: " . ($dryrun ? "DRY RUN (preview)" : "FIX") . "\n\n";

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

$targets = [
    'adminpresets' => ['comments', 'name'],
    'config_plugins' => ['value'],
    'course' => ['fullname', 'shortname'],
    'course_sections' => ['name'],
    'event' => ['name'],
    'grade_items' => ['itemname'],
    'grade_items_history' => ['itemname'],
    'label' => ['intro', 'name'],
    'notifications' => ['contexturlname', 'fullmessage'],
];

$totalfixed = 0;

foreach ($targets as $table => $columns) {
    foreach ($columns as $column) {
        $where = str_replace('COLUMN_PH', $column, $likeTemplate);
        $changeWhere = "({$where}) AND {$column} COLLATE utf8mb4_bin != CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)";

        $count = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {{$table}} WHERE {$changeWhere}",
            $likeParams
        );

        if ($count == 0) {
            continue;
        }

        echo "------------------------------------------------------------\n";
        echo "TABLE: {$CFG->prefix}{$table}.{$column} ({$count} rows)\n";
        echo "------------------------------------------------------------\n";

        $samples = $DB->get_records_sql(
            "SELECT id, {$column} AS val,
                    CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4) AS fixed
               FROM {{$table}}
              WHERE {$changeWhere}
              LIMIT 5",
            $likeParams
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

        if (!$dryrun) {
            $DB->execute(
                "UPDATE {{$table}}
                    SET {$column} = CONVERT(CAST(CONVERT({$column} USING latin1) AS BINARY) USING utf8mb4)
                  WHERE {$changeWhere}",
                $likeParams
            );
            $totalfixed += $count;
            echo "  -> NAPRAWIONO {$count} rows\n\n";
        }
    }
}

echo "\n=== Podsumowanie ===\n";
if ($dryrun) {
    echo "DRY RUN — nic nie zmienione.\n";
    if (CLI_SCRIPT) {
        echo "Aby naprawic: php fix_encoding_core.php --fix\n";
    } else {
        $fixurl = new moodle_url('/fix_encoding_core.php', ['fix' => '1', 'sesskey' => sesskey()]);
        echo "\n<a href=\"" . $fixurl->out() . "\">KLIKNIJ ABY NAPRAWIC</a>\n";
    }
} else {
    echo "Naprawiono: {$totalfixed} rows\n";
    echo "Gotowe! Wyczysc cache: /admin/purgecaches.php\n";
}

if (!CLI_SCRIPT) {
    echo '</pre></body></html>';
}
