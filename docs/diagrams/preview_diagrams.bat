@echo off
echo =============================================
echo  Asset Management System - Diagram Preview
echo =============================================
echo.
echo This script will open the pre-generated diagram images.
echo.
echo If you want to generate the diagrams yourself:
echo 1. Install Java from https://www.java.com/
echo 2. Download PlantUML from https://plantuml.com/download
echo 3. Run generate_diagrams.bat in the docs folder
echo.
pause

if not exist "png" (
    echo Error: No pre-generated diagrams found.
    echo Please run generate_diagrams.bat first after installing PlantUML.
    pause
    exit /b 1
)

start "" "png\00-context.png"
start "" "png\01-main-processes.png"
start "" "png\02-asset-management.png"
start "" "png\03-asset-registration.png"

echo.
echo All diagrams opened in your default image viewer.
echo.
