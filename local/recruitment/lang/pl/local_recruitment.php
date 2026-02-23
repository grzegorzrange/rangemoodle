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
 * Polish strings for local_recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Zarządzanie rekrutacjami';
$string['recruitments'] = 'Rekrutacje';
$string['addrecruitment'] = 'Dodaj rekrutację';
$string['editrecruitment'] = 'Edytuj rekrutację';
$string['deleterecruitment'] = 'Usuń rekrutację';
$string['recruitmentname'] = 'Nazwa';
$string['recruitmentdate'] = 'Data rekrutacji';
$string['basecategory'] = 'Kategoria bazowa';
$string['archivecourse'] = 'Kurs archiwum';
$string['preparationcourse'] = 'Kurs przygotowania';
$string['quizescourse'] = 'Kurs testów wewnętrznych';
$string['cohort'] = 'Kohorta';
$string['choose'] = '-- Wybierz --';
$string['confirmdelete'] = 'Czy na pewno chcesz usunąć rekrutację "{$a}"? Wszystkie kursy (kierunki) i ich kohorty zostaną usunięte.';
$string['recruitmentsaved'] = 'Rekrutacja została zapisana.';
$string['recruitmentdeleted'] = 'Rekrutacja została usunięta.';
$string['norecruitments'] = 'Nie znaleziono rekrutacji.';
$string['cohortfor'] = 'Kohorta dla rekrutacji: {$a}';
$string['cohortfor_direction'] = 'Kohorta dla: {$a->recruitment} - {$a->direction}';
$string['privacy:metadata'] = 'Wtyczka rekrutacji nie przechowuje danych osobowych bezpośrednio. Przypisania użytkowników są zarządzane przez członkostwo w kohortach.';
$string['eventrecruitmentcreated'] = 'Rekrutacja utworzona';
$string['eventrecruitmentupdated'] = 'Rekrutacja zaktualizowana';
$string['eventrecruitmentdeleted'] = 'Rekrutacja usunięta';
$string['assignedcourses'] = 'Przypisane kursy';
$string['enter'] = 'Wejdź';
$string['directions'] = 'Kursy (kierunki)';
$string['directionname'] = 'Nazwa kursu (kierunku)';
$string['adddirection'] = 'Dodaj kurs';
$string['editdirection'] = 'Edytuj kurs (kierunek)';
$string['deletedirection'] = 'Usuń kurs (kierunek)';
$string['confirmdeletedirection'] = 'Czy na pewno chcesz usunąć kurs (kierunek) "{$a}" i jego kohortę?';
$string['directionsaved'] = 'Kurs (kierunek) został zapisany.';
$string['directiondeleted'] = 'Kurs (kierunek) został usunięty.';
$string['backtorecruitments'] = 'Powrót do rekrutacji';
$string['copystatus'] = 'Status kopiowania';
$string['copyinprogress'] = 'Kopiowanie w toku...';
$string['copydone'] = 'Gotowe';
$string['users'] = 'Użytkownicy';
$string['importusers'] = 'Importuj użytkowników';
$string['downloadcsvsample'] = 'Pobierz przykładowy CSV';
$string['declaration'] = 'Deklaracja';
$string['declarationyes'] = 'Tak';
$string['declarationno'] = 'Nie';
$string['toggledeclaration'] = 'Przełącz deklarację';
$string['removeuser'] = 'Usuń użytkownika';
$string['confirmremoveuser'] = 'Czy na pewno chcesz usunąć użytkownika "{$a}" z tego kierunku?';
$string['userimported'] = 'Użytkownicy zaimportowani. Utworzono: {$a->created}, Zaktualizowano: {$a->updated}, Błędy: {$a->errors}, Powiadomiono: {$a->notified}.';
$string['importerror'] = 'Błąd w wierszu {$a->line}: {$a->message}';
$string['backtousers'] = 'Powrót do użytkowników';
$string['csvformat'] = 'Format CSV: username;firstname;lastname;email;declaration (tak/nie)';
$string['exportusers'] = 'Eksportuj użytkowników';
$string['setdeclaration'] = 'Odznacz deklarację';
$string['confirmsetdeclaration'] = 'Czy na pewno chcesz oznaczyć deklarację dla użytkownika "{$a}"? Ta akcja jest nieodwracalna i spowoduje wysłanie powiadomienia.';
$string['declarationset'] = 'Deklaracja została oznaczona i powiadomienie wysłane.';
$string['notificationstatus'] = 'Powiadomienie';
$string['notifiedyes'] = 'Wysłano';
$string['notifiedno'] = 'Nie wysłano';
$string['examregistrationsubject'] = 'Zapis na egzamin jest już dostępny';
$string['examregistrationbody'] = 'Witaj, zapis na egzamin dla kursu {$a->direction} (rekrutacja: {$a->recruitment}) jest już dostępny. Zaloguj się na platformę.';
$string['examregistrationsms'] = 'Zapis na egzamin dla {$a->direction} ({$a->recruitment}) jest juz dostepny. Zaloguj sie na platforme.';
$string['messageprovider:exam_registration'] = 'Powiadomienie o zapisie na egzamin';
