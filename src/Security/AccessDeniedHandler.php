<?php
namespace App\Security;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;
use Twig\Environment;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private Environment $twig) {}

    public function handle(Request $request, AccessDeniedException $exception): ?Response
    {
        $html = $this->twig->render('error/access_denied.html.twig');
        return new Response($html, 403);
    }
}
