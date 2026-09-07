(function () {
    'use strict';

    function el(selector) {
        return document.querySelector(selector);
    }

    function scrollToBottom(container) {
        container.scrollTop = container.scrollHeight;
    }

    function escapeAttr(value) {
        return String(value).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function formatTime(iso) {
        var d = new Date(iso.replace(' ', 'T'));
        if (isNaN(d.getTime())) {
            return '';
        }
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function roleBadgeHtml(role) {
        if (role === 'admin') {
            return '<span class="role-badge role-badge-admin">admin</span>';
        }
        if (role === 'moderator') {
            return '<span class="role-badge role-badge-moderator">mod</span>';
        }
        return '';
    }

    function appendMessage(container, opts) {
        var row = document.createElement('div');
        row.className = 'message-row' + (opts.mine ? ' mine' : '');
        row.dataset.messageId = String(opts.id);

        row.innerHTML =
            '<img class="avatar avatar-sm" src="' + escapeAttr(opts.avatar || '/assets/img/default-avatar.svg') + '" alt="">' +
            '<div>' +
            '<div class="message-meta">' + escapeAttr(opts.username) + roleBadgeHtml(opts.role) + ' · ' + escapeAttr(formatTime(opts.createdAt)) + '</div>' +
            '<div class="message-bubble">' + opts.bodyHtml + '</div>' +
            '</div>';

        container.appendChild(row);
    }

    function initComposer(form, messagesContainer, currentUserId) {
        var textarea = form.querySelector('textarea');

        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        });

        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var body = textarea.value.trim();
            if (body === '') {
                return;
            }

            var data = new FormData(form);
            var button = form.querySelector('button[type="submit"]');
            button.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'fetch' },
                credentials: 'same-origin',
            }).then(function (res) {
                if (!res.ok && res.status !== 302) {
                    throw new Error('send-failed');
                }
                textarea.value = '';
                textarea.style.height = 'auto';
            }).catch(function () {
                alert("L'envoi du message a echoue. Recharge la page et reessaie.");
            }).finally(function () {
                button.disabled = false;
                textarea.focus();
            });
        });
    }

    function initStream(panel) {
        var messagesContainer = el('#chat-messages');
        var roomId = panel.dataset.roomId ? parseInt(panel.dataset.roomId, 10) : null;
        var dmWith = panel.dataset.dmWith ? parseInt(panel.dataset.dmWith, 10) : null;
        var currentUserId = parseInt(panel.dataset.currentUserId, 10);
        var lastMessageId = parseInt(panel.dataset.lastMessageId || '0', 10);
        var lastDmId = parseInt(panel.dataset.lastDmId || '0', 10);

        var params = new URLSearchParams();
        if (roomId) {
            params.set('room_id', String(roomId));
            params.set('last_message_id', String(lastMessageId));
        }
        if (dmWith) {
            params.set('dm_with', String(dmWith));
            params.set('last_dm_id', String(lastDmId));
        }

        if (!roomId && !dmWith) {
            return;
        }

        var source = new EventSource('/stream.php?' + params.toString());

        source.addEventListener('message', function (e) {
            var data = JSON.parse(e.data);
            if (roomId && data.room_id === roomId && messagesContainer) {
                appendMessage(messagesContainer, {
                    id: data.id,
                    mine: data.user_id === currentUserId,
                    username: data.username,
                    avatar: data.avatar_path,
                    role: data.role,
                    bodyHtml: data.body,
                    createdAt: data.created_at,
                });
                scrollToBottom(messagesContainer);
            }
        });

        source.addEventListener('dm', function (e) {
            var data = JSON.parse(e.data);
            var belongsToThread = dmWith && (data.sender_id === dmWith || data.recipient_id === dmWith);
            if (belongsToThread && messagesContainer) {
                appendMessage(messagesContainer, {
                    id: data.id,
                    mine: data.sender_id === currentUserId,
                    username: data.sender_username,
                    avatar: data.sender_avatar_path,
                    bodyHtml: data.body,
                    createdAt: data.created_at,
                });
                scrollToBottom(messagesContainer);
            }
        });
    }

    function initSidebarToggle() {
        var sidebar = el('.sidebar');
        if (!sidebar) {
            return;
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'sidebar-toggle';
        button.setAttribute('aria-label', 'Afficher les salons');
        button.textContent = '☰ Salons';
        button.addEventListener('click', function () {
            sidebar.classList.toggle('mobile-open');
        });

        var shell = el('.app-shell');
        if (shell) {
            shell.parentNode.insertBefore(button, shell);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var panel = el('#chat-panel');
        var messagesContainer = el('#chat-messages');

        if (messagesContainer) {
            scrollToBottom(messagesContainer);
        }

        var composerForm = el('#composer-form');
        if (composerForm) {
            initComposer(composerForm, messagesContainer, panel ? parseInt(panel.dataset.currentUserId, 10) : null);
        }

        if (panel) {
            initStream(panel);
        }

        initSidebarToggle();
    });
})();
