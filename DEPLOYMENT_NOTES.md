# AcadVault - Deployment Recap (Oct 5, 2026)

## Live site
- URL: https://acadvault-ttcl.onrender.com
- Render service name: acadvault (auto-deploys from GitHub on push)
- GitHub repo: github.com/mikmik091801/AcadVault

## Environment variables set on Render
- APP_URL: https://acadvault-ttcl.onrender.com
- APP_KEY: (use the one in local .env or the generated one)
- DB_* : provided automatically by the Render blueprint (Postgres)
- APP_ENV: production, APP_DEBUG: **still true — set back to false!**

## Local setup
- App name: AcadVault, local URL http://localhost:8000
- DB: MySQL (database: acadvault)
- APP_KEY (local): base64:55lv4VX06rZTdH3l8HkEoDaspktDTc6pwoJ3NmvTluk=
- PHP 8.4

## TODO tomorrow
1. Set APP_DEBUG back to false in Render Environment
2. Run pending migration locally if needed:
   php artisan migrate
3. Seed demo accounts on Render: Render dashboard -> acadvault -> Shell -> php artisan db:seed
4. Note: Render free Postgres starts empty - seeds add demo users
5. Remember: uploaded files on Render free disk are wiped on redeploy

## Deployment files created
- Dockerfile (PHP 8.4, composer install, vite build, migrate on boot)
- render.yaml (web service + free Postgres blueprint)
- bootstrap/app.php patched with trustProxies for correct https URLs
