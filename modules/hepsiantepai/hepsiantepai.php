<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/src/autoload.php';

use Hepsiantep\Ai\Install\DatabaseInstaller;

final class HepsiantepAi extends Module
{
    public function __construct()
    {
        $this->name = 'hepsiantepai';
        $this->tab = 'others';
        $this->version = '0.1.0';
        $this->author = 'Hepsiantep';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '1.7.8.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans('Hepsiantep AI Commerce', [], 'Modules.Hepsiantepai.Admin');
        $this->description = $this->trans(
            'Canonical product data, evidence and AI commerce exporters for Hepsiantep.',
            [],
            'Modules.Hepsiantepai.Admin'
        );
    }

    public function install(): bool
    {
        return parent::install()
            && (new DatabaseInstaller())->install();
    }

    public function uninstall(): bool
    {
        return (new DatabaseInstaller())->uninstall()
            && parent::uninstall();
    }
}
