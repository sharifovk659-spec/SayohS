@echo off
chcp 65001 >nul
cd /d "%~dp0"

if not exist "config\database.php" (
  copy /Y "config\database.example.php" "config\database.php" >nul
  echo Created config\database.php
)

echo.
echo Sayoh site: http://127.0.0.1:8080/
echo Admin:      http://127.0.0.1:8080/admin/login.php
echo.
echo Press Ctrl+C to stop.
echo.

php -S 127.0.0.1:8080 -t .
