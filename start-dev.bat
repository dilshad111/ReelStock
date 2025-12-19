@echo off
cd /d c:\xampp\htdocs\reelStock
start cmd /k "npm run dev"
start cmd /k "php artisan serve --host=192.168.10.47 --port=8000"