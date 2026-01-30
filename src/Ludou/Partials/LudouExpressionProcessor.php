<?php

declare(strict_types=1);

namespace Ludelix\Ludou\Partials;

class LudouExpressionProcessor
{
    /**
     * Process a Ludou expression.
     *
     * @param string $expression
     * @return string
     */
    public function process(string $expression): string
    {
        // Basic implementation to return the expression as PHP echo
        // This will need to be expanded based on the framework's needs
        return "<?php echo {$expression}; ?>";
    }

    /**
     * Compile a function call expression.
     *
     * @param string $function
     * @param string $arguments
     * @return string
     */
    public function compileFunction(string $function, string $arguments): string
    {
        return "<?php echo {$function}({$arguments}); ?>";
    }
}
