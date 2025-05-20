# PowerShell script for Windows development environment setup

Write-Host "🚀 Setting up development environment for Asset Management System..." -ForegroundColor Cyan

# Check if Chocolatey is installed
if (-not (Get-Command choco -ErrorAction SilentlyContinue)) {
    Write-Host "❌ Chocolatey is not installed. Installing..." -ForegroundColor Yellow
    Set-ExecutionPolicy Bypass -Scope Process -Force
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    Invoke-Expression ((New-Object System.Net.WebClient).DownloadString('https://chocolatey.org/install.ps1'))
   
    # Refresh PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User") 
} else {
    Write-Host "✅ Chocolatey is already installed" -ForegroundColor Green
}

# Install Java if not installed
if (-not (Get-Command java -ErrorAction SilentlyContinue)) {
    Write-Host "📦 Installing Java..." -ForegroundColor Cyan
    choco install -y jre8
    
    # Refresh PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
} else {
    Write-Host "✅ Java is already installed" -ForegroundColor Green
}

# Install Graphviz
if (-not (Get-Command dot -ErrorAction SilentlyContinue)) {
    Write-Host "📦 Installing Graphviz..." -ForegroundColor Cyan
    choco install -y graphviz
} else {
    Write-Host "✅ Graphviz is already installed" -ForegroundColor Green
}

# Download PlantUML if not present
$plantUmlPath = "$env:USERPROFILE\plantuml.jar"
if (-not (Test-Path $plantUmlPath)) {
    Write-Host "📦 Downloading PlantUML..." -ForegroundColor Cyan
    Invoke-WebRequest -Uri "https://github.com/plantuml/plantuml/releases/latest/download/plantuml.jar" -OutFile $plantUmlPath
    
    # Add PlantUML to PATH
    [Environment]::SetEnvironmentVariable("PLANTUML_JAR", $plantUmlPath, [System.EnvironmentVariableTarget]::User)
    
    # Create a batch file to run PlantUML
    $batchContent = "@echo off`r\njava -jar `"$plantUmlPath`" %*"
    Set-Content -Path "$env:USERPROFILE\bin\plantuml.bat" -Value $batchContent -Force
    
    # Add to PATH if not already there
    $userPath = [Environment]::GetEnvironmentVariable("Path", [System.EnvironmentVariableTarget]::User)
    if ($userPath -notlike "*$env:USERPROFILE\bin*") {
        [Environment]::SetEnvironmentVariable("Path", "$userPath;$env:USERPROFILE\bin", [System.EnvironmentVariableTarget]::User)
    }
    
    # Refresh PATH for current session
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
} else {
    Write-Host "✅ PlantUML is already installed" -ForegroundColor Green
}

# Install Node.js if not installed
if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "📦 Installing Node.js..." -ForegroundColor Cyan
    choco install -y nodejs
    
    # Refresh PATH
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
} else {
    Write-Host "✅ Node.js is already installed" -ForegroundColor Green
}

# Initialize npm project if needed
if (-not (Test-Path "package.json")) {
    Write-Host "📦 Initializing npm project..." -ForegroundColor Cyan
    npm init -y
}

# Install Husky for Git hooks
Write-Host "📦 Setting up Git hooks with Husky..." -ForegroundColor Cyan
if (-not (Get-Command husky -ErrorAction SilentlyContinue)) {
    npm install husky --save-dev
}
npx husky install

# Make sure the pre-commit hook exists
if (-not (Test-Path ".husky/pre-commit")) {
    New-Item -ItemType Directory -Path ".husky" -Force | Out-Null
    @"
#!/bin/sh
. "`$(dirname -- "`$0")/_/husky.sh"

# Check if any .puml files are staged or modified
if git diff --cached --name-only | grep -q '\.puml\$' || \
   git diff --name-only | grep -q '\.puml\$'; then
  echo "Detected changes in .puml files. Generating diagrams..."
  
  # Generate diagrams
  if command -v plantuml &> /dev/null; then
    plantuml -tpng -o docs/diagrams/png docs/diagrams/*.puml
    plantuml -tsvg -o docs/diagrams/svg docs/diagrams/*.puml
    
    # Add generated images to the commit
    git add docs/diagrams/png/*.png docs/diagrams/svg/*.svg
  else
    echo "Warning: PlantUML not found. Please install it to generate diagrams."
    echo "Visit https://plantuml.com/ for installation instructions."
  fi
fi
"@ | Out-File -FilePath ".husky/pre-commit" -Encoding utf8

    # Make the script executable (for Git Bash)
    if (Get-Command bash -ErrorAction SilentlyContinue) {
        bash -c 'chmod +x .husky/pre-commit'
    }
}

Write-Host "\n✅ Development environment setup complete!" -ForegroundColor Green
Write-Host "\n🚀 Next steps:" -ForegroundColor Cyan
Write-Host "1. Make sure to restart your terminal or run 'refreshenv' to update environment variables"
Write-Host "2. Run 'composer install' to install PHP dependencies"
Write-Host "3. Run 'npm install' to install frontend dependencies"
Write-Host "4. Run 'php artisan docs:generate' to generate initial diagrams"
Write-Host "\nHappy coding! 🎉" -ForegroundColor Cyan
