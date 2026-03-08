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
 * Polish language strings for local_activityreport.
 *
 * @package    local_activityreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Raport aktywności';
$string['activityreport'] = 'Raport aktywności';
$string['filter_firstname'] = 'Imię';
$string['filter_lastname'] = 'Nazwisko';
$string['filter_email'] = 'Email';
$string['filter_eventname'] = 'Nazwa zdarzenia';
$string['filter'] = 'Filtruj';
$string['resetfilters'] = 'Resetuj';
$string['eventname'] = 'Nazwa zdarzenia';
$string['description'] = 'Opis';
$string['timecreated'] = 'Czas';
$string['nologsfound'] = 'Nie znaleziono wpisów w logach.';
$string['firstname'] = 'Imię';
$string['lastname'] = 'Nazwisko';
$string['email'] = 'Email';
$string['filter_description'] = 'Opis';
$string['filter_datefrom'] = 'Data od';
$string['filter_dateto'] = 'Data do';
$string['privacy:metadata'] = 'Wtyczka Raport aktywności nie przechowuje żadnych danych osobowych. Odczytuje jedynie istniejące dane z logów.';

// Polish event descriptions.
$string['eventdesc_user_loggedin'] = 'Użytkownik {$a->user} zalogował się na platformę.';
$string['eventdesc_course_module_viewed'] = 'Użytkownik {$a->user} wyświetlił materiał „{$a->module}" w kursie „{$a->course}".';
$string['eventdesc_course_module_completion_updated'] = 'Użytkownik {$a->user} zaktualizował ukończenie modułu „{$a->module}" w kursie „{$a->course}".';
$string['eventdesc_course_completed'] = 'Użytkownik {$a->user} ukończył kurs „{$a->course}".';
$string['eventdesc_attempt_started'] = 'Użytkownik {$a->user} rozpoczął podejście do testu „{$a->module}" w kursie „{$a->course}".';
$string['eventdesc_attempt_submitted'] = 'Użytkownik {$a->user} wysłał podejście do testu „{$a->module}" w kursie „{$a->course}".';
$string['eventdesc_attempt_reviewed'] = 'Użytkownik {$a->user} przeglądał wynik testu „{$a->module}" w kursie „{$a->course}".';
$string['eventdesc_attempt_viewed'] = 'Użytkownik {$a->user} wyświetlił podejście do testu „{$a->module}" w kursie „{$a->course}".';

// Polish event names (fallback).
$string['eventname_user_loggedin'] = 'Logowanie użytkownika';
$string['eventname_course_module_viewed'] = 'Wyświetlenie modułu kursu';
$string['eventname_course_module_completion_updated'] = 'Aktualizacja ukończenia modułu';
$string['eventname_course_completed'] = 'Ukończenie kursu';
$string['eventname_attempt_started'] = 'Rozpoczęcie podejścia do testu';
$string['eventname_attempt_submitted'] = 'Wysłanie podejścia do testu';
$string['eventname_attempt_reviewed'] = 'Przegląd wyniku testu';
$string['eventname_attempt_viewed'] = 'Wyświetlenie podejścia do testu';
