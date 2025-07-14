<?php

declare(strict_types=1);

namespace App\Services;

class TemplateRenderer
{
    private string $templatesPath;
    
    public function __construct(string $templatesPath)
    {
        $this->templatesPath = $templatesPath;
    }
    
    /**
     * Render a template with data
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $template, array $data = []): string
    {
        // Extract variables to be used in the template
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the template file
        include $this->templatesPath . '/' . $template . '.php';
        
        // Get the contents of the buffer and clean it
        return ob_get_clean();
    }
}