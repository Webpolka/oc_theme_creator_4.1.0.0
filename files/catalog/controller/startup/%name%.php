<?php
namespace Opencart\Catalog\Controller\Extension\%ThemeName%\Startup;

class %Name% extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        if ($this->config->get('config_theme') == '%name%' && $this->config->get('theme_%name%_status')) {
            $this->event->register('view/*/before', new \Opencart\System\Engine\Action('extension/%theme_name%/startup/%name%.event'));
        }
    }

    public function event(string &$route, array &$args, mixed &$output): void
    {
        $override = [
            'common/header',
        ];

        if (in_array($route, $override)) {
            $route = 'extension/%theme_name%/' . $route;
        }
    }


}
