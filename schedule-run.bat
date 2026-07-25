@echo off
cd /d "C:\laragon\www\AbsensiFaceRecognition"
"C:\laragon\bin\php\php-8.5.3-Win32-vs17-x64 (3)\php.exe" artisan schedule:run >> storage\logs\schedule-run.log 2>&1
