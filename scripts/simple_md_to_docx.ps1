# Simple script to convert Markdown to Word using Word COM object

# Input and output paths
$reportsDir = Join-Path -Path $PSScriptRoot -ChildPath "..\reports"
$inputFile = Join-Path -Path $reportsDir -ChildPath "Asset_Management_System_Report_$(Get-Date -Format 'yyyyMMdd').md"
$outputFile = Join-Path -Path $reportsDir -ChildPath "Asset_Management_System_Report_$(Get-Date -Format 'yyyyMMdd').docx"

# Create Word application
$word = New-Object -ComObject Word.Application
$word.Visible = $false

# Create a new document
$doc = $word.Documents.Add()
$selection = $word.Selection

# Set default font
$selection.Font.Name = "Calibri"
$selection.Font.Size = 11

# Read markdown content
$content = Get-Content -Path $inputFile -Raw

# Split into paragraphs
$paragraphs = $content -split "\r?\n\r?\n"

foreach ($para in $paragraphs) {
    # Skip empty paragraphs
    if ([string]::IsNullOrWhiteSpace($para)) {
        $selection.TypeParagraph()
        continue
    }
    
    # Handle headers
    if ($para.StartsWith("# ")) {
        $selection.Style = "Title"
        $selection.TypeText($para.Substring(2).Trim())
    }
    elseif ($para.StartsWith("## ")) {
        $selection.Style = "Heading 1"
        $selection.TypeText($para.Substring(3).Trim())
    }
    elseif ($para.StartsWith("### ")) {
        $selection.Style = "Heading 2"
        $selection.TypeText($para.Substring(4).Trim())
    }
    # Handle lists
    elseif ($para.Trim().StartsWith("- ")) {
        $selection.Style = "List Bullet"
        $selection.TypeText($para.Trim().Substring(2).Trim())
    }
    # Handle tables
    elseif ($para.Contains("|")) {
        $selection.Style = "Normal"
        $selection.TypeText($para.Trim())
    }
    # Normal text
    else {
        $selection.Style = "Normal"
        $selection.TypeText($para.Trim())
    }
    
    $selection.TypeParagraph()
}

# Save the document
$outputDir = [System.IO.Path]::GetDirectoryName($outputFile)
if (-not [string]::IsNullOrEmpty($outputDir) -and -not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

# Use a simpler approach to save the document
$doc.SaveAs2($outputFile, 16)  # 16 = wdFormatDocumentDefault

# Clean up
$doc.Close()
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
[System.GC]::Collect()
[System.GC]::WaitForPendingFinalizers()

Write-Host "Word document created successfully at: $outputFile" -ForegroundColor Green
