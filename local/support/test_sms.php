<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test page for SMS sending.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/support/test_sms.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Test SMS');
$PAGE->set_heading('Test SMS');

$phone = optional_param('phone', '', PARAM_RAW);
$text = optional_param('text', '', PARAM_RAW);
$send = optional_param('send', 0, PARAM_BOOL);

echo $OUTPUT->header();
echo $OUTPUT->heading('Test SMS');

if ($send && confirm_sesskey() && !empty($phone) && !empty($text)) {
    // Create a fake user object with the provided phone number.
    $fakeuser = clone $USER;
    $fakeuser->phone1 = $phone;

    $result = \local_support\sms_service::send($fakeuser, $text, 'local_support', 'sms_test');

    // Show last SMS history record for debug.
    $lastrecord = $DB->get_record_sql(
        "SELECT * FROM {local_sms_history} WHERE userid = ? ORDER BY id DESC LIMIT 1",
        [$USER->id]
    );

    if ($result) {
        echo $OUTPUT->notification('SMS wysłany pomyślnie na numer: ' . s($phone), 'success');
    } else {
        echo $OUTPUT->notification('Wysyłka SMS nie powiodła się.', 'error');
    }

    if ($lastrecord) {
        echo '<div class="alert alert-info mt-2">';
        echo '<strong>Debug:</strong><br>';
        echo 'Numer: ' . s($lastrecord->phone) . '<br>';
        echo 'Success: ' . $lastrecord->success . '<br>';
        echo 'API Response: <pre>' . s($lastrecord->response) . '</pre>';
        echo '</div>';
    } else {
        // No record means send() returned before API call.
        $token = get_config('local_support', 'sms_api_token');
        echo '<div class="alert alert-warning mt-2">';
        echo '<strong>Debug:</strong><br>';
        echo 'Brak rekordu w local_sms_history. Prawdopodobna przyczyna:<br>';
        if (empty($token)) {
            echo '- <strong>API Token jest pusty!</strong> Ustaw go w: Administracja > Wtyczki lokalne > Custom File Support<br>';
        }
        echo '- Numer telefonu po normalizacji mógł być pusty. Podany numer: ' . s($phone) . '<br>';
        echo '</div>';
    }
}

$formaction = (new moodle_url('/local/support/test_sms.php'))->out(false);
$sesskey = sesskey();
?>

<form method="post" action="<?php echo $formaction; ?>" class="mb-3">
    <input type="hidden" name="sesskey" value="<?php echo $sesskey; ?>">
    <input type="hidden" name="send" value="1">
    <div class="form-group mb-3">
        <label for="phone">Numer telefonu</label>
        <input type="text" id="phone" name="phone" class="form-control" style="max-width:400px"
               value="<?php echo s($phone); ?>" placeholder="+48500600700" required>
    </div>
    <div class="form-group mb-3">
        <label for="text">Treść SMS</label>
        <textarea id="text" name="text" class="form-control" style="max-width:600px" rows="3"
                  required placeholder="Wpisz treść wiadomości..."><?php echo s($text); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Wyślij testowy SMS</button>
</form>

<?php
echo $OUTPUT->footer();
