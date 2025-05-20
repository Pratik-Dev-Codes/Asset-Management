@echo off
setlocal enabledelayedexpansion

echo Generating diagrams...

:: Check if PlantUML is installed
where plantuml >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo Error: PlantUML is not installed or not in PATH
    echo Please install PlantUML from https://plantuml.com/download
    exit /b 1
)

:: Create output directories
if not exist "docs\diagrams\png" mkdir "docs\diagrams\png"
if not exist "docs\diagrams\svg" mkdir "docs\diagrams\svg"

:: Process all .puml files
for /r "docs\diagrams" %%f in (*.puml) do (
    echo Processing %%~nxf...
    
    :: Generate PNG
    plantuml -tpng -o "..\..\docs\diagrams\png" "%%f"
    
    :: Generate SVG
    plantuml -tsvg -o "..\..\docs\diagrams\svg" "%%f"
done

echo Done! Diagrams generated in docs/diagrams/png and docs/diagrams/svg
pause
