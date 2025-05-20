# Create Word application object
$word = New-Object -ComObject Word.Application
$word.Visible = $false

# Create a new document
$doc = $word.Documents.Add()
$selection = $word.Selection

# Add title
$selection.Style = "Title"
$selection.TypeText("Asset Management System")
$selection.TypeParagraph()
$selection.Style = "Subtitle"
$selection.TypeText("Project Report")
$selection.TypeParagraph()
$selection.TypeText("Prepared for: NEEPCO (North Eastern Electric Power Corporation Limited)")
$selection.TypeParagraph()
$selection.TypeText((Get-Date -Format "MMMM dd, yyyy"))
$selection.TypeParagraph()

# Add table of contents
$selection.Style = $word.ActiveDocument.Styles("Heading 1")
$selection.TypeText("Table of Contents")
$selection.TypeParagraph()
$toc = $doc.TablesOfContents.Add($selection.Range)
$selection.TypeParagraph()

# 1. Executive Summary
$selection.Style = $word.ActiveDocument.Styles("Heading 1")
$selection.TypeText("1. Executive Summary")
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Normal")
$selection.TypeText("The Asset Management System is a comprehensive solution developed for NEEPCO to efficiently track, manage, and maintain their physical and digital assets. Built on Laravel 10, the system provides a robust platform for asset lifecycle management with advanced tracking and reporting capabilities.")
$selection.TypeParagraph()

# 2. System Overview
$selection.Style = $word.ActiveDocument.Styles("Heading 1")
$selection.TypeText("2. System Overview")
$selection.TypeParagraph()

## 2.1 Core Features
$selection.Style = $word.ActiveDocument.Styles("Heading 2")
$selection.TypeText("2.1 Core Features")
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Normal")
$features = @(
    "Asset lifecycle management with unique identifiers",
    "Role-based access control",
    "Maintenance scheduling and tracking",
    "Document management",
    "Real-time reporting and analytics",
    "Barcode/QR code support",
    "Automated notifications"
)
foreach ($feature in $features) {
    $selection.TypeText("• $feature")
    $selection.TypeParagraph()
}

## 2.2 Technical Stack
$selection.Style = $word.ActiveDocument.Styles("Heading 2")
$selection.TypeText("2.2 Technical Stack")
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Normal")
$selection.TypeText("• Backend: Laravel 10, PHP 8.1+")
$selection.TypeParagraph()
$selection.TypeText("• Frontend: HTML5, CSS3, JavaScript, Vue.js")
$selection.TypeParagraph()
$selection.TypeText("• Database: MySQL/PostgreSQL")
$selection.TypeParagraph()
$selection.TypeText("• Documentation: PlantUML, Markdown")
$selection.TypeParagraph()
$selection.TypeText("• Testing: PHPUnit, Pest")
$selection.TypeParagraph()

# 3. System Architecture
$selection.Style = $word.ActiveDocument.Styles("Heading 1")
$selection.TypeText("3. System Architecture")
$selection.TypeParagraph()

## 3.1 Database Schema
$selection.Style = $word.ActiveDocument.Styles("Heading 2")
$selection.TypeText("3.1 Database Schema")
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Normal")
$selection.TypeText("The system utilizes a relational database with the following key tables:")
$selection.TypeParagraph()

# Add database tables as a formatted table
$tables = @(
    @{Name="users"; Description="Manages system users and authentication"},
    @{Name="assets"; Description="Tracks all physical and digital assets"},
    @{Name="asset_models"; Description="Defines asset models and specifications"},
    @{Name="asset_statuses"; Description="Manages asset lifecycle states"},
    @{Name="maintenance_records"; Description="Tracks maintenance history"}
)

$table = $doc.Tables.Add($selection.Range, $tables.Count + 1, 2)
$table.Borders.Enable = $true
$table.Cell(1,1).Range.Text = "Table Name"
$table.Cell(1,2).Range.Text = "Description"

for ($i = 0; $i -lt $tables.Count; $i++) {
    $table.Cell($i+2,1).Range.Text = $tables[$i].Name
    $table.Cell($i+2,2).Range.Text = $tables[$i].Description
}
$selection.EndKey(6)  # Move to end of document

# Add diagrams section with placeholders
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Heading 2")
$selection.TypeText("3.2 System Diagrams")
$selection.TypeParagraph()
$selection.Style = $word.ActiveDocument.Styles("Normal")
$selection.TypeText("The following diagrams illustrate the system architecture and processes:")
$selection.TypeParagraph()

# Add diagram placeholders (actual diagrams would be inserted here)
$diagrams = @(
    "System Context Diagram",
    "Main Process Flow",
    "Asset Management Flow",
    "Asset Registration Process"
)

foreach ($diagram in $diagrams) {
    $selection.Style = "Heading 3"
    $selection.TypeText($diagram)
    $selection.TypeParagraph()
    $selection.Style = $word.ActiveDocument.Styles("Normal")
    $selection.InlineShapes.AddHorizontalLineStandard()
    $selection.TypeText("[Diagram: $diagram]")
    $selection.TypeParagraph()
}

# Update table of contents
$toc.Update()

# Save the document
$reportPath = "$PSScriptRoot\..\reports\Asset_Management_System_Report_$(Get-Date -Format 'yyyyMMdd').docx"
New-Item -ItemType Directory -Force -Path "$PSScriptRoot\..\reports" | Out-Null
$doc.SaveAs([ref]$reportPath, [ref]16)  # 16 = wdFormatDocumentDefault

# Close Word
$doc.Close()
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
[System.GC]::Collect()
[System.GC]::WaitForPendingFinalizers()

Write-Host "Report generated successfully at: $reportPath" -ForegroundColor Green
