@echo off

cls

set AnalysisLevel=9
set OutputFile=./output.txt
set ConfigFile=./phpstan.neon

echo -------------------------------------------------------
echo RUNNING PHPSTAN @ LEVEL %AnalysisLevel%
echo -------------------------------------------------------

echo.

call ../../vendor/bin/phpstan analyse -c %ConfigFile% -l %AnalysisLevel% > %OutputFile%

echo.
echo Saved to %OutputFile%.
echo.

start "" "%OutputFile%"
