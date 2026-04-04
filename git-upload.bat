@echo off
echo Checking git status...
git status

echo.
echo Adding all changes...
git add .

echo.
echo Committing changes...
git commit -m "Management Dashboard Tab Added"

echo.
echo Pushing to origin main...
git push -u origin main

echo.
echo Done.
pause