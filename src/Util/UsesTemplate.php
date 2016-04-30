<?php


namespace OFFLINE\Bootstrapper\October\Util;


trait UsesTemplate
{
    public function getTemplate($file)
    {
        $dist      = __DIR__ . DS . implode(DS, ['..', '..', 'templates', $file]);
        $overwrite = __DIR__ . DS . implode(DS, ['..', '..', '..', '..', 'october', $file]);

        return file_exists($overwrite) ? realpath($overwrite) : realpath($dist);
    }
}