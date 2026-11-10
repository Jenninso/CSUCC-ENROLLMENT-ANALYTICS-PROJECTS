@echo off
setlocal
title CAPSTONE Laravel (port 8000)
set "PHP=C:\xampp new\php\php.exe"
cd /d "%~dp0"

if not exist "%PHP%" (
  echo PHP not found at %PHP%
  pause
  exit /b 1
)

:loop
echo.
echo [%date% %time%] Starting http://127.0.0.1:8000
echo Keep this window open. Closing it stops the site.
echo.
"%PHP%" artisan serve --host=127.0.0.1 --port=8000
echo.
echo [%date% %time%] Server stopped (exit %ERRORLEVEL%). Restarting in 3 seconds...
timeout /t 3 /nobreak >nul
goto loop
