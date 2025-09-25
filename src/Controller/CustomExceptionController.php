<?php

namespace App\Controller;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Twig\Error\Error as TwigError;


class CustomExceptionController extends AbstractController
{
    public function showAction(Throwable $exception): Response
    {
        // Default 404 values
        $statusCode = Response::HTTP_NOT_FOUND;
        $message = 'Page not found.';

        // Check if the exception is an instance of HttpExceptionInterface and has a 404 status code
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 404) {
            // Render the 404 error page
            return new Response(
                $this->renderView('security/404_page.html.twig'),
                $statusCode
            );
        }

        // If the exception is not a 404, return a default response or do nothing
        // Depending on your application, you could choose to throw a different exception or return a blank page
        throw $exception; // Re-throw the exception if it's not a 404 error
    }
}