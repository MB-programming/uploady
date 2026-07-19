// AI script chat frontend: send flow, markdown-lite rendering, per-hashtag copy chips,
// and memory management. Everything lives here because the CSP forbids inline scripts.
(function () {
    var form = document.getElementById('chatForm');
    var input = document.getElementById('chatInput');
    var sendBtn = document.getElementById('chatSend');
    var messagesEl = document.getElementById('chatMessages');
    var convIdEl = document.getElementById('conversationId');
    if (!form || !messagesEl) return;

    var csrf = form.querySelector('input[name="csrf_token"]').value;
    var copiedLabel = messagesEl.getAttribute('data-copied-label') || '✓';
    var keywordsLabel = messagesEl.getAttribute('data-keywords-label') || '';

    // ---- rendering -------------------------------------------------------------

    function escapeHtml(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Minimal markdown: headings, bold, bullet/numbered lists, links, hashtag chips.
    function renderMarkdown(raw) {
        var lines = raw.split('\n');
        var html = '';
        var inList = false;

        lines.forEach(function (line) {
            var l = escapeHtml(line);

            // [text](url) links, then bare URLs — target=_blank, http(s) only.
            l = l.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
            l = l.replace(/(^|[^"'>])(https?:\/\/[^\s<]+)/g, '$1<a href="$2" target="_blank" rel="noopener noreferrer">$2</a>');
            l = l.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            // Hashtag chips (skip lines that are markdown headings).
            if (!/^#{1,4}\s/.test(line)) {
                l = l.replace(/(^|\s)#([\p{L}\p{N}_]+)/gu, '$1<button type="button" class="hashtag-chip" data-hashtag="#$2">#$2</button>');
            }

            var listMatch = /^\s*([-*]|\d+[.)])\s+(.*)$/.exec(l);
            var headingMatch = /^(#{1,4})\s+(.*)$/.exec(l);

            if (listMatch) {
                if (!inList) { html += '<ul>'; inList = true; }
                html += '<li>' + listMatch[2] + '</li>';
                return;
            }
            if (inList) { html += '</ul>'; inList = false; }

            if (headingMatch) {
                html += '<h4>' + headingMatch[2] + '</h4>';
            } else if (l.trim() === '') {
                html += '<div class="chat-gap"></div>';
            } else {
                html += '<p>' + l + '</p>';
            }
        });
        if (inList) html += '</ul>';
        return html;
    }

    function decorateBubble(bubble) {
        var raw = bubble.getAttribute('data-raw');
        if (raw === null) return;
        if (bubble.classList.contains('from-user')) {
            bubble.textContent = raw;
        } else {
            bubble.innerHTML = renderMarkdown(raw);
            addCompetitionLink(bubble);
        }
        bubble.removeAttribute('data-raw');
    }

    // Under every AI reply that contains hashtags: a link to the keyword tool prefilled
    // with the reply's first hashtag, where the REAL competition numbers live.
    function addCompetitionLink(bubble) {
        var firstTag = bubble.querySelector('.hashtag-chip');
        if (!firstTag || !keywordsLabel) return;
        var seed = firstTag.getAttribute('data-hashtag').replace(/^#/, '').replace(/_/g, ' ');
        var link = document.createElement('a');
        link.className = 'chat-competition-link';
        link.href = 'keywords.php?seed=' + encodeURIComponent(seed);
        link.textContent = '🔍 ' + keywordsLabel;
        bubble.appendChild(link);
    }

    messagesEl.querySelectorAll('.chat-bubble').forEach(decorateBubble);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    // Per-hashtag copy on click (event delegation covers future bubbles too).
    messagesEl.addEventListener('click', function (e) {
        var chip = e.target.closest('.hashtag-chip');
        if (!chip) return;
        var tag = chip.getAttribute('data-hashtag');
        var done = function () {
            var original = chip.textContent;
            chip.textContent = copiedLabel;
            chip.classList.add('copied');
            setTimeout(function () { chip.textContent = original; chip.classList.remove('copied'); }, 1200);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(tag).then(done);
        } else {
            var area = document.createElement('textarea');
            area.value = tag;
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            document.body.removeChild(area);
            done();
        }
    });

    // ---- sending ---------------------------------------------------------------

    function appendBubble(role, raw) {
        var empty = document.getElementById('chatEmpty');
        if (empty) empty.remove();
        var bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + (role === 'user' ? 'from-user' : 'from-ai');
        bubble.setAttribute('data-raw', raw);
        messagesEl.appendChild(bubble);
        decorateBubble(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
        return bubble;
    }

    function api(fields) {
        var body = new URLSearchParams();
        body.set('csrf_token', csrf);
        Object.keys(fields).forEach(function (k) { body.set(k, fields[k]); });
        return fetch('script_chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: body.toString()
        }).then(function (r) { return r.json(); });
    }

    var pending = false;
    function sendMessage() {
        var text = input.value.trim();
        if (!text || pending) return;
        pending = true;
        sendBtn.disabled = true;
        input.value = '';
        autosize();

        appendBubble('user', text);
        var typing = document.createElement('div');
        typing.className = 'chat-bubble from-ai chat-typing';
        typing.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(typing);
        messagesEl.scrollTop = messagesEl.scrollHeight;

        api({ action: 'send', conversation_id: convIdEl.value, message: text })
            .then(function (data) {
                typing.remove();
                if (data.reply) {
                    appendBubble('assistant', data.reply);
                    if (data.is_new && data.conversation_id) {
                        convIdEl.value = data.conversation_id;
                        // Keep the URL shareable/reload-safe without reloading the page.
                        history.replaceState(null, '', 'script_chat.php?c=' + data.conversation_id);
                    }
                } else {
                    if (data.conversation_id) convIdEl.value = data.conversation_id;
                    appendBubble('assistant', '⚠️ ' + (data.error || 'Error'));
                }
            })
            .catch(function () {
                typing.remove();
                appendBubble('assistant', '⚠️ Network error');
            })
            .then(function () {
                pending = false;
                sendBtn.disabled = false;
                input.focus();
            });
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); sendMessage(); });
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    function autosize() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    }
    input.addEventListener('input', autosize);

    document.querySelectorAll('[data-suggest]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            input.value = btn.textContent.trim();
            autosize();
            input.focus();
        });
    });

    // ---- conversations & memory sidebar ---------------------------------------

    document.querySelectorAll('[data-delete-conv]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            api({ action: 'delete_conversation', id: btn.getAttribute('data-delete-conv') }).then(function () {
                var item = btn.closest('.chat-conv-item');
                var wasActive = item && item.classList.contains('is-active');
                if (item) item.remove();
                if (wasActive) window.location.href = 'script_chat.php';
            });
        });
    });

    var memoryInput = document.getElementById('memoryInput');
    var memoryAdd = document.getElementById('memoryAdd');
    var memoryList = document.getElementById('memoryList');

    function bindMemoryDelete(btn) {
        btn.addEventListener('click', function () {
            api({ action: 'delete_memory', id: btn.getAttribute('data-delete-memory') }).then(function () {
                var li = btn.closest('li');
                if (li) li.remove();
            });
        });
    }
    document.querySelectorAll('[data-delete-memory]').forEach(bindMemoryDelete);

    if (memoryAdd && memoryInput && memoryList) {
        var addMemory = function () {
            var content = memoryInput.value.trim();
            if (!content) return;
            api({ action: 'add_memory', content: content }).then(function (data) {
                if (!data.ok) return;
                memoryInput.value = '';
                var li = document.createElement('li');
                li.setAttribute('data-memory-id', data.id);
                var span = document.createElement('span');
                span.textContent = data.content;
                var del = document.createElement('button');
                del.type = 'button';
                del.className = 'chat-conv-delete';
                del.setAttribute('data-delete-memory', data.id);
                del.textContent = '×';
                bindMemoryDelete(del);
                li.appendChild(span);
                li.appendChild(del);
                memoryList.appendChild(li);
            });
        };
        memoryAdd.addEventListener('click', addMemory);
        memoryInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); addMemory(); }
        });
    }
})();
