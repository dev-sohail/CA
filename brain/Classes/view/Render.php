<?php

class Render
{
    protected Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Render a view file located at: /Modules/{role}/{module}/view/{file}.ct.php
     *
     * @param string $role   Role name (e.g., admin, user)
     * @param string $module Module name (e.g., dashboard)
     * @param string $file   View file (without .ct.php)
     * @param array  $data   Variables passed to view
     * @param bool   $return Return instead of echo
     * @return string|void
     */
    public function render(string $role, string $module, string $file, array $data = [], bool $return = false)
    {
        $viewPath = rtrim(DIR_MODULES, '/') . "/$role/$module/Views/" . ltrim($file, '/') . '.ct.php';

        if (!is_file($viewPath)) {
            trigger_error("Render error: View [$file] not found at [$viewPath]", E_USER_WARNING);
            return '';
        }

        extract($data, EXTR_SKIP);

        // Inject services
        $language = $this->registry->get('language') ?? null;
        $config   = $this->registry->get('config') ?? null;
        $url      = $this->registry->get('url') ?? null;

        ob_start();
        include $viewPath;
        $output = ob_get_clean();

        return $return ? $output : print $output;
    }

    /**
     * Render a view wrapped inside a layout
     *
     * @param string $layoutPath Layout file path (relative to DIR_VIEW)
     * @param string $role       Role name
     * @param string $module     Module name
     * @param string $view       View file
     * @param array  $data       Data for both layout and view
     */
    public function renderWithLayout(string $layoutPath, string $role, string $module, string $view, array $data = []): void
    {
        $data['content'] = $this->render($role, $module, $view, $data, true);
        $layoutFullPath = rtrim(DIR_VIEW, '/') . '/' . ltrim($layoutPath, '/') . '.ct.php';

        if (!is_file($layoutFullPath)) {
            trigger_error("Layout [$layoutPath] not found at [$layoutFullPath]", E_USER_WARNING);
            return;
        }

        extract($data, EXTR_SKIP);

        $language = $this->registry->get('language') ?? null;
        $config   = $this->registry->get('config') ?? null;
        $url      = $this->registry->get('url') ?? null;

        include $layoutFullPath;
    }

    /**
     * Render a partial from module view
     *
     * @param string $role
     * @param string $module
     * @param string $file
     * @param array  $data
     */
    public function partial(string $role, string $module, string $file, array $data = []): void
    {
        $this->render($role, $module, $file, $data);
    }

    /**
     * Escape HTML output safely
     *
     * @param string|null $text
     * @return string
     */
    public function e(?string $text): string
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}
