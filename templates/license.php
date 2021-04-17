<?php
// This file will be executed in the context of your newly created
// OctoberCMS installation. It sets up your license key.
//
// We try to re-use as much of the original October code as possible.

use October\Rain\Process\Composer as ComposerProcess;
use System\Classes\UpdateManager;
use System\Traits\SetupHelper;

// Check if a key was provided.
if (count($argv) !== 2) {
    fwrite(STDERR, "Provide your license key as an argument to this script.\n");
    exit(2);
}

// Boot an application kernel.
require 'bootstrap/autoload.php';

/** @var \October\Rain\Foundation\Application $app */
$app = require_once 'bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $kernel */
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Register the application.
(new class {
    use SetupHelper;

    public function run($key)
    {
        try {
            $result = UpdateManager::instance()->requestProjectDetails($key);

            // Check status.
            $isActive = $result['is_active'] ?? false;
            if (!$isActive) {
                throw new Exception('License is unpaid or has expired. Please visit octobercms.com to obtain a license.');
            }

            $projectId = $result['project_id'] ?? null;
            $projectEmail = $result['email'] ?? null;
            $this->setComposerAuth($projectEmail, $projectId);

            $composer = new ComposerProcess;
            $composer->addRepository('octobercms', 'composer', $this->getComposerUrl());

            $requireStr = $this->composerRequireString();

            $composer = new ComposerProcess;
            $composer->setCallback(function($message) { echo $message; });
            $composer->require($requireStr);

            if ($composer->lastExitCode() !== 0) {
                throw new Exception('Failed to install october/* composer dependencies');
            }
        } catch (Throwable $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            exit(1);
        }
    }
})->run($argv[1]);
