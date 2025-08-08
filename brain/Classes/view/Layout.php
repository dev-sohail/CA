<?php

class Layout
{
    public static function render(string $layoutFile, array $data = []): void
    {
        $layoutFullPath = rtrim(DIR_VIEW, '/') . '/' . ltrim($layoutFile, '/') . '.ct.php';
        if (!is_file($layoutFullPath)) {
            trigger_error("Layout [$layoutFile] not found at [$layoutFullPath]", E_USER_WARNING);
            return;
        }
        extract($data, EXTR_SKIP);
        include $layoutFullPath;
    }
}