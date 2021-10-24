<?php

use Laminas\I18n\Translator\Translator;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ViewRenderer {

    private $basePath;
    private $csrfManager;
    private $translator;

    public function __construct(string $basePath, CsrfTokenManagerInterface $csrfManager, Translator $translator) {
        $this->basePath = $basePath;
        $this->csrfManager = $csrfManager;
        $this->translator = $translator;
    }

    public function render(ModelAndView $modelAndView, string $locale) {
        extract($modelAndView->getModel());
        ob_clean();
        $view = $modelAndView->getViewName();
        $_csrfToken = $this->csrfManager->getToken('_csrf')->getValue();
        $trans = function($key) use($locale) {
            return $this->translator->translate($key, "messages", $locale);
        };
        $_csrf = "<input type='hidden' name='_csrf' value='${_csrfToken}' />";
        require_once $this->basePath."/templates/layout.php";
        return ob_get_clean();
    }

}
