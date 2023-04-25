<?php

namespace Impack\WP\Components;

class Setting
{
    protected $setting;

    protected $group;

    protected $page;

    public function __construct($setting, $group, $page = '')
    {
        $this->setting = $setting;
        $this->group   = $group;
        $this->page    = $page ?: $this->group;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function getSetting()
    {
        return $this->setting;
    }

    public function addUpdatedNoticeIf()
    {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] && count($this->getErrors()) < 1) {
            $this->addUpdatedNotice();
        }
    }

    public function addUpdatedNotice()
    {
        $this->addError('settings_updated', __('Settings saved.'), 'success');
    }

    public function addError($code, $message, $type = 'error')
    {
        \add_settings_error($this->setting, $code, $message, $type);
    }

    public function getErrors($sanitize = false)
    {
        return \get_settings_errors($this->setting, $sanitize);
    }

    public function renderErrors($sanitize = false, $hide_on_update = false)
    {
        \settings_errors($this->setting, $sanitize, $hide_on_update);
    }

    public function renderPage($title = null, $capability = 'manage_options', ...$renderFormArgs)
    {
        if ($capability != null && !\current_user_can($capability)) {
            return;
        }

        $this->addUpdatedNoticeIf();

        echo '<div class="wrap">';
        echo '<h1>' . \esc_html($title ?? \get_admin_page_title()) . '</h1>';
        $this->renderErrors();
        $this->renderForm(...$renderFormArgs);
        echo '</div>';
    }

    public function renderForm($action = 'options.php', $method = 'post', ...$submitArgs)
    {
        echo '<form method="' . $method . '" action="' . $action . '">';
        $this->render();
        \submit_button(...$submitArgs);
        echo '</form>';
    }

    public function render()
    {
        \settings_fields($this->group);
        \do_settings_sections($this->page);
    }
}