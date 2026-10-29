@echo off
setlocal
call "%~dp0scripts\deploy.bat" %*
exit /b %ERRORLEVEL%
