<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$conversations = ChatConversation::forUser(Auth::id());
$memories = ChatMemory::forUser(Auth::id());

$activeId = (int) ($_GET['c'] ?? 0);
$activeConversation = $activeId > 0 ? ChatConversation::findForUser($activeId, Auth::id()) : null;
$messages = $activeConversation ? ChatMessage::forConversation((int) $activeConversation['id']) : [];

$configured = AiChatService::isConfigured();

$pageTitle = t('nav.script_chat');
require __DIR__ . '/partials_header.php';
?>
<h1 style="margin-bottom:4px;"><?= t('chat.title') ?></h1>
<p class="muted" style="margin-top:0;"><?= t('chat.intro') ?></p>

<?php if (!$configured): ?>
    <div class="alert error">
        <?= t('chat.not_configured') ?>
        <?php if (Auth::isAdmin()): ?><a href="admin_settings.php#ai"><?= t('chat.configure_link') ?></a><?php endif; ?>
    </div>
<?php endif; ?>

<div class="chat-shell">
    <aside class="chat-sidebar">
        <a href="script_chat.php" class="btn" style="display:block;text-align:center;margin-bottom:12px;">+ <?= t('chat.new_chat') ?></a>

        <div class="chat-conv-list">
            <?php foreach ($conversations as $conv): ?>
                <div class="chat-conv-item<?= $activeConversation && (int) $conv['id'] === (int) $activeConversation['id'] ? ' is-active' : '' ?>">
                    <a href="script_chat.php?c=<?= (int) $conv['id'] ?>" title="<?= htmlspecialchars($conv['title']) ?>"><?= htmlspecialchars($conv['title'] ?: t('chat.untitled')) ?></a>
                    <button type="button" class="chat-conv-delete" data-delete-conv="<?= (int) $conv['id'] ?>" title="<?= t('common.delete') ?>">×</button>
                </div>
            <?php endforeach; ?>
            <?php if ($conversations === []): ?>
                <p class="muted" style="font-size:13px;"><?= t('chat.no_conversations') ?></p>
            <?php endif; ?>
        </div>

        <div class="chat-memory-box">
            <strong style="font-size:13px;">🧠 <?= t('chat.memory_title') ?></strong>
            <p class="muted" style="font-size:12px;margin:4px 0 8px;"><?= t('chat.memory_hint') ?></p>
            <ul id="memoryList">
                <?php foreach ($memories as $memory): ?>
                    <li data-memory-id="<?= (int) $memory['id'] ?>">
                        <span><?= htmlspecialchars($memory['content']) ?></span>
                        <button type="button" class="chat-conv-delete" data-delete-memory="<?= (int) $memory['id'] ?>">×</button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div style="display:flex;gap:6px;margin-top:8px;">
                <input type="text" id="memoryInput" placeholder="<?= t('chat.memory_placeholder') ?>" style="margin:0;font-size:13px;">
                <button type="button" class="btn secondary" id="memoryAdd" style="padding:6px 12px;"><?= t('chat.memory_add') ?></button>
            </div>
        </div>
    </aside>

    <section class="chat-main">
        <div class="chat-messages" id="chatMessages"
             data-empty-title="<?= htmlspecialchars(t('chat.empty_title')) ?>"
             data-keywords-label="<?= htmlspecialchars(t('chat.analyze_competition')) ?>"
             data-copied-label="<?= htmlspecialchars(t('keywords.copied')) ?>">
            <?php if ($messages === []): ?>
                <div class="chat-empty" id="chatEmpty">
                    <div style="font-size:40px;">🎬</div>
                    <h3><?= t('chat.empty_title') ?></h3>
                    <p class="muted"><?= t('chat.empty_hint') ?></p>
                    <div class="chat-suggestions">
                        <button type="button" class="chat-suggestion" data-suggest><?= t('chat.suggest_1') ?></button>
                        <button type="button" class="chat-suggestion" data-suggest><?= t('chat.suggest_2') ?></button>
                        <button type="button" class="chat-suggestion" data-suggest><?= t('chat.suggest_3') ?></button>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach ($messages as $message): ?>
                <div class="chat-bubble <?= $message['role'] === 'user' ? 'from-user' : 'from-ai' ?>" data-raw="<?= htmlspecialchars($message['content']) ?>"></div>
            <?php endforeach; ?>
        </div>

        <form class="chat-input-row" id="chatForm">
            <?= Csrf::field() ?>
            <input type="hidden" id="conversationId" value="<?= $activeConversation ? (int) $activeConversation['id'] : 0 ?>">
            <textarea id="chatInput" rows="1" placeholder="<?= t('chat.input_placeholder') ?>" <?= $configured ? '' : 'disabled' ?>></textarea>
            <button type="submit" class="btn" id="chatSend" <?= $configured ? '' : 'disabled' ?>><?= t('chat.send') ?></button>
        </form>
    </section>
</div>

<script src="assets/js/chat.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
