<?php

namespace App\Controller;


use App\Document\Station;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\UsersRepository;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class CommentController extends AbstractController
{
    #[Route('/comments', name: 'app_comments', methods: ['POST'])]
    public function getComment(Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['osmId'])) {
            return new JsonResponse(['message' => 'Station de ski non renseigné'], Response::HTTP_BAD_REQUEST);
        }

        $comments = $commentRepository->findBy(['osmId' => $data['osmId']]);

        return new JsonResponse($comments);
    }

    /**
     * @throws MappingException
     * @throws LockException
     */
    #[Route('/comment/add', name: 'app_add_comment', methods: ['POST'])]
    public function addComment(Request $request, CommentRepository $commentRepository, DocumentManager $documentManager,
                               UsersRepository $usersRepository, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['osmId'])) {
            return new JsonResponse(['errors' => 'Station de ski non renseigné'], Response::HTTP_BAD_REQUEST);
        }
        $stationRepository = $documentManager->getRepository(Station::class);
        $station = $stationRepository->find($data['osmId']);
        if (!isset($station)) {
            return new JsonResponse(['errors' => 'Station de ski non trouvé'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setOsmId($data['osmId']);
        $user = $this->getUser()->getUserIdentifier();
        $user = $usersRepository->findOneBy(['email' => $user]);
        $comment->setUser($user);
        $comment->setCreatedAt(new DateTimeImmutable('now'));

        if(isset($data['title'])) {
            $comment->setTitle($data['title']);
        }
        if(isset($data['description'])) {
            $comment->setDescription($data['description']);
        }
        if(isset($data['note'])) {
            $comment->setNote($data['note']);
        }

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }

        $commentRepository->save($comment);
        return new JsonResponse(['message' => 'Le commentaire a bien été ajouté.'], Response::HTTP_CREATED);
    }

    #[Route('/comment/delete', name: 'app_delete_comment', methods: ['POST'])]
    public function deleteComment(Request $request, CommentRepository $commentRepository, UsersRepository $usersRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['commentId'])) {
            return new JsonResponse(['errors' => 'Vous devez renseigner un commentaire.'], Response::HTTP_BAD_REQUEST);
        }

        $comment = $commentRepository->find($data['commentId']);
        if (!isset($comment)) {
            return new JsonResponse(['errors' => 'Commentaire non trouvé.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser()->getUserIdentifier();
        $user = $usersRepository->findOneBy(['email' => $user]);
        if(!in_array('ROLE_ADMIN',$user->getRoles(),true) || $user != $comment->getUser()) {
            return new JsonResponse(['errors' => 'Vous ne pouvez pas supprimer ce commentaire.'], Response::HTTP_BAD_REQUEST);
        }

        $commentRepository->remove($comment);
        return new JsonResponse(['message' => 'Le commentaire a bien été supprimé.'], Response::HTTP_OK);
    }

    #[Route('/admin/comments', name: 'app_admin_comments', methods: ['GET'])]
    public function getAllComments(CommentRepository $commentRepository): JsonResponse
    {
       $comments = $commentRepository->findAll();
       return new JsonResponse($comments);
    }

    #[Route('/admin/comment/disable', name: 'app_admin_comment', methods: ['POST'])]
    public function disableComment(Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['commentId'])) {
            return new JsonResponse(['errors' => 'Vous devez renseigner un commentaire.'], Response::HTTP_BAD_REQUEST);
        }
        $comment = $commentRepository->find($data['commentId']);
        if (!isset($comment)) {
            return new JsonResponse(['errors' => 'Commentaire non trouvé.'], Response::HTTP_BAD_REQUEST);
        }

        $comment->setIsValide(false);
        $commentRepository->save($comment);
        return new JsonResponse(['message' => 'Le commentaire a bien été désactivé.'], Response::HTTP_OK);
    }
}
