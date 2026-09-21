@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"
title Wedding Za Premium - Local Server
color 0F

echo.
echo ======================================================
echo              WEDDING ZA PREMIUM LAUNCHER
echo ======================================================
echo.

:automode
set "PHP_EXE="

REM 1) PHP already available in PATH
for /f "delims=" %%I in ('where php 2^>nul') do (
  if not defined PHP_EXE set "PHP_EXE=%%I"
)

REM 2) Common XAMPP locations
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"

REM 3) Common Laragon locations
if not defined PHP_EXE (
  for /d %%D in ("C:\laragon\bin\php\php-*") do (
    if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
  )
)
if not defined PHP_EXE (
  for /d %%D in ("D:\laragon\bin\php\php-*") do (
    if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
  )
)

REM 4) Common WAMP locations
if not defined PHP_EXE (
  for /d %%D in ("C:\wamp64\bin\php\php*") do (
    if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
  )
)
if not defined PHP_EXE (
  for /d %%D in ("C:\wamp\bin\php\php*") do (
    if exist "%%~fD\php.exe" set "PHP_EXE=%%~fD\php.exe"
  )
)

REM 5) Scoop PHP
if not defined PHP_EXE if exist "%USERPROFILE%\scoop\apps\php\current\php.exe" set "PHP_EXE=%USERPROFILE%\scoop\apps\php\current\php.exe"

if not defined PHP_EXE goto :NO_PHP

echo [OK] PHP found:
echo      %PHP_EXE%
echo.

REM Pick a free port from a small safe list.
set "PORT="
for %%P in (8088 8089 8090 8091 9000 9001) do (
  if not defined PORT (
    powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort %%P -State Listen -ErrorAction SilentlyContinue) { exit 1 } else { exit 0 }" >nul 2>nul
    if not errorlevel 1 set "PORT=%%P"
  )
)

if not defined PORT (
  echo [ERROR] I could not find a free local port.
  echo Close other local servers and run this file again.
  echo.
  pause
  exit /b 1
)

echo [OK] Using local address:
echo      http://127.0.0.1:%PORT%
echo.
echo The browser will open automatically.
echo KEEP THIS WINDOW OPEN while you use Wedding Za.
echo Press Ctrl+C here when you want to stop the website.
echo.

REM Open browser after a short delay so PHP has time to bind the port.
start "" powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process 'http://127.0.0.1:%PORT%/'"

"%PHP_EXE%" -S 127.0.0.1:%PORT% -t "%CD%"

echo.
echo Wedding Za server stopped.
pause
exit /b 0

:NO_PHP
color 0C
echo [ERROR] PHP was not found on this Windows PC.
echo.
echo This website is a PHP project, so Windows cannot run it by
 echo double-clicking index.php like a normal HTML file.
echo.
echo EASIEST FIX:
echo   1. Install XAMPP for Windows.
echo   2. You do NOT need to configure Apache/MySQL for this preview.
echo   3. Run this START-WEDDING-ZA.bat file again.
echo.
echo The launcher will automatically detect:
echo   - PHP in PATH
echo   - C:\xampp\php\php.exe
echo   - Laragon PHP
echo   - WAMP PHP
echo.
echo If PHP is already installed somewhere else, open Command Prompt,
echo go to this folder, and run:
echo   full\path\to\php.exe -S 127.0.0.1:8088
 echo.
echo This window will stay open so you can read the error.
echo.
pause
exit /b 1
