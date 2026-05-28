<?php

use eftec\bladeone\BladeOne;

class MessageController
{
    private MessageModel $messageModel;
    private BladeOne $blade;

    public function __construct(MessageModel $messageModel)
    {
        $this->messageModel = $messageModel;
        $this->blade = new BladeOne(__DIR__ . '/views', __DIR__ . '/../cache');
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
                $this->handleDelete();
            } else {
                $this->handlePost();
            }
        } else {
            $this->handleGet();
        }
    }

    private function handlePost(): void
    {
        $this->messageModel->create($_POST['name'], $_POST['title'], $_POST['content']);
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    private function handleDelete(): void
    {
        $this->messageModel->delete($_POST['id']);
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    private function handleGet(): void
    {
        $posts = $this->messageModel->getAll();
        echo $this->blade->run('index', ['posts' => $posts]);
        exit;
    }
}
