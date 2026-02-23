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
 * Polish strings for local_support.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Wsparcie plikow niestandardowych';
$string['fallbackemail'] = 'Zapasowy e-mail do resetowania hasła';
$string['fallbackemail_desc'] = 'Jeśli użytkownik poda adres e-mail, który nie istnieje w systemie podczas resetowania hasła, wiadomość zostanie wysłana na ten adres. Pozostaw puste, aby wyłączyć.';
$string['peselnotfound'] = 'Użytkownik o takim numerze PESEL nie istnieje.';
$string['fallbackemailsubject'] = 'Proba resetowania hasla dla nieznanego adresu email: {$a}';
$string['fallbackemailbody'] = 'Ktos probowal zresetowac haslo podajac adres email: {$a}, ale ten adres nie istnieje w systemie.';
$string['fallbackemailbody_html'] = '<p>Ktos probowal zresetowac haslo podajac adres email: <strong>{$a}</strong>, ale ten adres nie istnieje w systemie.</p>';
$string['emailpasswordconfirmmaybesent'] = 'Jesli istnieje konto powiazane z tymi danymi, wyslano wiadomosc z instrukcjami.';
$string['blockedurls'] = 'Zablokowane adresy URL dla zwyklych uzytkownikow';
$string['blockedurls_desc'] = 'Wpisz jedna sciezke URL na linie. Uzytkownicy nie bedacy administratorami odwiedzajacy te strony zostana przekierowani na /my/. Uzyj czesciowych sciezek, np. /grade/report/overview/index.php';
$string['sms_heading'] = 'Ustawienia SMS (SerwerSMS.pl)';
$string['sms_heading_desc'] = 'Konfiguracja serwisu wysylki SMS przez API SerwerSMS.pl.';
$string['sms_api_token'] = 'Token API';
$string['sms_api_token_desc'] = 'Token Bearer API z SerwerSMS.pl (Panel Klienta > Ustawienia interfejsow > HTTPS API > Tokeny API).';
$string['sms_sender'] = 'Nazwa nadawcy SMS';
$string['sms_sender_desc'] = 'Nazwa nadawcy wyswietlana w SMS (musi byc zarejestrowana w SerwerSMS.pl).';
$string['event_sms_sent'] = 'Wyslano SMS';
$string['internaltest_notdone'] = 'Niezrobiony';
$string['internaltest_passed'] = 'Zaliczony';
$string['internaltest_failed'] = 'Niezaliczony';
$string['internaltest_active'] = 'Aktywny';
$string['internaltest_inactive'] = 'Nieaktywny';
