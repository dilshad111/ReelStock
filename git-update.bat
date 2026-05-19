@echo off
setlocal

:: Banner
echo ======================================================
echo          ReelStock - Update from Repository
echo ======================================================

:: Step 1: Force Update from Repository
echo.
echo [1/6] Discarding local changes and forcing update from repository...
git fetch origin main
git reset --hard origin/main
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: Failed to reset from Git. 
    pause
    exit /b %ERRORLEVEL%
)



:: Step 2: Install/Update Composer Dependencies
echo.
echo [2/6] Updating PHP dependencies (Composer)...
:: Check if composer.phar exists, otherwise use global composer
if exist "composer.phar" (
    php composer.phar install --no-interaction --prefer-dist
) else (
    composer install --no-interaction --prefer-dist
)
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: Composer install failed.
    pause
    exit /b %ERRORLEVEL%
)

:: Step 3: Install/Update NPM Dependencies
echo.
echo [3/6] Updating Node dependencies (NPM)...
call npm install
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: NPM install failed.
    pause
    exit /b %ERRORLEVEL%
)

:: Step 4: Build Assets
echo.
echo [4/6] Compiling assets (Vite)...
call npm run build
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: Asset compilation failed.
    pause
    exit /b %ERRORLEVEL%
)

:: Step 5: Run Migrations
echo.
echo [5/6] Running database migrations...
php artisan migrate --force
if %ERRORLEVEL% neq 0 (
    echo.
    echo ERROR: Database migration failed.
    pause
    exit /b %ERRORLEVEL%
)

:: Step 6: Clear and Refresh Cache
echo.
echo [6/6] Clearing and refreshing application cache...
php artisan optimize:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache

echo.
echo ======================================================
echo    Update Complete! Project is now up to date.
echo ======================================================
pause
