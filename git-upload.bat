@echo off
echo Checking git status...
git status

echo.
echo Adding all changes...
git add .

echo.
echo Committing changes...
git commit -m "Reel Number Issue resolved multi user reel receipt & Customer, Carton Sketch Form Removed"

echo.
echo Pushing to origin main...
git push -u origin main

echo.
echo Done.
pause