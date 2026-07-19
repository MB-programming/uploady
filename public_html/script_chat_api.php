<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

header('Content-Type: application/json; charset=utf-8');

function jsonOut(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'POST only'], 405);
}
Csrf::verify();

$action = $_POST['action'] ?? '';
$userId = Auth::id();

if ($action === 'send') {
    if (!AiChatService::isConfigured()) {
        jsonOut(['error' => t('chat.err_not_configured')], 400);
    }

    $message = trim((string) ($_POST['message'] ?? ''));
    if ($message === '' || mb_strlen($message) > 12000) {
        jsonOut(['error' => t('chat.err_message_required')], 400);
    }

    $conversationId = (int) ($_POST['conversation_id'] ?? 0);
    $isNew = false;
    if ($conversationId > 0) {
        if (!ChatConversation::findForUser($conversationId, $userId)) {
            jsonOut(['error' => 'Not found'], 404);
        }
    } else {
        $conversationId = ChatConversation::create($userId, mb_substr($message, 0, 60));
        $isNew = true;
    }

    ChatMessage::add($conversationId, 'user', $message);

    try {
        $history = ChatMessage::recentForConversation($conversationId);
        $reply = AiChatService::reply($history, ChatMemory::forUser($userId));
    } catch (Throwable $e) {
        error_log('[script_chat] ' . $e->getMessage());
        // The user's message stays saved; surface the failure so they can retry.
        jsonOut(['error' => $e->getMessage(), 'conversation_id' => $conversationId, 'is_new' => $isNew], 502);
    }

    ChatMessage::add($conversationId, 'assistant', $reply);
    ChatConversation::touch($conversationId);

    jsonOut([
        'reply' => $reply,
        'conversation_id' => $conversationId,
        'is_new' => $isNew,
        'title' => mb_substr($message, 0, 60),
    ]);
}

if ($action === 'delete_conversation') {
    ChatConversation::delete((int) ($_POST['id'] ?? 0), $userId);
    jsonOut(['ok' => true]);
}

if ($action === 'add_memory') {
    $content = trim((string) ($_POST['content'] ?? ''));
    if ($content === '') {
        jsonOut(['error' => t('chat.err_memory_required')], 400);
    }
    $id = ChatMemory::add($userId, $content);
    jsonOut(['ok' => true, 'id' => $id, 'content' => mb_substr($content, 0, 500)]);
}

if ($action === 'delete_memory') {
    ChatMemory::delete((int) ($_POST['id'] ?? 0), $userId);
    jsonOut(['ok' => true]);
}

jsonOut(['error' => 'Unknown action'], 400);
