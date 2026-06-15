# How To Use ADET Presenter

This guide explains how to run and use the website after the Laravel + Vue conversion.

## 1. What the website does

ADET Presenter lets you:

- Upload presentation files (`.ppt`, `.pptx`, or `.pdf`)
- Convert them to PDF in the backend
- Present slides in the browser
- Control slides with voice commands and keyboard shortcuts
- Manage the presentation library from the home screen

## 2. Prerequisites

Before using the site, make sure these are installed:

- PHP 8.2 or newer
- Composer
- Node.js and npm
- SQLite enabled for Laravel

## 3. Install and run the project

From the project root:

```bash
cp .env.example .env
composer install
npm install
touch database/database.sqlite
php artisan migrate
composer dev
```

Open the Laravel app in your browser, usually at:

```text
http://127.0.0.1:8000
```

## 4. Upload a presentation

On the home screen:

1. Click the upload area or drag and drop a file into it.
2. Choose a `.ppt`, `.pptx`, or `.pdf` file.
3. Optionally enter a custom title.
4. Click **Upload presentation**.

What happens next:

- The file is stored on the server.
- Laravel converts it to PDF.
- The library card updates its status automatically.

## 5. Understand presentation status

Each item in the library can show one of these states:

- `processing` - the file is being converted
- `ready` - the deck is ready to present
- `failed` - conversion failed and shows an error message

If a deck fails, use **Retry** after fixing the problem.

## 6. Start presenting

When a deck is ready:

1. Click **Present**.
2. The presenter view opens in full screen style.
3. Use the controls or voice commands to move through slides.

## 7. Navigate slides

You can move through the presentation using:

- Right Arrow
- Space
- Left Arrow
- Escape to exit presenter mode
- `M` to toggle the microphone

You can also click a slide thumbnail on the right side of the presenter view to jump directly to that slide.

## 8. Use voice commands

The app uses the browser's built-in Web Speech API.

Supported commands:

- `next`
- `forward`
- `siguiente`
- `adelante`
- `previous`
- `back`
- `anterior`
- `exit`
- `stop`
- `salir`

If wake word mode is enabled, say `Jarvis` before the command.

Examples:

- `Jarvis next`
- `Jarvis previous`
- `Jarvis exit`

## 9. Presenter layout controls

Inside presenter mode you can:

- Collapse or expand the voice control sidebar on the left
- Collapse or expand the slide thumbnail strip on the right
- View live speech transcript text
- Clear transcript history with **Clear words**

## 10. Manage presentations

From the home screen library you can:

- **Download** the original uploaded PowerPoint file
- **Present** any ready deck
- **Retry** a failed conversion
- **Delete** a presentation permanently

Deletion removes the database record and deletes the stored files.

## 11. Troubleshooting

### Voice commands do not work

- Make sure the browser supports the Web Speech API.
- Use Chrome or Edge for best results.
- Allow microphone permission in the browser.

### Upload succeeds but conversion fails

- Confirm the file is a valid `.ppt`, `.pptx`, or `.pdf`.
- Check that Node.js is on your PATH (`node --version`).
- Check the error message shown on the presentation card.

### The page looks blank on first load

- Run the frontend build once with `npm run build`.
- Then refresh the page.

## 12. Recommended workflow

1. Start the backend and frontend.
2. Upload a PowerPoint deck.
3. Wait for the status to become `ready`.
4. Click **Present**.
5. Use voice or keyboard controls during the presentation.

