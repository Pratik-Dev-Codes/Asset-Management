<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class GenerateDocumentationCommand extends Command
{
    protected $signature = 'docs:generate';
    protected $description = 'Generate system documentation and diagrams';

    public function handle()
    {
        $this->info('Generating system documentation...');
        
        // Ensure diagrams directory exists
        $diagramPath = base_path('docs/diagrams');
        if (!File::exists($diagramPath)) {
            File::makeDirectory($diagramPath, 0755, true);
        }

        // Generate architecture diagrams
        $this->generateArchitectureDiagrams($diagramPath);
        
        // Generate API documentation (can be extended)
        $this->generateApiDocumentation($diagramPath);
        
        $this->info('Documentation generated successfully!');
        $this->info('Diagrams saved to: ' . $diagramPath);
    }

    protected function generateArchitectureDiagrams($outputPath)
    {
        $this->info('Generating architecture diagrams...');
        
        // Generate context diagram
        $this->generateContextDiagram($outputPath);
        
        // Generate class diagrams (example for models)
        $this->generateClassDiagrams($outputPath);
        
        // Generate sequence diagrams for key workflows
        $this->generateSequenceDiagrams($outputPath);
    }

    protected function generateContextDiagram($outputPath)
    {
        $puml = "@startuml Context Diagram\n";
        $puml .= "skinparam backgroundColor #f8fafc\n";
        $puml .= "skinparam defaultFontName 'Segoe UI', 'Arial', sans-serif\n";
        
        // Add actors
        $puml .= "actor \":Admin\" as admin #4f46e5\n";
        $puml .= "actor \":User\" as user #10b981\n";
        $puml .= "actor \":Manager\" as manager #8b5cf6\n";
        $puml .= "actor \":Technician\" as tech #f59e0b\n";
        $puml .= "actor \":Auditor\" as auditor #06b6d4\n\n";
        
        // Add system boundary
        $puml .= "rectangle \"Asset Management System\" #ffffff {\n";
        $puml .= "  (System Core) as system #f1f5f9\n";
        $puml .= "  database \"Database\" as db #4f46e5\n";
        $puml .= "  db --> system : Stores\n";
        $puml .= "}\n\n";
        
        // Add interactions
        $puml .= "admin --> system : Manages Users/Roles\n";
        $puml .= "user --> system : Uses System\n";
        $puml .= "manager --> system : Approves/Views\n";
        $puml .= "tech --> system : Updates Status\n";
        $puml .= "auditor --> system : Reviews Logs\n\n";
        
        // Add legend
        $puml .= "legend bottom center\n";
        $puml .= "  <b>SYSTEM ROLES</b>\n";
        $puml .= "  <color:#4f46e5>Admin</color> | ";
        $puml .= "<color:#10b981>User</color> | ";
        $puml .= "<color:#8b5cf6>Manager</color> | ";
        $puml .= "<color:#f59e0b>Technician</color> | ";
        $puml .= "<color:#06b6d4>Auditor</color>\n";
        $puml .= "end legend\n";
        $puml .= "@enduml";
        
        File::put("$outputPath/00-context-auto.puml", $puml);
    }

    protected function generateClassDiagrams($outputPath)
    {
        $modelsPath = app_path('Models');
        $models = File::files($modelsPath);
        
        $puml = "@startuml Class Diagram\n";
        $puml .= "skinparam backgroundColor #f8fafc\n";
        $puml .= "skinparam classFontStyle bold\n";
        $puml .= "skinparam classAttributeFontSize 12\n";
        $puml .= "skinparam classFontName 'Segoe UI', 'Arial', sans-serif\n\n";
        
        $puml .= "title <b>Class Diagram</b>\n";
        $puml .= "Generated on " . now() . "\n\n";
        
        // Add classes
        foreach ($models as $modelFile) {
            $className = 'App\\Models\\' . pathinfo($modelFile->getFilename(), PATHINFO_FILENAME);
            
            try {
                $reflection = new ReflectionClass($className);
                $puml .= "class " . $reflection->getShortName() . " {\n";
                
                // Add properties
                $properties = $reflection->getDefaultProperties();
                foreach ($properties as $name => $value) {
                    $puml .= "  {$name}\n";
                }
                
                // Add methods
                $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
                $methodNames = array_map(fn($m) => $m->getName(), $methods);
                $methodNames = array_filter($methodNames, fn($m) => !in_array($m, ['__construct', '__destruct']));
                
                if (!empty($methodNames)) {
                    $puml .= "  --\n";
                    foreach ($methodNames as $method) {
                        $puml .= "  {$method}()\n";
                    }
                }
                
                $puml .= "}\n\n";
                
                // Add relationships (simplified)
                if (method_exists($className, 'users')) {
                    $puml .= $reflection->getShortName() . " -- " . "User\n";
                }
                
            } catch (\Exception $e) {
                $this->warn("Could not process class {$className}: " . $e->getMessage());
            }
        }
        
        File::put("$outputPath/04-class-diagram-auto.puml", $puml);
    }

    protected function generateSequenceDiagrams($outputPath)
    {
        // Example sequence diagram for asset registration
        $puml = "@startuml Asset Registration Sequence\n";
        $puml .= "skinparam backgroundColor #f8fafc\n";
        $puml .= "skinparam defaultFontName 'Segoe UI', 'Arial', sans-serif\n\n";
        
        $puml .= "actor User as user #10b981\n";
        $puml .= "participant UI as ui #3b82f6\n";
        $puml .= "participant Controller as ctrl #8b5cf6\n";
        $puml .= "participant Service as svc #10b981\n";
        $puml .= "database Database as db #4f46e5\n\n";
        
        $puml .= "== Asset Registration ==\n";
        $puml .= "user -> ui: Fill and submit form\n";
        $puml .= "activate ui\n";
        $puml .= "ui -> ctrl: POST /assets\n";
        $puml .= "activate ctrl\n";
        $puml .= "ctrl -> svc: validateAndCreateAsset()\n";
        $puml .= "activate svc\n";
        $puml .= "svc -> db: Begin Transaction\n";
        $puml .= "svc -> db: Save Asset\n";
        $puml .= "db --> svc: Asset ID\n";
        $puml .= "svc -> db: Save Audit Log\n";
        $puml .= "svc -> db: Commit\n";
        $puml .= "svc --> ctrl: Asset Created\n";
        $puml .= "deactivate svc\n";
        $puml .= "ctrl --> ui: 201 Created\n";
        $puml .= "deactivate ctrl\n";
        $puml .= "ui --> user: Show Success\n";
        $puml .= "deactivate ui\n";
        $puml .= "@enduml";
        
        File::put("$outputPath/05-sequence-asset-registration-auto.puml", $puml);
    }

    protected function generateApiDocumentation($outputPath)
    {
        // This can be extended to generate OpenAPI/Swagger docs
        $this->info('API documentation generation would go here...');
    }
}
