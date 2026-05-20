<?php

$files = trim(shell_exec('grep -Rl "<<<\'HTML\'\|<<<\'blade\'" app/Modules/'));
$files = explode("\n", $files);

foreach ($files as $file) {
    if (empty($file) || !file_exists($file)) continue;

    $content = file_get_contents($file);
    
    // Find the heredoc
    if (preg_match('/return\s+<<<\'?(HTML|blade)\'?\s*\n(.*?)\n\s*\1;/s', $content, $matches)) {
        $viewContent = $matches[2];
        
        // Determine module and component name
        // Example: app/Modules/Screening/Http/Livewire/FormScreeningPage.php
        if (preg_match('/app\/Modules\/([^\/]+)\/Http\/Livewire\/(.*?)\.php/', $file, $pathMatches)) {
            $module = strtolower($pathMatches[1]);
            $componentClass = $pathMatches[2];
            
            // Convert component class to kebab case (e.g. FormScreeningPage -> form-screening-page)
            $componentKebab = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $componentClass));
            
            // Create target directory
            $viewDir = "resources/views/livewire/modules/{$module}";
            if (!is_dir($viewDir)) {
                mkdir($viewDir, 0755, true);
            }
            
            $viewFile = "{$viewDir}/{$componentKebab}.blade.php";
            file_put_contents($viewFile, $viewContent);
            
            // Replace in original file
            $newReturn = "return view('livewire.modules.{$module}.{$componentKebab}');";
            $newContent = str_replace($matches[0], $newReturn, $content);
            file_put_contents($file, $newContent);
            
            echo "Refactored $file -> $viewFile\n";
        }
    }
}

