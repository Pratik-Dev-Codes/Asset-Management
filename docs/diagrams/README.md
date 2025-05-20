# System Architecture Diagrams

This directory contains PlantUML source files and pre-generated diagrams for the Asset Management System's architecture. These diagrams provide a visual representation of the system's structure, processes, and data flows at different levels of abstraction.

## Viewing Pre-generated Diagrams

You can view the pre-generated diagrams without any additional software:

1. **Windows**: Double-click `preview_diagrams.bat` to open all diagrams in your default image viewer
2. **Manual Viewing**: Navigate to the `png/` or `svg/` directory and open the files directly

## Generating Diagrams (Optional)

If you want to modify the diagrams or generate them yourself, follow these steps:

## Diagram Levels

1. **00-context.puml** - High-level context diagram showing system boundaries and external actors
2. **01-main-processes.puml** - Level 1 diagram showing main system processes
3. **02-asset-management.puml** - Level 2 diagram detailing the asset management process
4. **03-asset-registration.puml** - Level 3 diagram showing the detailed asset registration workflow

### Prerequisites

- Install [PlantUML](https://plantuml.com/download)
- (Optional) Install [Graphviz](https://graphviz.org/download/) for better layout

### Generation Options

#### Option 1: Using the provided script (Windows)

1. Navigate to the project root
2. Run the generation script:
   ```
   docs\generate_diagrams.bat
   ```

#### Option 2: Manual Generation

To generate all diagrams:

```bash
# Generate PNG files
plantuml -tpng -o ./png/ *.puml

# Generate SVG files
plantuml -tsvg -o ./svg/ *.puml
```

## Directory Structure

```
diagrams/
├── png/                  # Generated PNG images
├── svg/                  # Generated SVG images
├── 00-context.puml       # Context diagram
├── 01-main-processes.puml # Level 1 processes
├── 02-asset-management.puml # Level 2 asset management
├── 03-asset-registration.puml # Level 3 registration workflow
└── README.md             # This file
```

## Color Scheme

- **Primary**: Indigo (#4f46e5) - Admin functions
- **Secondary**: Emerald (#10b981) - Asset processes
- **Accent**: Amber (#f59e0b) - Warnings/validations
- **Danger**: Red (#ef4444) - Errors/deletions
- **Info**: Blue (#3b82f6) - Information
- **Success**: Green (#10b981) - Success states

## Best Practices

1. Keep diagrams focused on a single level of abstraction
2. Use consistent styling and colors
3. Include legends for complex diagrams
4. Update diagrams when related code changes
5. Generate both PNG (for quick viewing) and SVG (for scaling) versions

## Editing Diagrams

1. Install a PlantUML plugin for your IDE:
   - VS Code: [PlantUML extension](https://marketplace.visualstudio.com/items?itemName=jebbs.plantuml)
   - IntelliJ: [PlantUML Integration](https://plugins.jetbrains.com/plugin/7017-plantuml-integration)
2. Edit the .puml files
3. Regenerate the diagrams
4. Commit both .puml and generated image files

## Version Control

- Always commit both the .puml source files and generated images
- Use descriptive commit messages when updating diagrams
- Reference related code changes in commit messages
