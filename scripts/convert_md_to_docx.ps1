param (
    [string]$MarkdownFile,
    [string]$OutputFile
)

# Check if input file exists
if (-not (Test-Path $MarkdownFile)) {
    Write-Error "Markdown file not found: $MarkdownFile"
    exit 1
}

# Set output file path if not provided
if ([string]::IsNullOrEmpty($OutputFile)) {
    $OutputFile = [System.IO.Path]::ChangeExtension($MarkdownFile, ".docx")
}

try {
    # Read markdown content
    $content = Get-Content -Path $MarkdownFile -Raw -Encoding UTF8
    
    # Create Word application
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    
    # Create a new document
    $doc = $word.Documents.Add()
    $selection = $word.Selection
    $selection.Style = $word.ActiveDocument.Styles.Item("Normal")
    
    # Split content into lines and process each line
    $lines = $content -split "`r`n"
    
    foreach ($line in $lines) {
        # Skip empty lines
        if ([string]::IsNullOrWhiteSpace($line)) {
            $selection.TypeParagraph()
            continue
        }
        
        # Handle headers
        if ($line.StartsWith("# ")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("Title")
            $selection.TypeText($line.Substring(2).Trim())
        }
        elseif ($line.StartsWith("## ")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("Heading 1")
            $selection.TypeText($line.Substring(3).Trim())
        }
        elseif ($line.StartsWith("### ")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("Heading 2")
            $selection.TypeText($line.Substring(4).Trim())
        }
        # Handle lists
        elseif ($line.Trim().StartsWith("- ")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("List Bullet")
            $selection.TypeText($line.Trim().Substring(2).Trim())
        }
        # Handle tables (simple implementation)
        elseif ($line.Contains("|")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("Normal")
            $selection.TypeText($line.Trim())
        }
        # Handle code blocks
        elseif ($line.Trim().StartsWith("```")) {
            $selection.Style = $word.ActiveDocument.Styles.Item("Normal")
            $selection.Font.Name = "Consolas"
            $selection.Font.Size = 10
            continue
        }
        # Normal text
        else {
            $selection.Style = $word.ActiveDocument.Styles.Item("Normal")
            $selection.TypeText($line.Trim())
        }
        
        $selection.TypeParagraph()
    }
    
    # Save the document
    $outputDir = [System.IO.Path]::GetDirectoryName($OutputFile)
    if (-not [string]::IsNullOrEmpty($outputDir) -and -not (Test-Path $outputDir)) {
        New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
    }
    
    $doc.SaveAs([ref]$OutputFile, [ref]16)  # 16 = wdFormatDocumentDefault
    
    Write-Host "Word document created successfully at: $OutputFile" -ForegroundColor Green
}
catch {
    Write-Error "Error converting Markdown to Word: $_"
    exit 1
}
finally {
    # Clean up
    if ($doc -ne $null) { $doc.Close() }
    if ($word -ne $null) { $word.Quit() }
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()
}

# Example usage:
# .\convert_md_to_docx.ps1 -MarkdownFile "path\to\input.md" -OutputFile "path\to\output.docx"
