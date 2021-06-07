<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Twig {
    const TWIG_CONFIG_FILE = 'twig';

    protected $template_dir;
    protected $cache_dir;
    private $_ci;
    private $_twig_env;

    public function __construct() {
        $this->_ci =& get_instance();
        $this->_ci->config->load(self::TWIG_CONFIG_FILE);

        $this->template_dir = $this->_ci->config->item('template_dir');
        $this->cache_dir = $this->_ci->config->item('cache_dir');

	    $loader = new Twig\Loader\FilesystemLoader($this->template_dir, $this->cache_dir);
	    $this->_twig_env = new Twig\Environment($loader, array(
		    //'cache' => $this->cache_dir,
		    'cache' => false,
		    'auto_reload' => TRUE
	    ));

        $this->_twig_env->addFilter(new \Twig\TwigFilter('url2link', [$this, 'url2link']));
	    $this->_twig_env->addFilter(new \Twig\TwigFilter('sanitize', [$this, 'sanitize']));
	    $this->_twig_env->addFilter(new \Twig\TwigFilter('is_numeric', 'is_numeric'));
	    $this->_twig_env->addFilter(new \Twig\TwigFilter('datetime2iso', [$this, 'datetime2iso']));
    }

    public function render($template, $data = array(), $render = TRUE)
    {
        $template = $this->_twig_env->loadTemplate($template);
        return ($render) ? $template->render($data) : $template;
    }

    public function display($template, $data = array())
    {
        $template = $this->_twig_env->loadTemplate($template);
        $this->_ci->output->set_output($template->render($data));
    }

    public function datetime2iso ($datetime) {
//    	print $datetime;
//    	exit;
    	return substr($datetime, 0, 10) . 'T' . substr($datetime, 11) . "+09:00";
    }

    public function url2link ($text, $link_title = '') {
        $pattern = '/((href|src)=")?(https?:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/i';
        $text = preg_replace_callback($pattern, function($m) use ($link_title) {
            // 既にリンクの場合や Markdown style link の場合はそのまま
            if (!empty($m[1])) return $m[0];
            $target = '';
            if ($m[3]) {
                $pattern = '/(https?:\/\/' . Meow::FQDN . ')/';
                if (!preg_match($pattern, $m[3])) {
                    $target = 'target="_blank"';
                }
            }
            return "<a href=\"{$m[3]}\" {$target}>" . ($link_title ?: $m[3]) . "</a>";
        }, $text);
        return $text;
    }

    public function sanitize ($text) {
        $pattern = '/<script\s*.*?>.+?<\/script\s?>/';
        $text = preg_replace($pattern, '', $text);
        return $text;
    }
}