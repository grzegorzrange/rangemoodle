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
 * Strings for component 'qtype_essay', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage essay
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['acceptedfiletypes'] = 'Akceptowane typy plików';
$string['acceptedfiletypes_help'] = 'Akceptowane typy plików można ograniczyć, wprowadzając listę rozszerzeń plików. Jeśli pole pozostanie puste, wszystkie typy plików będą dozwolone.';
$string['allowattachments'] = 'Zezwalaj na załączniki';
$string['answerfiles'] = 'Pliki odpowiedzi';
$string['answertext'] = 'Tekst odpowiedzi';
$string['attachedfiles'] = 'Załączniki: {$a}';
$string['attachmentsoptional'] = 'Załączniki są opcjonalne';
$string['attachmentsrequired'] = 'Wymagaj załączników';
$string['attachmentsrequired_help'] = 'Ta opcja określa minimalną liczbę załączników wymaganych do uznania odpowiedzi za kwalifikującą się.';
$string['err_maxminmismatch'] = 'Maksymalny limit słów musi być większy niż minimalny limit słów';
$string['err_maxwordlimit'] = 'Maksymalny limit słów jest włączony, ale nie jest ustawiony';
$string['err_maxwordlimitnegative'] = 'Maksymalny limit słów nie może być liczbą ujemną';
$string['err_minwordlimit'] = 'Minimalny limit słów jest włączony, ale nie jest ustawiony';
$string['err_minwordlimitnegative'] = 'Minimalny limit słów nie może być liczbą ujemną';
$string['formateditor'] = 'Edytor HTML';
$string['formateditorfilepicker'] = 'Edytor HTML z selektorem plików';
$string['formatmonospaced'] = 'Zwykły tekst, czcionka o stałej szerokości';
$string['formatnoinline'] = 'Brak tekstu online';
$string['formatplain'] = 'Zwykły tekst';
$string['graderinfo'] = 'Informacje dla oceniających';
$string['graderinfoheader'] = 'Informacje o oceniającym';
$string['maxbytes'] = 'Maksymalny rozmiar pliku';
$string['maxwordlimit'] = 'Maksymalny limit słów';
$string['maxwordlimit_help'] = 'Jeśli odpowiedź wymaga od uczniów wprowadzenia tekstu, jest to maksymalna liczba słów, jaką każdy uczeń może przesłać.';
$string['maxwordlimitboundary'] = 'Limit słów dla tego pytania wynosi {$a->limit} słów, a próbujesz przesłać {$a->count} słów. Skróć swoją odpowiedź i spróbuj ponownie.';
$string['minwordlimit'] = 'Minimalny limit słów';
$string['minwordlimit_help'] = 'Jeśli odpowiedź wymaga od uczniów wprowadzenia tekstu, jest to minimalna liczba słów, jaką każdy uczeń będzie mógł przesłać.';
$string['minwordlimitboundary'] = 'To pytanie wymaga odpowiedzi o długości co najmniej {$a->limit} słów, a próbujesz przesłać {$a->count} słów. Rozszerz swoją odpowiedź i spróbuj ponownie.';
$string['mustattach'] = 'Jeśli wybrano opcję „Brak tekstu online” lub odpowiedzi są opcjonalne, musisz zezwolić na co najmniej jeden załącznik.';
$string['mustrequire'] = 'Jeśli wybrano opcję „Brak tekstu online” lub odpowiedzi są opcjonalne, musisz zezwolić na co najmniej jeden załącznik.';
$string['mustrequirefewer'] = 'Nie można wymagać więcej załączników niż jest to dozwolone.';
$string['nlines'] = '{$a} wierszy';
$string['nonexistentfiletypes'] = 'Następujące typy plików nie zostały rozpoznane: {$a}';
$string['pluginname'] = 'Plk';
$string['pluginname_help'] = 'W odpowiedzi na pytanie respondent może przesłać jeden lub więcej plików i/lub wprowadzić tekst online. Można podać szablon odpowiedzi. Odpowiedzi należy oceniać ręcznie.';
$string['pluginname_link'] = 'question/type/essay';
$string['pluginnameadding'] = 'Dodawanie pytania';
$string['pluginnameediting'] = 'Edytowanie pytania';
$string['pluginnamesummary'] = 'Umożliwia odpowiedź w postaci przesłanego pliku i/lub tekstu online. Należy ją następnie ocenić ręcznie.';
$string['privacy:metadata'] = 'Wtyczka typu pytania plik pozwala na umieszczenie pliku w odpowiedzi';
$string['privacy:preference:defaultmark'] = 'Domyślny punkt ustawiony dla danego pytania.';
$string['privacy:preference:responseformat'] = 'Jaki jest format odpowiedzi (edytor HTML, zwykły tekst itp.)?';
$string['privacy:preference:responserequired'] = 'Czy uczeń musi wpisać tekst, czy jest to opcjonalne.';
$string['privacy:preference:responsefieldlines'] = 'Liczba wierszy określająca rozmiar pola wprowadzania (textarea).';
$string['privacy:preference:attachments'] = 'Liczba dozwolonych załączników.';
$string['privacy:preference:attachmentsrequired'] = 'Liczba wymaganych załączników.';
$string['privacy:preference:maxbytes'] = 'Maksymalny rozmiar pliku.';
$string['responsefieldlines'] = 'Rozmiar pola wprowadzania danych';
$string['responseformat'] = 'Format odpowiedzi';
$string['responseoptions'] = 'Opcje odpowiedzi';
$string['responserequired'] = 'Wymagaj tekstu';
$string['responsenotrequired'] = 'Podanie tekstu jest opcjonalne';
$string['responseisrequired'] = 'Wymagaj od ucznia wprowadzenia tekstu';
$string['responsetemplate'] = 'Szablon odpowiedzi';
$string['responsetemplateheader'] = 'Szablon odpowiedzi';
$string['responsetemplate_help'] = 'Wszelki tekst wpisany w tym miejscu będzie wyświetlany w polu odpowiedzi, gdy rozpocznie się nowa próba odpowiedzi na pytanie.';
$string['wordcount'] = 'Liczba słów: {$a}';
$string['wordcounttoofew'] = 'Liczba słów: {$a->count}, mniejsza niż wymagana liczba {$a->limit} słów.';
$string['wordcounttoomuch'] = 'Liczba słów: {$a->count}, większa niż limit {$a->limit} słów.';
