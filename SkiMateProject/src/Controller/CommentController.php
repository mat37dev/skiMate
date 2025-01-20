<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comment')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentRepository $commentRepository,
        private EntityManagerInterface $entityManager,
    )
    {
    }
    #[Route('/', name: 'app_comment', methods: ["GET"])]
    public function listComments(): JsonResponse
    {
        $comments = $this->commentRepository->findAll();

        $commentsData = [];
        foreach ($comments as $comment) {
            $commentsData[] = [
                'id' => $comment->getId(),
                'title' => $comment->getTitle(),
                'description' => $comment->getDescription(),
                'user'=>$comment->getUsers(),
                'note'=>$comment->getNote(),
            ];
        }

        return $this->json($commentsData, 200);
    }

    #[Route('/add', name: 'app_add_comment', methods: ["POST"])]
    public function AddComment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $comment = new Comment();
        $comment->setTitle($data['title']);
        $comment->setDescription($data['comment']);
        $comment->setNote($data['note']);

        $resort = random_int(1,10);
        $resortId = random_int(1,10);
        $comment->setEntityType($resort);
        $comment->setEntityId($resortId);

        $this->commentRepository->save($comment);

        return $this->json([
            'id' => $comment->getId(),
            'title' => $comment->getTitle(),
            'comment' => $comment->getDescription(),
            'user' => $comment->getUsers(),
            'note' => $comment->getNote(),
            'resort'=>$comment->getEntityType(),
            'resortId'=>$comment->getEntityId(),

        ], Response::HTTP_CREATED);
    }
    #[Route('/delete/{id}', name: 'app_delete_comment', requirements: ['id'=>'^[0-9a-fA-F\-]{36}$'], methods: ['DELETE'])]
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

    #[Route('/edit/{id}', name: 'app_edit_comment', requirements: ['id'=>'^[0-9a-fA-F\-]{36}$'], methods: ['PUT'])]
    public function editComment(Request $request,ValidatorInterface $validator, Uuid $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);
        if (!$comment) {
            return new JsonResponse(['message' => 'commentaire non trouvé'], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $comment->setTitle($data['title']);
        }
        if(isset($data['comment'])){
            $comment->setDescription($data['comment']);
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
