# Introduce new language

1. Check if there is already an issue opened that introduces the new language.
2. If not, you open a new issue and put the language in the title of the issue.
3. Fork the repo.
4. Create a new branch like `ISSUE-xxx` using `git checkout -b [name_of_your_new_branch]`
3. Create a new folder with the 2-lettered country code.
4. Copy the php-files from the en-folder to stay up-to-date.
5. Run `php artisan trans:publish [2-lettered country code] --force`
6. Update the other translation-files.
7. Commit and open a PR

# Update existing language

1. Open a issue and say what you are going to update.
2. Fork the repo.
3. Create a new branch like `ISSUE-xxx` using `git checkout -b [name_of_your_new_branch]`
5. Run `php artisan trans:publish [2-lettered country code] --force`
5. Update the translation-files.
6. Commit and open a PR.

# Files
**Make sure you have the following files in the folder.**

## Custom files
- application.php -> strings which are used throughout the application.
- countries.php -> strings which are used throughout the application for countries and national teams.
- cup_stages.php -> strings which are used to show the cup-stages
- injuries.php -> strings which are used to show the reason why a player is sidelined
- leagues.php -> strings which are used to show the leagues

## Default files

- auth.php
- pagination.php
- passwords.php
- validation.php
