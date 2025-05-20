<#
.SYNOPSIS
    Generates PNG and SVG diagrams from PlantUML source files.
.DESCRIPTION
    This script searches for .puml files in the current directory and subdirectories,
    then generates PNG and SVG versions of each diagram using PlantUML.
.NOTES
    Version: 1.0
    Author: Asset Management System Team
    Requires: PlantUML (https://plantuml.com/)
#>

# Configuration
$plantUmlJar = "plantuml.jar"  # Update this path if needed
$outputDirPng = Join-Path $PSScriptRoot "diagrams\png"
$outputDirSvg = Join-Path $PSScriptRoot "diagrams\svg"
$sourceDir = Join-Path $PSScriptRoot "diagrams"

# Create output directories if they don't exist
if (-not (Test-Path $outputDirPng)) {
    New-Item -ItemType Directory -Path $outputDirPng -Force | Out-Null
}
if (-not (Test-Path $outputDirSvg)) {
    New-Item -ItemType Directory -Path $outputDirSvg -Force | Out-Null
}

# Check if PlantUML is available
$plantUmlPath = Get-Command plantuml -ErrorAction SilentlyContinue

if (-not $plantUmlPath) {
    Write-Host "PlantUML not found in PATH. Checking for plantuml.jar..." -ForegroundColor Yellow
    
    if (Test-Path $plantUmlJar) {
        $plantUmlPath = $plantUmlJar
        $useJar = $true
    } else {
        Write-Host "Error: PlantUML not found. Please install PlantUML and ensure it's in your PATH." -ForegroundColor Red
        Write-Host "Download from: https://plantuml.com/download" -ForegroundColor Cyan
        exit 1
    }
}

# Find all .puml files
$pumlFiles = Get-ChildItem -Path $sourceDir -Filter "*.puml" -Recurse

if ($pumlFiles.Count -eq 0) {
    Write-Host "No .puml files found in $sourceDir" -ForegroundColor Yellow
    exit 0
}

# Process each .puml file
Write-Host "Generating diagrams from $($pumlFiles.Count) files..." -ForegroundColor Cyan

foreach ($file in $pumlFiles) {
    $relativePath = $file.FullName.Substring($PSScriptRoot.Length + 1)
    Write-Host "Processing $relativePath..." -ForegroundColor White
    
    # Generate PNG
    $pngOutput = Join-Path $outputDirPng ($file.BaseName + ".png")
    if ($useJar) {
        java -jar $plantUmlJar -tpng -o $outputDirPng $file.FullName
    } else {
        plantuml -tpng -o $outputDirPng $file.FullName
    }
    
    # Generate SVG
    $svgOutput = Join-Path $outputDirSvg ($file.BaseName + ".svg")
    if ($useJar) {
        java -jar $plantUmlJar -tsvg -o $outputDirSvg $file.FullName
    } else {
        plantuml -tsvg -o $outputDirSvg $file.FullName
    }
    
    Write-Host "  → Generated: $pngOutput" -ForegroundColor Green
    Write-Host "  → Generated: $svgOutput" -ForegroundColor Green
}

Write-Host "\nDiagram generation complete!" -ForegroundColor Green
Write-Host "PNG files saved to: $outputDirPng"
Write-Host "SVG files saved to: $outputDirSvg"

# Open the output directory
if ($IsWindows) {
    explorer $outputDirPng
}
