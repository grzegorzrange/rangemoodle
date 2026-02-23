@qtype @qtype_filepicker
Feature: Test duplicating a quiz containing an Filepicker question
  As a teacher
  In order re-use my courses containing Filepicker questions
  I need to be able to backup and restore them

  Background:
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name      | template         |
      | Test questions   | filepicker     | filepicker-001 | editor           |
      | Test questions   | filepicker     | filepicker-002 | editorfilepicker |
      | Test questions   | filepicker     | filepicker-003 | plain            |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | filepicker-001 | 1 |
      | filepicker-002 | 1 |
      | filepicker-003 | 1 |
    And the following config values are set as admin:
      | enableasyncbackup | 0 |

  @javascript
  Scenario: Backup and restore a course containing 3 Filepicker questions
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    Then I should see "filepicker-001"
    And I should see "filepicker-002"
    And I should see "filepicker-003"
    And I choose "Edit question" action for "filepicker-001" in the question bank
    Then the following fields match these values:
      | Question name              | filepicker-001                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | HTML editor                                             |
      | Require text               | Require the student to enter text                       |
    And I press "Cancel"
    And I choose "Edit question" action for "filepicker-002" in the question bank
    Then the following fields match these values:
      | Question name              | filepicker-002                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | HTML editor with file picker                            |
      | Require text               | Require the student to enter text                       |
    And I press "Cancel"
    And I choose "Edit question" action for "filepicker-003" in the question bank
    Then the following fields match these values:
      | Question name              | filepicker-003                                               |
      | Question text              | Please write a story about a frog.                      |
      | General feedback           | I hope your story had a beginning, a middle and an end. |
      | Response format            | Plain text                                              |
      | Require text               | Require the student to enter text                       |
