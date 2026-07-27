@echo off
cd /d "C:\laragon\www\AbsensiFaceRecognition"
"C:\laragon\bin\php\php-8.5.3-Win32-vs17-x64 (3)\php.exe" artisan queue:work --tries=3 --max-time=3600 --sleep=3 >> storage\logs\queue-work.log 2>&1
