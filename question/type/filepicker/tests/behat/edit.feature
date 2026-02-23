@qtype @qtype_filepicker
Feature: Test editing an Filepicker question
  As a teacher
  In order to be able to update my Filepicker question
  I need to edit them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype | name      | template         |
      | Test questions   | filepicker | filepicker-001 | editor           |
      | Test questions   | filepicker | filepicker-002 | editorfilepicker |
      | Test questions   | filepicker | filepicker-003 | plain            |

  Scenario: Edit an Filepicker question
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I choose "Edit question" action for "filepicker-001" in the question bank
    And I set the following fields to these values:
      | Question name | |
    And I press "id_submitbutton"
    And I should see "You must supply a value here."
    And I set the following fields to these values:
      | Question name   | Edited filepicker-001 name |
      | Response format | No online text        |
    And I press "id_submitbutton"
    And I should see "When \"No online text\" is selected, or responses are optional, you must allow at least one attachment."
    And I set the following fields to these values:
      | Response format | Plain text |
    And I press "id_submitbutton"
    Then I should see "Edited filepicker-001 name"
