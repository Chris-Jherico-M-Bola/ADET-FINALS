# ADET Presenter

Laravel 12 + Vue 3 presentation app for uploading PowerPoint decks and presenting them with voice navigation. Features an AI-powered notes assistant, Supabase PostgreSQL for storage, and a dark-themed SPA.

## Core features

- Upload `.ppt`, `.pptx`, and `.pdf` files (up to 50MB)
- Convert decks to PDF with the `pptx-to-pdf` converter (no LibreOffice required)
- Present slides in a Vue SPA with keyboard and voice navigation
- AI-powered notes assistant with Puter.js (chat with your slide notes)
- Voice transcription to auto-generate slide notes
- Supabase PostgreSQL database and Supabase Storage for file serving
- Filter, search, and sort your presentation library
- Retry failed conversions and download source files

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.3+) |
| Frontend | Vue 3 (JavaScript, no TypeScript) |
| Database | Supabase PostgreSQL |
| File Storage | Supabase Storage (REST API) |
| PPTX→PDF | `pptx-to-df` via Node.js |
| AI Chat | Puter.js (user-pays model) |
| CSS | Tailwind CSS 4 (dark theme) |
| Build | Vite |

## Local setup

1. Install PHP dependencies:
   ```bash
   composer install
   ```
2. Install frontend dependencies:
   ```bash
   npm install
   ```
3. Copy `.env.example` to `.env` and configure Supabase credentials:
   ```bash
   cp .env.example .env
   ```
   Required environment variables:
   - `SUPABASE_PROJECT` — Supabase project ref
   - `SUPABASE_SECRET` — Supabase service_role key
   - `SUPABASE_BUCKET` — Storage bucket name (`Presentation Buckets`)
   - Database credentials (host, port, database, username, password)

4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the app (both servers):
   ```bash
   npm run dev:full
   ```
   Or separately:
   ```bash
   npm run dev    # frontend (Vite on :5173)
   php artisan serve --no-reload  # backend (:8000)
   ```

## Project Members

| Name | Role |
|------|------|
| Arabaca, Irish May N. | |
| Bola, Chris Jericho M. | Main Dev |
| Dumas, John Paul P. | |
| Miñoza, Leovie S. | |
| Sevilla, Paulo Neil A. | Sub Dev |

## Notes

- All routes are public — no authentication or authorization.
- PPTX-to-PDF conversion uses the `pptx-to-pdf` npm package via `node bin/convert-pptx.cjs`.
- The Notes Assistant uses Puter.js with a user-pays model (each user signs into their own Puter account).
- The Supabase Storage bucket is set to public for direct file serving.
- Audio notes transcription requires browser microphone access.
