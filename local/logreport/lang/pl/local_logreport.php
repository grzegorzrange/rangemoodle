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
 * Polish language strings for local_logreport.
 *
 * @package    local_logreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Raport logów';
$string['logreport'] = 'Raport logów';
$string['eventname'] = 'Nazwa zdarzenia';
$string['description'] = 'Opis';
$string['timecreated'] = 'Data';
$string['filter_eventname'] = 'Nazwa zdarzenia';
$string['filter_description'] = 'Opis';
$string['filter_datefrom'] = 'Data od';
$string['filter_dateto'] = 'Data do';
$string['filter'] = 'Filtruj';
$string['resetfilters'] = 'Resetuj';
$string['privacy:metadata'] = 'Wtyczka Raport logów nie przechowuje żadnych danych osobowych. Odczytuje jedynie istniejące dane z logów.';

// Generic Polish event description templates.
$string['eventdesc_generic'] = 'Użytkownik {$a->user} wykonał akcję „{$a->action}" na obiekcie „{$a->target}" (komponent: {$a->component}).';
$string['eventdesc_generic_course'] = 'Użytkownik {$a->user} wykonał akcję „{$a->action}" na obiekcie „{$a->target}" w kursie „{$a->course}" (komponent: {$a->component}).';
$string['eventdesc_generic_module'] = 'Użytkownik {$a->user} wykonał akcję „{$a->action}" na module „{$a->module}" w kursie „{$a->course}" (komponent: {$a->component}).';
$string['eventdesc_questions_imported'] = 'Użytkownik {$a->user} zaimportował pytania do bazy pytań.';

// CRUD action translations.
$string['crud_c'] = 'utworzył';
$string['crud_r'] = 'wyświetlił';
$string['crud_u'] = 'zaktualizował';
$string['crud_d'] = 'usunął';

// Common event descriptions for local plugins.
$string['eventdesc_created'] = 'Użytkownik {$a->user} utworzył {$a->target} (komponent: {$a->component}).';
$string['eventdesc_updated'] = 'Użytkownik {$a->user} zaktualizował {$a->target} (komponent: {$a->component}).';
$string['eventdesc_deleted'] = 'Użytkownik {$a->user} usunął {$a->target} (komponent: {$a->component}).';
$string['eventdesc_viewed'] = 'Użytkownik {$a->user} wyświetlił {$a->target} (komponent: {$a->component}).';

// Component name translations.
$string['component_local_recruitment'] = 'Rekrutacje';
$string['component_local_dashboard'] = 'Pulpit / Ogłoszenia';
$string['component_local_financial'] = 'Sprawy finansowe';
$string['component_local_organizational'] = 'Sprawy organizacyjne';
$string['component_local_schedule'] = 'Harmonogram';
$string['component_core'] = 'System';

// Target translations.
$string['target_recruitment'] = 'rekrutację';
$string['target_announcement'] = 'ogłoszenie';
$string['target_financial'] = 'sprawę finansową';
$string['target_organizational'] = 'sprawę organizacyjną';
$string['target_schedule'] = 'harmonogram';
$string['target_course'] = 'kurs';
$string['target_user'] = 'użytkownika';
$string['target_role'] = 'rolę';
$string['target_category'] = 'kategorię';
$string['target_cohort'] = 'kohortę';
$string['target_questions'] = 'pytania';
