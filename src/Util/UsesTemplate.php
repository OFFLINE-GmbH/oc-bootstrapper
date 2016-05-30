<?php
namespace OFFLINE\Bootstrapper\October\Util;

trait UsesTemplate
{
    /**
     * Look in ~/.composer/october for overwritten template files.
     * Return the default template if there are none.
     *
     * @param $file
     *
     * @return string
     */
    public function getTemplate($file)
    {
        $dist      = __DIR__ . DS . implode(DS, ['..', '..', 'templates', $file]);
        $overwrite = __DIR__ . DS . implode(DS, ['..', '..', '..', '..', '..', 'october', $file]);

        return file_exists($overwrite) ? realpath($overwrite) : realpath($dist);
    }
}