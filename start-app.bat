@echo off
cd /d c:\xampp\htdocs\ReelStock

echo Starting MySQL...
start "" "C:\xampp\mysql_start.bat"

echo Waiting for MySQL to initialize...
timeout /t 5 /nobreak >nul

echo Starting Laravel Backend...
start "Laravel Server" php artisan serve --host=0.0.0.0 --port=8000

echo Starting Vite Frontend...
start "Vite Server" npm run dev

echo Application started. You can close this window, but keep the other windows open.
timeout /t 5
exit
