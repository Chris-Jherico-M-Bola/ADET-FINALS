<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel">
  <br>
  <img src="https://raw.githubusercontent.com/vitejs/vite/main/docs/public/logo.svg" width="48" alt="+">
  <img src="https://raw.githubusercontent.com/vuejs/art/master/logo.svg" width="48" alt="Vue">
</p>

<h1 align="center">ADET Presenter</h1>

<p align="center">
  <a href="https://adet-presenter.onrender.com/">
    <img src="https://img.shields.io/badge/Live_Demo-46E3B7?style=for-the-badge&logo=render&logoColor=black" alt="Live Demo">
  </a>
</p>

<p align="center">
  Upload PowerPoint decks and present them with voice navigation.<br>
  An AI-powered notes assistant helps you chat with your slide notes.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#local-setup">Setup</a> •
  <a href="#project-members">Team</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/Vue.js-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white" alt="Vue.js">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <br>
  <img src="https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/Supabase-3FCF8E?style=for-the-badge&logo=supabase&logoColor=white" alt="Supabase">
  <img src="https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white" alt="PostgreSQL">
  <img src="https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/NGINX-009639?style=for-the-badge&logo=nginx&logoColor=white" alt="NGINX">
</p>

---

<h2 id="features">Features</h2>

<ul>
  <li>Upload <code>.ppt</code>, <code>.pptx</code>, and <code>.pdf</code> files (up to 100MB)</li>
  <li>Convert decks to PDF with <code>pptx-to-pdf</code> (no LibreOffice required)</li>
  <li>Present slides in a Vue SPA with keyboard and voice navigation</li>
  <li>AI-powered Notes Assistant — chat with your slide notes via Puter.js</li>
  <li>Voice transcription to auto-generate and save slide notes</li>
  <li>Supabase PostgreSQL database + Supabase Storage for file serving</li>
  <li>Filter, search, and sort your presentation library</li>
  <li>Retry failed conversions and download source files</li>
</ul>

<h2 id="tech-stack">Tech Stack</h2>

<table>
  <tr>
    <th align="left">Layer</th>
    <th align="left">Technology</th>
  </tr>
  <tr>
    <td>Backend</td>
    <td>
      <img src="https://img.shields.io/badge/Laravel_12-FF2D20?style=flat&logo=laravel&logoColor=white" alt="Laravel 12">
      <img src="https://img.shields.io/badge/PHP_8.4-777BB4?style=flat&logo=php&logoColor=white" alt="PHP 8.4">
    </td>
  </tr>
  <tr>
    <td>Frontend</td>
    <td>
      <img src="https://img.shields.io/badge/Vue_3-4FC08D?style=flat&logo=vuedotjs&logoColor=white" alt="Vue 3">
      <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black" alt="JavaScript">
      <img src="https://img.shields.io/badge/Tailwind_CSS_4-06B6D4?style=flat&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
    </td>
  </tr>
  <tr>
    <td>Build</td>
    <td>
      <img src="https://img.shields.io/badge/Vite-646CFF?style=flat&logo=vite&logoColor=white" alt="Vite">
    </td>
  </tr>
  <tr>
    <td>Database</td>
    <td>
      <img src="https://img.shields.io/badge/Supabase_PostgreSQL-3FCF8E?style=flat&logo=supabase&logoColor=white" alt="Supabase PostgreSQL">
    </td>
  </tr>
  <tr>
    <td>File Storage</td>
    <td>
      <img src="https://img.shields.io/badge/Supabase_Storage-3FCF8E?style=flat&logo=supabase&logoColor=white" alt="Supabase Storage">
    </td>
  </tr>
  <tr>
    <td>PPTX &rarr; PDF</td>
    <td>
      <img src="https://img.shields.io/badge/pptx--to--pdf-339933?style=flat&logo=nodedotjs&logoColor=white" alt="pptx-to-pdf">
    </td>
  </tr>
  <tr>
    <td>AI Chat</td>
    <td>
      <img src="https://img.shields.io/badge/Puter.js-1a1a2e?style=flat&logo=python&logoColor=white" alt="Puter.js">
    </td>
  </tr>
  <tr>
    <td>Infrastructure</td>
    <td>
      <img src="https://img.shields.io/badge/Docker-2496ED?style=flat&logo=docker&logoColor=white" alt="Docker">
      <img src="https://img.shields.io/badge/NGINX-009639?style=flat&logo=nginx&logoColor=white" alt="NGINX">
      <img src="https://img.shields.io/badge/Render-46E3B7?style=flat&logo=render&logoColor=black" alt="Render">
    </td>
  </tr>
</table>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<h2 id="local-setup">Local Setup</h2>

<ol>
  <li>
    <strong>Clone the repository</strong>
    <pre><code>git clone https://github.com/Chris-Jherico-M-Bola/ADET-FINALS.git
cd ADET-FINALS</code></pre>
  </li>
  <li>
    <strong>Install PHP dependencies</strong>
    <pre><code>composer install</code></pre>
  </li>
  <li>
    <strong>Install frontend dependencies</strong>
    <pre><code>npm install</code></pre>
  </li>
  <li>
    <strong>Configure environment</strong>
    <pre><code>cp .env.example .env</code></pre>
    Fill in your Supabase credentials:
    <ul>
      <li><code>SUPABASE_PROJECT</code> — project reference</li>
      <li><code>SUPABASE_SECRET</code> — <code>service_role</code> key</li>
      <li><code>SUPABASE_BUCKET</code> — <code>Presentation Buckets</code></li>
      <li>Database host, port, name, username, password</li>
    </ul>
  </li>
  <li>
    <strong>Run migrations</strong>
    <pre><code>php artisan migrate</code></pre>
  </li>
  <li>
    <strong>Start the app</strong>
    <pre><code>npm run dev:full</code></pre>
    Or separately:
    <pre><code>npm run dev    # frontend on :5173
php artisan serve --no-reload  # backend on :8000</code></pre>
  </li>
</ol>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<h2 id="deployment">Deployment</h2>

<p>The app is containerized with Docker and deploys on Render.</p>

<pre><code>docker build -t novakamiii/adet-presenter .
docker push novakamiii/adet-presenter:latest
</code></pre>

<p>
  <a href="https://adet-presenter.onrender.com/">
    <img src="https://img.shields.io/badge/Live_Demo-46E3B7?style=for-the-badge&logo=render&logoColor=black" alt="Live Demo">
  </a>
</p>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<h2 id="project-members">Project Members</h2>

<table>
  <tr>
    <th align="left">Name</th>
    <th align="left">Role</th>
  </tr>
  <tr>
    <td>Bola, Chris Jericho M.</td>
    <td><strong>Main Developer</strong></td>
  </tr>
  <tr>
    <td>Sevilla, Paulo Neil A.</td>
    <td><strong>Sub Developer</strong></td>
  </tr>
  <tr>
    <td>Arabaca, Irish May N.</td>
    <td>—</td>
  </tr>
  <tr>
    <td>Dumas, John Paul P.</td>
    <td>—</td>
  </tr>
  <tr>
    <td>Miñoza, Leovie S.</td>
    <td>—</td>
  </tr>
</table>

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<h2>Notes</h2>

<ul>
  <li>All routes are public — no authentication or authorization.</li>
  <li>PPTX-to-PDF conversion uses <code>pptx-to-pdf</code> via <code>node bin/convert-pptx.cjs</code>.</li>
  <li>The Notes Assistant uses Puter.js with a user-pays model (each user signs into their own Puter account).</li>
  <li>The Supabase Storage bucket is set to public for direct file serving.</li>
  <li>Audio notes transcription requires browser microphone access.</li>
</ul>

<p align="center">
  <br>
  <sub>Built with Laravel, Vue, and Supabase — ADET Final Project</sub>
  <br>
  <img src="https://img.shields.io/badge/made_with-%E2%9D%A4%EF%B8%8F_and_%E2%98%95-red?style=flat-square" alt="Made with love and coffee">
</p>
