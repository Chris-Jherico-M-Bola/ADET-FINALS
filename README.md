# ADET Presenter

Laravel 12 + Vue 3 presentation app for uploading PowerPoint decks and presenting them with voice navigation.

## Core features

- Upload `.ppt` and `.pptx` files
- Convert decks to PDF with LibreOffice
- Present slides in a Vue SPA
- Navigate with voice commands
- Delete, retry, and download presentations from the library

## Local setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Install frontend dependencies:
   ```bash
   npm install
   ```
3. Make sure `database/database.sqlite` exists
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the app:
   ```bash
   npm run dev
   php artisan serve
   ```

## Notes

- LibreOffice must be installed for PPT/PPTX conversion.
- The app uses polling and local browser speech recognition.
