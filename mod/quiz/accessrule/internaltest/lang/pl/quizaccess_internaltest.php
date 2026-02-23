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
 * Polish strings for quizaccess_internaltest.
 *
 * @package    quizaccess_internaltest
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Test wewnętrzny';
$string['internaltest'] = 'Test wewnętrzny';
$string['internaltest_help'] = 'Po włączeniu odpowiedzi z testu są zapisywane jako JSON po złożeniu podejścia. Powiadomienia e-mail są wysyłane do zapisanych użytkowników przed otwarciem i zamknięciem testu.';
$string['privacy:metadata:quizaccess_inttest_results'] = 'Przechowuje dane JSON z odpowiedziami użytkownika na testy wewnętrzne.';
$string['privacy:metadata:quizaccess_inttest_results:userid'] = 'ID użytkownika, który złożył podejście.';
$string['privacy:metadata:quizaccess_inttest_results:jsondata'] = 'Dane JSON zawierające pytania i odpowiedzi użytkownika.';
$string['privacy:metadata:quizaccess_inttest_results:timecreated'] = 'Czas zapisania wyniku.';
$string['messageprovider:internaltest_reminder'] = 'Przypomnienia o testach wewnętrznych';
$string['notification_subject_7days'] = 'Test wewnętrzny "{$a->quizname}" otwiera się za 7 dni';
$string['notification_subject_open'] = 'Test wewnętrzny "{$a->quizname}" jest już dostępny';
$string['notification_subject_closing'] = 'Test wewnętrzny "{$a->quizname}" zamyka się za 24 godziny';
$string['notification_body_7days'] = 'Test wewnętrzny "{$a->quizname}" w kursie "{$a->coursename}" zostanie otwarty za 7 dni ({$a->opendate}). Proszę się odpowiednio przygotować.';
$string['notification_body_open'] = 'Test wewnętrzny "{$a->quizname}" w kursie "{$a->coursename}" jest już dostępny. Możesz rozpocząć test tutaj: {$a->url}';
$string['notification_body_closing'] = 'Test wewnętrzny "{$a->quizname}" w kursie "{$a->coursename}" zamyka się za 24 godziny ({$a->closedate}). Proszę ukończyć go przed terminem.';
$string['taskname'] = 'Wysyłanie powiadomień o testach wewnętrznych';
$string['sms_7days'] = 'Test "{$a->quizname}" ({$a->coursename}) otwiera sie {$a->opendate}. Prosimy o przygotowanie.';
$string['sms_open'] = 'Test "{$a->quizname}" ({$a->coursename}) jest juz dostepny. Zaloguj sie, aby rozpoczac.';
$string['sms_closing'] = 'Test "{$a->quizname}" ({$a->coursename}) zamyka sie {$a->closedate}. Prosimy o ukonczenie przed terminem.';
