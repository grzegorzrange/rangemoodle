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
 * Polish strings for local_financial.
 *
 * @package    local_financial
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Zarządzanie sprawami finansowymi';
$string['financialmatters'] = 'Sprawy finansowe';
$string['addfinancial'] = 'Dodaj sprawę finansową';
$string['editfinancial'] = 'Edytuj sprawę finansową';
$string['deletefinancial'] = 'Usuń sprawę finansową';
$string['viewfinancial'] = 'Zobacz sprawy finansowe';
$string['financialname'] = 'Nazwa';
$string['financialcontent'] = 'Treść';
$string['recruitment'] = 'Rekrutacja';
$string['direction'] = 'Kurs (kierunek)';
$string['sendnotification'] = 'Wyślij powiadomienie po zapisaniu';
$string['financialsaved'] = 'Sprawa finansowa została zapisana.';
$string['financialdeleted'] = 'Sprawa finansowa została usunięta.';
$string['confirmdeletefinancial'] = 'Czy na pewno chcesz usunąć sprawę finansową "{$a}"?';
$string['notificationsendsuccess'] = 'Powiadomienie wysłane do {$a} użytkowników.';
$string['notificationsubject'] = 'Sprawy finansowe zaktualizowane: {$a}';
$string['notificationbody'] = 'Sprawy finansowe zostały zaktualizowane: {$a->name}

{$a->message}';
$string['notificationbody_html'] = '<p>Sprawy finansowe zostały zaktualizowane: <strong>{$a->name}</strong></p>{$a->message}';
$string['notifyhistory'] = 'Historia powiadomień';
$string['notifyhistoryfor'] = 'Historia powiadomień: {$a}';
$string['sentby'] = 'Wysłane przez';
$string['recipientcount'] = 'Odbiorcy';
$string['nofinancial'] = 'Brak spraw finansowych dla tej rekrutacji.';
$string['norecruitmentselected'] = 'Nie wybrano rekrutacji.';
$string['financial:manage'] = 'Zarządzanie sprawami finansowymi';
$string['financial:view'] = 'Przeglądanie spraw finansowych';
$string['eventfinancialcreated'] = 'Sprawa finansowa utworzona';
$string['eventfinancialupdated'] = 'Sprawa finansowa zaktualizowana';
$string['eventfinancialdeleted'] = 'Sprawa finansowa usunięta';
$string['privacy:metadata'] = 'Wtyczka spraw finansowych nie przechowuje danych osobowych bezpośrednio.';
