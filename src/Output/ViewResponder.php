<?php
// @codingStandardsIgnoreFile

namespace Jnjxp\WebUi\Output;

use Aura\View\View;
use Psr\Http\Message\ResponseInterface as Response;


class ViewResponder
{
    /**
     * view
     *
     * @var mixed
     *
     * @access protected
     */
    protected $view;

    /**
     * __construct
     *
     * @param View $view DESCRIPTION
     *
     * @return mixed
     *
     * @access public
     */
    public function __construct(View $view)
    {
        $this->view = $view;
    }

    /**
     * Render
     *
     * @param string $script DESCRIPTION
     * @param array  $data   DESCRIPTION
     *
     * @return string
     *
     * @access protected
     */
    protected function render(string $script, array $data = []) : string
    {
        $view = $this->view;
        $view->setView($script);

        if ($data) {
            $view->addData($data);
        }

        return $view();
    }

    /**
     * responseTemplate
     *
     * @param Response $response DESCRIPTION
     * @param string   $script   DESCRIPTION
     * @param array    $data     DESCRIPTION
     *
     * @return void
     *
     * @access protected
     */
    protected function responseTemplate(
        Response $response,
        string $script,
        array $data = []
    ) : void {

        $template = $this->render($script, $data);
        $body     = $response->getBody();
        $body->write($template);
    }

}
