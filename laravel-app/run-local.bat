@echo off
setlocal
set "PHP=C:\xampp new\php\php.exe"
cd /d "%~dp0"

echo Using: %PHP%
"%PHP%" -v
if errorlevel 1 (
  echo PHP was not found at %PHP%
  exit /b 1
)

"%PHP%" setup-pgsql.php
if errorlevel 1 exit /b 1

if not exist composer.phar (
  echo Downloading Composer...
  "%PHP%" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  "%PHP%" composer-setup.php --install-dir="%~dp0" --filename=composer.phar
  del composer-setup.php
)

echo Installing PHP packages...
"%PHP%" composer.phar install --no-interaction --prefer-dist
if errorlevel 1 exit /b 1

"%PHP%" artisan config:clear
"%PHP%" artisan migrate:fresh --seed --force
if errorlevel 1 exit /b 1

echo.
echo Site: http://127.0.0.1:8000
echo Login: admin@enrollment.com / password
echo.
"%PHP%" artisan serve --host=127.0.0.1 --port=8000
