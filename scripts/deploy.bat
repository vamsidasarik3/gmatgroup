@echo off
setlocal
set "BASH_EXE=C:\Program Files\Git\bin\bash.exe"
if not exist "%BASH_EXE%" (
    where bash >nul 2>nul
    if %ERRORLEVEL% EQU 0 (
        set "BASH_EXE=bash"
    ) else (
        echo [ERROR] Git Bash was not found at C:\Program Files\Git\bin\bash.exe or in PATH.
        exit /b 1
    )
)

"%BASH_EXE%" "%~dp0deploy.sh" %*
exit /b %ERRORLEVEL%
