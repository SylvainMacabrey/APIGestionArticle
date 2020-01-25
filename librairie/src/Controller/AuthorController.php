<?php

namespace App\Controller;

use App\Entity\Author;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use App\Representation\Authors;

class AuthorController extends FOSRestController
{

    /**
     * @Get(
     *     path = "/authors/{id}",
     *     name = "author.show",
     *     requirements = {"id"="\d+"}
     * )
     * @View
     */
    public function showAction(Author $author)
    {
        return $author;
    }

    /**
     * @Post("/authors", name="author.create")
     * @View(StatusCode = 201)
     * @ParamConverter("author", converter="fos_rest.request_body")
     */
    public function createAction(Author $author, ConstraintViolationList $violations)
    {
        if (count($violations)) {
            $message = 'The JSON sent contains invalid data. Here are the errors you need to correct: ';
            foreach ($violations as $violation) {
                $message .= sprintf("Field %s: %s ", $violation->getPropertyPath(), $violation->getMessage());
            }
            throw new ResourceValidationException($message);
            //return $this->view($violations, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($author);
        $em->flush();

        return $this->view(
            $author, 
            Response::HTTP_CREATED, 
            [
                'Location' => $this->generateUrl('Author.show', ['id' => $author->getId(), UrlGeneratorInterface::ABSOLUTE_URL])
            ]
        );
    }

    /**
     * @View(StatusCode = 200)
     * @Put(
     *     path = "/authors/{id}",
     *     name = "author.update",
     *     requirements = {"id"="\d+"}
     * )
     * @ParamConverter("newAuthor", converter="fos_rest.request_body")
     */
    public function updateAction(Author $author, Author $newAuthor, ConstraintViolationList $violations)
    {
        if (count($violations)) {
            $message = 'The JSON sent contains invalid data. Here are the errors you need to correct: ';
            foreach ($violations as $violation) {
                $message .= sprintf("Field %s: %s ", $violation->getPropertyPath(), $violation->getMessage());
            }

            throw new ResourceValidationException($message);
        }

        $author->setFullname($newAuthor->getFullname());
        $author->setBiography($newAuthor->getBiography());

        $this->getDoctrine()->getManager()->flush();

        return $author;
    }

    /**
     * View(StatusCode = 204)
     * Delete(
     *     path = "/authors/{id}",
     *     name = "author.delete",
     *     requirements = {"id"="\d+"}
     * )
     */
    public function deleteAction(Author $Author)
    {
        $this->getDoctrine()->getManager()->remove($author);

        return;
    }

     /**
     * @Get("/authors", name="author.list")
     * @QueryParam(
     *     name="keyword",
     *     requirements="[a-zA-Z0-9]",
     *     nullable=true,
     *     description="The keyword to search for."
     * )
     * @QueryParam(
     *     name="order",
     *     requirements="asc|desc",
     *     default="asc",
     *     description="Sort order (asc or desc)"
     * )
     * @QueryParam(
     *     name="limit",
     *     requirements="\d+",
     *     default="3",
     *     description="Max number of authors per page."
     * )
     * @QueryParam(
     *     name="offset",
     *     requirements="\d+",
     *     default="1",
     *     description="The pagination offset"
     * )
     * @View()
     */
    public function listAction(ParamFetcherInterface $paramFetcher)
    {
        $pager = $this->getDoctrine()->getRepository('App:Author')->search(
            $paramFetcher->get('keyword'),
            $paramFetcher->get('order'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset')
        );

        return new Authors($pager);
    }

}