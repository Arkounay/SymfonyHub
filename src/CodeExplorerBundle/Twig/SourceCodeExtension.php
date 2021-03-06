<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CodeExplorerBundle\Twig;

/**
 * CAUTION: this is an extremely advanced Twig extension. It's used to get the
 * source code of the controller and the template used to render the current
 * page. If you are starting with Symfony, don't look at this code and consider
 * studying instead the code of the src/AppBundle/Twig/AppExtension.php extension.
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class SourceCodeExtension extends \Twig_Extension
{
    private $controller;
    private $kernelRootDir;

    public function __construct($kernelRootDir)
    {
        $this->kernelRootDir = $kernelRootDir;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('show_source_code', [$this, 'showSourceCode'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    public function showSourceCode(\Twig_Environment $twig, $template)
    {
        return $twig->render('@CodeExplorer/source_code.html.twig', [
            'controller' => $this->getController(),
            'template' => $this->getTemplateSource($twig->resolveTemplate($template)),
        ]);
    }

    private function getController()
    {
        // this happens for example for exceptions (404 errors, etc.)
        if (null === $this->controller) {
            return;
        }

        $method = $this->getCallableReflector($this->controller);

        $classCode = file($method->getFileName());
        $methodCode = array_slice($classCode, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1);
        $controllerCode = '    ' . $method->getDocComment() . "\n" . implode('', $methodCode);

        return [
            'file_path' => $method->getFileName(),
            'starting_line' => $method->getStartLine(),
            'source_code' => $this->unindentCode($controllerCode),
        ];
    }

    /**
     * Gets a reflector for a callable.
     *
     * This logic is copied from Symfony\Component\HttpKernel\Controller\ControllerResolver::getArguments
     *
     * @param callable $callable
     *
     * @return \ReflectionFunctionAbstract
     */
    private function getCallableReflector($callable)
    {
        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_object($callable) && !$callable instanceof \Closure) {
            $r = new \ReflectionObject($callable);

            return $r->getMethod('__invoke');
        }

        return new \ReflectionFunction($callable);
    }

    private function getTemplateSource(\Twig_Template $template)
    {

        $filePath = $this->kernelRootDir . '/Resources/views/' . str_replace(':', '/', $template->getTemplateName());

        $sourceCode = $template->getSource();
        if (empty($sourceCode)) {
            $sourceCode = @file_get_contents($filePath);
        }

        return [
            // Twig templates are not always stored in files (they can be stored
            // in a database for example). However, for the needs of the Symfony
            // Demo app, we consider that all templates are stored in files and
            // that their file paths can be obtained through the source context.
            'file_path' => $filePath,
            'starting_line' => 1,
            'source_code' => $sourceCode
        ];
    }

    /**
     * Utility method that "unindents" the given $code when all its lines start
     * with a tabulation of four white spaces.
     *
     * @param string $code
     *
     * @return string
     */
    private function unindentCode($code)
    {
        $formattedCode = $code;
        $codeLines = explode("\n", $code);

        $indentedLines = array_filter($codeLines, function ($lineOfCode) {
            return '' === $lineOfCode || '    ' === substr($lineOfCode, 0, 4);
        });

        if (count($indentedLines) === count($codeLines)) {
            $formattedCode = array_map(function ($lineOfCode) {
                return substr($lineOfCode, 4);
            }, $codeLines);
            $formattedCode = implode("\n", $formattedCode);
        }

        return $formattedCode;
    }

    // the name of the Twig extension must be unique in the application
    public function getName()
    {
        return 'code_explorer_source_code';
    }
}
