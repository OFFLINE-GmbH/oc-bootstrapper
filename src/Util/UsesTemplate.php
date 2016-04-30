<?php


namespace OFFLINE\Bootstrapper\October\Util;


trait UsesTemplate
{
    public function getTemplate($file)
    {
        $path = __DIR__ . DS . implode(DS, ['..', '..', 'templates', $file]);

        if (file_exists($path)) {
            return realpath($path);
        }

        return realpath($path . '.dist');
    }
}