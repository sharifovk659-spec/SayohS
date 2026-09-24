@echo off
chcp 65001 >nul
set "MYSQLD=C:\Program Files\MySQL\MySQL Server 8.4\bin\mysqld.exe"
set "INI=C:\ProgramData\MySQL\MySQL Server 8.4\my.ini"

if not exist "%MYSQLD%" (
  echo MySQL Server 8.4 not found at: %MYSQLD%
  exit /b 1
)

netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul
if %ERRORLEVEL%==0 (
  echo MySQL already running on port 3306.
  exit /b 0
)

echo Starting MySQL...
start "" /B "%MYSQLD%" --defaults-file="%INI%"
timeout /t 4 /nobreak >nul
netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul
if %ERRORLEVEL%==0 (
  echo MySQL started OK.
) else (
  echo MySQL failed to start. Check C:\ProgramData\MySQL\MySQL Server 8.4\Data\*.err
  exit /b 1
)
