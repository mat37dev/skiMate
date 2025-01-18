<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Core\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CommentController extends AbstractController
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }
    #[Route('/comments', name: 'app_comment', methods: ["GET"])]
    public function listComments(): JsonResponse
    {
        $comments = $this->commentRepository->findAll();

        $commentsData = [];
        foreach ($comments as $comment) {
            $commentData[] = [
                'id' => $comment->getId(),
                'title' => $comment->getTitle(),
                'description' => $comment->getDescription(),
                'user'=>$comment->getUsers(),
            ];
        }
        return $this->json($commentsData, 200);
    }

    #[Route('/comments/add', name: 'app_add_comment', methods: ["POST"])]
    public function AddComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $comment = new Comment();
        $comment->setTitle($data['title']);
        $comment->setDescription($data['description']);
        $comment->setNote($data['note']);

        return $this->json([
            'id' => $comment->getId(),
            'title' => $comment->getTitle(),
            'description' => $comment->getDescription(),
            'user' => $comment->getUsers(),
            'note' => $comment->getNote(),
        ], Response::HTTP_CREATED);
    }
    #[Route('/comment/delete/{id}', name: 'app_delete_comment', requirements: ['id'=>'\d+'], methods: ['DELETE'])]
    public function deleteComment(Uuid $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if(!$comment){
            throw $this->createNotFoundException('commentaire non trouvé');
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        return $this->json([
            'commentaire supprimé'
        ],200);
    }

    #[Route('/comment/edit/{id}', name: 'app_edit_comment', methods: ['PUT'])]
    public function editComment(Request $request,ValidatorInterface $validator, Uuid $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['title'])) {
            $comment->setTitle($data['title']);
        }
        if(isset($data['description'])){
            $comment->setDescription($data['description']);
        }
        if (isset($data['note'])) {
            $comment->setNote($data['note']);
        }

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }
        $this->commentRepository->save($comment);

        return new JsonResponse(['message' => 'commentaire mis à jour avec succès'], Response::HTTP_OK);
    }
}
