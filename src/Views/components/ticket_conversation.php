<?php
$viewerUserId = isset($viewerUserId) ? (int)$viewerUserId : (int)(\App\Core\Auth::user()['id'] ?? 0);
$viewerRoleGroup = $viewerRoleGroup ?? (in_array((string)(\App\Core\Auth::user()['role'] ?? ''), ['admin', 'staff'], true) ? 'staff' : 'user');
$conversationState = $conversationState ?? ['other_last_read_message_id' => null, 'typing_users' => []];
$otherLastReadMessageId = isset($conversationState['other_last_read_message_id']) ? (int)$conversationState['other_last_read_message_id'] : null;

$avatarUrlFor = static function (?string $path): ?string {
    if (empty($path)) {
        return null;
    }

    return \App\Config\App::path('/' . ltrim((string)$path, '/'));
};

$initialsFor = static function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return '?';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials !== '' ? $initials : strtoupper(substr($name, 0, 1));
};

$chatConfig = [
    'threadUrl' => \App\Config\App::basePath() . '/tickets/thread/' . (int)$ticket['id'],
    'messageUrl' => \App\Config\App::basePath() . '/tickets/message/' . (int)$ticket['id'],
    'readUrl' => \App\Config\App::basePath() . '/tickets/read/' . (int)$ticket['id'],
    'typingUrl' => \App\Config\App::basePath() . '/tickets/typing/' . (int)$ticket['id'],
    'csrfToken' => \App\Core\Session::csrfToken(),
    'viewerUserId' => $viewerUserId,
    'viewerRoleGroup' => $viewerRoleGroup,
    'otherLastReadMessageId' => $otherLastReadMessageId,
    'typingUsers' => $conversationState['typing_users'] ?? [],
];

$initialLastOwnSeenMessageId = null;
if ($otherLastReadMessageId !== null) {
    for ($index = count($messages) - 1; $index >= 0; $index--) {
        $candidate = $messages[$index];
        if ((int)$candidate['user_id'] === $viewerUserId && (int)$candidate['id'] <= $otherLastReadMessageId) {
            $initialLastOwnSeenMessageId = (int)$candidate['id'];
            break;
        }
    }
}

$lastMessageId = 0;
if (!empty($messages)) {
    $lastMessage = end($messages);
    $lastMessageId = (int)($lastMessage['id'] ?? 0);
    reset($messages);
}
?>
<div class="card bg-base-100 shadow-sm">
    <div class="border-b border-base-200 px-5 py-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="card-title text-base">Conversation</h4>
                <p class="text-sm opacity-60">Live updates, reply context, and read state are enabled.</p>
            </div>
            <div class="badge badge-ghost badge-sm" data-chat-presence>Live</div>
        </div>
    </div>
    <div class="px-2 pb-2 pt-3">
        <div class="h-[28rem] overflow-y-auto rounded-2xl bg-base-200/40 px-3 py-3 md:px-4" data-ticket-thread data-last-message-id="<?= $lastMessageId ?>">
            <div class="space-y-4" data-ticket-messages>
                <?php if (empty($messages)): ?>
                    <div class="rounded-2xl border border-dashed border-base-300 px-4 py-8 text-center text-sm opacity-60" data-empty-state>
                        No messages yet. Start the conversation below.
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <?php
                        $isOwnMessage = (int)$msg['user_id'] === $viewerUserId;
                        $isStaffMessage = in_array((string)$msg['user_role'], ['admin', 'staff'], true);
                        $replyPreview = '';
                        if (!empty($msg['reply_id'])) {
                            $replyPreview .= '<div class="mb-2 rounded-2xl border border-base-300/70 bg-base-100/70 px-3 py-2 text-left text-xs">';
                            $replyPreview .= '<div class="font-semibold opacity-80">' . htmlspecialchars((string)$msg['reply_user_name'], ENT_QUOTES, 'UTF-8') . '</div>';
                            $replyPreview .= '<div class="mt-1 line-clamp-2 opacity-70">' . htmlspecialchars((string)$msg['reply_message'], ENT_QUOTES, 'UTF-8') . '</div>';
                            $replyPreview .= '</div>';
                        }
                        $roleBadge = '';
                        if (!$isOwnMessage && $isStaffMessage) {
                            $roleBadge = '<span class="badge badge-primary badge-xs">Staff</span>';
                        } elseif (!$isOwnMessage && !$isStaffMessage && $viewerRoleGroup === 'staff') {
                            $roleBadge = '<span class="badge badge-secondary badge-xs">Tenant</span>';
                        }
                        $bubbleClass = $isOwnMessage ? 'chat-bubble-primary' : 'chat-bubble';
                        $avatarUrl = $avatarUrlFor($msg['user_profile_picture'] ?? null);
                        $initials = $initialsFor((string)$msg['user_name']);
                        $seenLabel = $initialLastOwnSeenMessageId === (int)$msg['id']
                            ? ($viewerRoleGroup === 'staff' ? 'Seen by tenant' : 'Seen by staff')
                            : '';
                        ?>
                        <div class="chat <?= $isOwnMessage ? 'chat-end' : 'chat-start' ?>" data-message-id="<?= (int)$msg['id'] ?>" data-sender-id="<?= (int)$msg['user_id'] ?>">
                            <div class="chat-image avatar">
                                <div class="w-10 rounded-full border border-base-300 bg-base-100 text-base-content overflow-hidden">
                                    <?php if ($avatarUrl): ?>
                                        <img src="<?= htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string)$msg['user_name'], ENT_QUOTES, 'UTF-8') ?>" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <span class="flex h-full w-full items-center justify-center text-xs font-semibold"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="chat-header mb-1 flex items-center gap-2 text-xs">
                                <span class="font-medium"><?= htmlspecialchars($isOwnMessage ? 'You' : (string)$msg['user_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?= $roleBadge ?>
                                <time class="opacity-50"><?= date('M d, H:i', strtotime((string)$msg['created_at'])) ?></time>
                            </div>
                            <div class="max-w-[85%]">
                                <div class="chat-bubble <?= $bubbleClass ?>">
                                    <?= $replyPreview ?>
                                    <?php if (trim((string)$msg['message']) !== ''): ?>
                                        <div class="whitespace-pre-wrap break-words"><?= htmlspecialchars((string)$msg['message'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($msg['attachment_path'])): ?>
                                        <?php
                                        $ext = strtolower(pathinfo($msg['attachment_path'], PATHINFO_EXTENSION));
                                        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                        $attachmentUrl = \App\Config\App::basePath() . '/files/ticket/' . (int)$msg['id'];
                                        ?>
                                        <div class="mt-2">
                                            <?php if ($isImage): ?>
                                                <div class="rounded-lg overflow-hidden border border-base-content/10 max-w-sm bg-base-100 p-1 shadow-sm">
                                                    <a href="<?= $attachmentUrl ?>" target="_blank" class="block hover:opacity-95 transition-opacity">
                                                        <img src="<?= $attachmentUrl ?>" alt="Attachment Thumbnail" class="max-h-48 rounded object-cover w-full">
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <a href="<?= $attachmentUrl ?>" target="_blank" class="btn btn-sm btn-outline btn-secondary gap-2 mt-1">
                                                    <i data-lucide="download" class="h-4 w-4"></i>
                                                    Download Attachment (.<?= htmlspecialchars($ext) ?>)
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-1 flex items-center gap-2 <?= $isOwnMessage ? 'justify-end' : 'justify-start' ?>">
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-xs"
                                        data-reply-button
                                        data-message-id="<?= (int)$msg['id'] ?>"
                                        data-message-user="<?= htmlspecialchars((string)$msg['user_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-message-text="<?= htmlspecialchars((string)$msg['message'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                        Reply
                                    </button>
                                    <span class="text-[11px] opacity-50 <?= $seenLabel === '' ? 'hidden' : '' ?>" data-seen-indicator><?= htmlspecialchars($seenLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="hidden pt-2" data-typing-row>
                <div class="chat chat-start">
                    <div class="chat-bubble">
                        <span class="text-sm" data-typing-text></span>
                        <span class="loading loading-dots loading-xs ml-1 align-middle"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-base-100 shadow-sm">
    <div class="card-body">
        <div class="alert alert-error hidden" data-compose-error></div>

        <div class="alert alert-info hidden" data-reply-preview>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-semibold uppercase tracking-wide opacity-70">Replying to</div>
                <div class="mt-1 font-medium" data-reply-author></div>
                <div class="mt-1 truncate text-sm opacity-70" data-reply-text></div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm btn-circle" data-clear-reply aria-label="Cancel reply">
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        </div>

        <form action="<?= \App\Config\App::basePath() ?>/tickets/message/<?= (int)$ticket['id'] ?>" method="POST" enctype="multipart/form-data" class="space-y-3" data-ticket-compose>
            <input type="hidden" name="csrf_token" value="<?= \App\Core\Session::csrfToken() ?>">
            <input type="hidden" name="reply_to_message_id" value="" data-reply-input>

            <textarea name="message" rows="3" class="textarea textarea-bordered w-full" placeholder="Type your reply here..." data-message-input></textarea>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <label for="ticket-attachment" class="btn btn-ghost btn-sm gap-1 cursor-pointer">
                        <i data-lucide="paperclip" class="h-4 w-4"></i>
                        Attach File
                    </label>
                    <input type="file" id="ticket-attachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt" class="hidden" data-attachment-input>
                    <span class="text-sm opacity-60" data-attachment-name>No file selected</span>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm hidden" data-clear-reply-button>Cancel reply</button>
                    <button type="submit" class="btn btn-primary btn-sm gap-2" data-send-button>
                        <i data-lucide="send-horizontal" class="h-4 w-4"></i>
                        Send Reply
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script id="ticket-chat-config" type="application/json"><?= json_encode($chatConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script>
    (function initTicketConversation() {
        var configNode = document.getElementById('ticket-chat-config');
        var thread = document.querySelector('[data-ticket-thread]');
        var messageList = document.querySelector('[data-ticket-messages]');
        var composeForm = document.querySelector('[data-ticket-compose]');
        if (!configNode || !thread || !messageList || !composeForm) {
            return;
        }

        var config = JSON.parse(configNode.textContent || '{}');
        var viewerUserId = Number(config.viewerUserId || 0);
        var viewerRoleGroup = config.viewerRoleGroup || 'user';
        var otherLastReadMessageId = config.otherLastReadMessageId === null ? null : Number(config.otherLastReadMessageId || 0);
        var typingUsers = Array.isArray(config.typingUsers) ? config.typingUsers : [];
        var lastMessageId = Number(thread.getAttribute('data-last-message-id') || '0');
        var pollingDelay = 3000;
        var pollTimer = 0;
        var typingStopTimer = 0;
        var isSending = false;
        var lastTypingState = false;
        var lastTypingPushAt = 0;

        var presenceBadge = document.querySelector('[data-chat-presence]');
        var typingRow = document.querySelector('[data-typing-row]');
        var typingText = document.querySelector('[data-typing-text]');
        var replyPreview = document.querySelector('[data-reply-preview]');
        var replyAuthor = document.querySelector('[data-reply-author]');
        var replyText = document.querySelector('[data-reply-text]');
        var replyInput = document.querySelector('[data-reply-input]');
        var clearReplyButtons = document.querySelectorAll('[data-clear-reply], [data-clear-reply-button]');
        var messageInput = document.querySelector('[data-message-input]');
        var attachmentInput = document.querySelector('[data-attachment-input]');
        var attachmentName = document.querySelector('[data-attachment-name]');
        var sendButton = document.querySelector('[data-send-button]');
        var composeError = document.querySelector('[data-compose-error]');

        var escapeHtml = function (value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        var replyExcerpt = function (value) {
            var normalized = String(value || '').replace(/\s+/g, ' ').trim();
            return normalized.length > 120 ? normalized.slice(0, 117) + '...' : normalized;
        };

        var initialsFor = function (name) {
            var normalized = String(name || '').trim();
            if (!normalized) {
                return '?';
            }

            var parts = normalized.split(/\s+/).filter(Boolean).slice(0, 2);
            var initials = parts.map(function (part) {
                return part.charAt(0).toUpperCase();
            }).join('');

            return initials || normalized.charAt(0).toUpperCase();
        };

        var renderAvatar = function (name, profilePictureUrl) {
            if (profilePictureUrl) {
                return '' +
                        '<div class="chat-image avatar">' +
                            '<div class="w-10 rounded-full border border-base-300 bg-base-100 text-base-content overflow-hidden">' +
                            '<img src="' + escapeHtml(profilePictureUrl) + '" alt="' + escapeHtml(name || 'User') + '" class="h-full w-full object-cover">' +
                        '</div>' +
                    '</div>';
            }

            return '' +
                '<div class="chat-image avatar">' +
                    '<div class="w-10 rounded-full border border-base-300 bg-base-100 text-base-content overflow-hidden">' +
                        '<span class="flex h-full w-full items-center justify-center text-xs font-semibold">' + escapeHtml(initialsFor(name)) + '</span>' +
                    '</div>' +
                '</div>';
        };

        var clearComposerError = function () {
            if (!composeError) {
                return;
            }

            composeError.textContent = '';
            composeError.classList.add('hidden');
        };

        var showComposerError = function (message) {
            if (!composeError) {
                return;
            }

            composeError.textContent = String(message || 'Something went wrong.');
            composeError.classList.remove('hidden');
        };

        var statusBadgeClass = function (status) {
            if (status === 'open') return 'badge-warning';
            if (status === 'in_progress') return 'badge-info';
            if (status === 'resolved') return 'badge-success';
            return 'badge-ghost';
        };

        var updateStatusBadge = function (status) {
            var labelNodes = document.querySelectorAll('[data-ticket-status-label]');
            labelNodes.forEach(function (node) {
                node.textContent = String(status || '').replace(/_/g, ' ').replace(/\b\w/g, function (char) { return char.toUpperCase(); });
            });

            var badgeNodes = document.querySelectorAll('[data-ticket-status-badge]');
            badgeNodes.forEach(function (node) {
                node.className = 'badge ' + statusBadgeClass(status) + ' badge-sm';
            });
        };

        var renderTypingUsers = function () {
            if (!typingRow || !typingText) {
                return;
            }

            if (!typingUsers.length) {
                typingRow.classList.add('hidden');
                return;
            }

            var label = '';
            if (typingUsers.length === 1) {
                label = typingUsers[0].user_name + ' is typing';
            } else {
                label = typingUsers.length + ' people are typing';
            }

            typingText.textContent = label;
            typingRow.classList.remove('hidden');
        };

        var updateSeenIndicators = function () {
            var indicators = thread.querySelectorAll('[data-seen-indicator]');
            indicators.forEach(function (indicator) {
                indicator.textContent = '';
                indicator.classList.add('hidden');
            });

            if (!otherLastReadMessageId) {
                return;
            }

            var candidates = Array.prototype.slice.call(thread.querySelectorAll('[data-message-id]')).filter(function (node) {
                return Number(node.getAttribute('data-sender-id') || '0') === viewerUserId
                    && Number(node.getAttribute('data-message-id') || '0') <= otherLastReadMessageId;
            });

            var latestSeenNode = candidates.length ? candidates[candidates.length - 1] : null;
            if (!latestSeenNode) {
                return;
            }

            var indicator = latestSeenNode.querySelector('[data-seen-indicator]');
            if (!indicator) {
                return;
            }

            indicator.textContent = viewerRoleGroup === 'staff' ? 'Seen by tenant' : 'Seen by staff';
            indicator.classList.remove('hidden');
        };

        var renderMessage = function (message) {
            var isOwn = Number(message.user_id) === viewerUserId;
            var isStaffMessage = ['admin', 'staff'].indexOf(String(message.user_role || '')) !== -1;
            var roleBadge = '';
            if (!isOwn && isStaffMessage) {
                roleBadge = '<span class="badge badge-primary badge-xs">Staff</span>';
            } else if (!isOwn && !isStaffMessage && viewerRoleGroup === 'staff') {
                roleBadge = '<span class="badge badge-secondary badge-xs">Tenant</span>';
            }

            var replyBlock = '';
            if (message.reply) {
                replyBlock =
                    '<div class="mb-2 rounded-2xl border border-base-300/70 bg-base-100/70 px-3 py-2 text-left text-xs">' +
                        '<div class="font-semibold opacity-80">' + escapeHtml(message.reply.user_name) + '</div>' +
                        '<div class="mt-1 line-clamp-2 opacity-70">' + escapeHtml(replyExcerpt(message.reply.message)) + '</div>' +
                    '</div>';
            }

            var attachmentBlock = '';
            if (message.attachment_url) {
                var ext = String(message.attachment_ext || '').toLowerCase();
                var isImg = ['jpg', 'jpeg', 'png', 'webp', 'gif'].indexOf(ext) !== -1;
                if (isImg) {
                    attachmentBlock =
                        '<div class="mt-2 rounded-lg overflow-hidden border border-base-content/10 max-w-sm bg-base-100 p-1 shadow-sm">' +
                            '<a href="' + escapeHtml(message.attachment_url) + '" target="_blank" class="block hover:opacity-95 transition-opacity">' +
                                '<img src="' + escapeHtml(message.attachment_url) + '" alt="Attachment Thumbnail" class="max-h-48 rounded object-cover w-full">' +
                            '</a>' +
                        '</div>';
                } else {
                    attachmentBlock =
                        '<div class="mt-2">' +
                            '<a href="' + escapeHtml(message.attachment_url) + '" target="_blank" class="btn btn-sm btn-outline btn-secondary gap-2 mt-1">' +
                                '<i data-lucide="download" class="h-4 w-4"></i>' +
                                'Download Attachment (.' + escapeHtml(ext) + ')' +
                            '</a>' +
                        '</div>';
                }
            }

            return '' +
                '<div class="chat ' + (isOwn ? 'chat-end' : 'chat-start') + '" data-message-id="' + Number(message.id) + '" data-sender-id="' + Number(message.user_id) + '">' +
                    renderAvatar(message.user_name, message.user_profile_picture_url) +
                    '<div class="chat-header mb-1 flex items-center gap-2 text-xs">' +
                        '<span class="font-medium">' + escapeHtml(isOwn ? 'You' : message.user_name) + '</span>' +
                        roleBadge +
                        '<time class="opacity-50">' + escapeHtml(message.created_label || '') + '</time>' +
                    '</div>' +
                    '<div class="max-w-[85%]">' +
                        '<div class="chat-bubble ' + (isOwn ? 'chat-bubble-primary' : 'chat-bubble') + '">' +
                            replyBlock +
                            (String(message.message || '').trim() !== '' ? '<div class="whitespace-pre-wrap break-words">' + escapeHtml(message.message || '') + '</div>' : '') +
                            attachmentBlock +
                        '</div>' +
                        '<div class="mt-1 flex items-center gap-2 ' + (isOwn ? 'justify-end' : 'justify-start') + '">' +
                            '<button type="button" class="btn btn-ghost btn-xs" data-reply-button data-message-id="' + Number(message.id) + '" data-message-user="' + escapeHtml(message.user_name) + '" data-message-text="' + escapeHtml(message.message || '') + '">Reply</button>' +
                            '<span class="hidden text-[11px] opacity-50" data-seen-indicator></span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        };

        var appendMessages = function (messages, shouldScroll) {
            if (!messages || !messages.length) {
                return;
            }

            var emptyState = messageList.querySelector('[data-empty-state]');
            if (emptyState) {
                emptyState.remove();
            }

            messages.forEach(function (message) {
                if (!message || messageList.querySelector('[data-message-id="' + Number(message.id) + '"]')) {
                    return;
                }

                messageList.insertAdjacentHTML('beforeend', renderMessage(message));
                lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
            });

            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            updateSeenIndicators();

            if (shouldScroll) {
                thread.scrollTop = thread.scrollHeight;
            }
        };

        var setReplyState = function (messageId, author, text) {
            if (!replyPreview || !replyAuthor || !replyText || !replyInput) {
                return;
            }

            clearComposerError();
            replyInput.value = String(messageId);
            replyAuthor.textContent = author;
            replyText.textContent = replyExcerpt(text);
            replyPreview.classList.remove('hidden');
        };

        var clearReplyState = function () {
            if (replyInput) {
                replyInput.value = '';
            }
            if (replyPreview) {
                replyPreview.classList.add('hidden');
            }
        };

        var pushTypingState = function (isTyping) {
            var now = Date.now();
            if (isTyping === lastTypingState && (isTyping === false || now - lastTypingPushAt < 1200)) {
                return;
            }

            lastTypingState = isTyping;
            lastTypingPushAt = now;

            var formData = new FormData();
            formData.append('csrf_token', config.csrfToken || '');
            formData.append('is_typing', isTyping ? '1' : '0');

            fetch(config.typingUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            }).catch(function () {});
        };

        var schedulePolling = function (delay) {
            window.clearTimeout(pollTimer);
            pollTimer = window.setTimeout(pollConversation, delay);
        };

        var pollConversation = function () {
            if (document.hidden) {
                schedulePolling(pollingDelay);
                return;
            }

            fetch(config.threadUrl + '?after=' + encodeURIComponent(String(lastMessageId)), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.success) {
                        schedulePolling(4500);
                        return;
                    }

                    var nearBottom = thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120;
                    appendMessages(payload.messages || [], nearBottom);
                    otherLastReadMessageId = payload.conversation ? payload.conversation.other_last_read_message_id : otherLastReadMessageId;
                    typingUsers = payload.conversation && Array.isArray(payload.conversation.typing_users) ? payload.conversation.typing_users : [];
                    renderTypingUsers();
                    updateSeenIndicators();
                    if (payload.ticket && payload.ticket.status) {
                        updateStatusBadge(payload.ticket.status);
                    }
                    schedulePolling(pollingDelay);
                })
                .catch(function () {
                    schedulePolling(4500);
                });
        };

        var sendReadReceipt = function () {
            var formData = new FormData();
            formData.append('csrf_token', config.csrfToken || '');

            fetch(config.readUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            }).catch(function () {});
        };

        messageList.addEventListener('click', function (event) {
            var replyButton = event.target.closest('[data-reply-button]');
            if (!replyButton) {
                return;
            }

            setReplyState(
                Number(replyButton.getAttribute('data-message-id') || '0'),
                replyButton.getAttribute('data-message-user') || 'Unknown',
                replyButton.getAttribute('data-message-text') || ''
            );

            if (messageInput) {
                messageInput.focus();
            }
        });

        clearReplyButtons.forEach(function (button) {
            button.addEventListener('click', clearReplyState);
        });

        if (attachmentInput && attachmentName) {
            attachmentInput.addEventListener('change', function () {
                attachmentName.textContent = attachmentInput.files && attachmentInput.files[0]
                    ? attachmentInput.files[0].name
                    : 'No file selected';
            });
        }

        if (messageInput) {
            messageInput.addEventListener('input', function () {
                window.clearTimeout(typingStopTimer);

                if (messageInput.value.trim() === '') {
                    pushTypingState(false);
                    return;
                }

                pushTypingState(true);
                typingStopTimer = window.setTimeout(function () {
                    pushTypingState(false);
                }, 1800);
            });

            messageInput.addEventListener('blur', function () {
                pushTypingState(false);
            });
        }

        composeForm.addEventListener('submit', function (event) {
            if (!window.fetch) {
                return;
            }

            event.preventDefault();
            if (isSending) {
                return;
            }

            clearComposerError();
            var formData = new FormData(composeForm);
            isSending = true;
            if (sendButton) {
                sendButton.disabled = true;
            }

            fetch(config.messageUrl, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || !payload.success) {
                        showComposerError(payload && payload.message ? payload.message : 'Failed to send message.');
                        return;
                    }

                    if (payload.message) {
                        appendMessages([payload.message], true);
                    }

                    otherLastReadMessageId = payload.conversation ? payload.conversation.other_last_read_message_id : otherLastReadMessageId;
                    typingUsers = payload.conversation && Array.isArray(payload.conversation.typing_users) ? payload.conversation.typing_users : [];
                    renderTypingUsers();
                    updateSeenIndicators();
                    composeForm.reset();
                    clearReplyState();
                    attachmentName.textContent = 'No file selected';
                    pushTypingState(false);
                })
                .catch(function () {
                    showComposerError('Failed to send message.');
                })
                .finally(function () {
                    isSending = false;
                    if (sendButton) {
                        sendButton.disabled = false;
                    }
                });
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                sendReadReceipt();
                pollConversation();
            }
        });

        renderTypingUsers();
        updateSeenIndicators();
        updateStatusBadge('<?= htmlspecialchars((string)$ticket['status'], ENT_QUOTES, 'UTF-8') ?>');
        thread.scrollTop = thread.scrollHeight;
        sendReadReceipt();
        schedulePolling(pollingDelay);
    })();
</script>
