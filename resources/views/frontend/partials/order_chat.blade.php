@php
    $chatData = $chatData ?? null;
    $canView = (bool) data_get($chatData, 'can_view', false);
@endphp

@if($canView)
    <div class="order-chat-widget" data-order-chat-widget data-order-no="{{ data_get($chatData, 'order_no') }}">
        <div class="order-chat-widget__header">
            <div>
                <div class="order-chat-widget__eyebrow">Conversation contextualisée</div>
                <h3 class="order-chat-widget__title">{{ data_get($chatData, 'title', 'Conversation de commande') }}</h3>
                <p class="order-chat-widget__subtitle">Client, restaurant et livreur échangent dans le même fil lié à la commande.</p>
            </div>
            <div class="order-chat-widget__badge">
                <i class="fas fa-comments"></i>
                <span>
                    @if(data_get($chatData, 'unread_count', 0) > 0)
                        {{ data_get($chatData, 'unread_label') }}
                    @else
                        {{ count(data_get($chatData, 'messages', [])) }} messages
                    @endif
                </span>
            </div>
        </div>

        <div class="order-chat-widget__participants">
            @foreach(data_get($chatData, 'participants', []) as $participant)
                <div class="order-chat-participant">
                    <span class="order-chat-participant__label">{{ $participant['label'] ?? 'Participant' }}</span>
                    <strong>{{ $participant['name'] ?? 'N/A' }}</strong>
                </div>
            @endforeach
        </div>

        <div class="order-chat-widget__messages"
             id="order-chat-messages-{{ data_get($chatData, 'order_no') }}"
             data-chat-messages
             data-refresh-url="{{ data_get($chatData, 'messages_url') }}"
             data-current-role="{{ data_get($chatData, 'role') }}">
            @include('frontend.partials.order_chat_messages', [
                'messages' => data_get($chatData, 'messages', []),
                'currentRole' => data_get($chatData, 'role')
            ])
        </div>

        @if(data_get($chatData, 'can_write'))
            <form method="POST" action="{{ data_get($chatData, 'store_url') }}" class="order-chat-widget__form">
                @csrf
                <label class="order-chat-widget__label" for="order-chat-input-{{ data_get($chatData, 'order_no') }}">Envoyer un message</label>
                <textarea id="order-chat-input-{{ data_get($chatData, 'order_no') }}" name="message" rows="3" maxlength="2000" placeholder="Écrivez un message lié à cette commande..."></textarea>
                <div class="order-chat-widget__actions">
                    <small>Les messages sont visibles uniquement par les participants de la commande.</small>
                    <button type="submit" style="display:inline-flex;align-items:center;justify-content:center;background:#009543;color:#fff;font-weight:700;padding:.7rem 1.35rem;border-radius:999px;border:none;cursor:pointer;text-decoration:none;">Envoyer</button>
                </div>
            </form>
        @endif
    </div>
@endif

@once
    <style>
        .order-chat-widget {
            margin-top: 1.5rem;
            background: linear-gradient(180deg, #ffffff 0%, #fff8f1 100%);
            border: 1px solid rgba(249, 115, 22, 0.12);
            border-radius: 24px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
            padding: 1.5rem;
        }
        .order-chat-widget__header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .order-chat-widget__eyebrow {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #ff5a1f;
            font-weight: 800;
            margin-bottom: .3rem;
        }
        .order-chat-widget__title {
            margin: 0;
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 900;
        }
        .order-chat-widget__subtitle {
            margin: .35rem 0 0;
            color: #64748b;
        }
        .order-chat-widget__badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .55rem .9rem;
            border-radius: 999px;
            background: #111827;
            color: #fff;
            font-size: .82rem;
            font-weight: 700;
        }
        .order-chat-widget__participants {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .order-chat-participant {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: .85rem .95rem;
        }
        .order-chat-participant__label {
            display: block;
            color: #ff5a1f;
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 800;
            margin-bottom: .25rem;
        }
        .order-chat-widget__messages {
            display: grid;
            gap: .85rem;
            max-height: 420px;
            overflow-y: auto;
            padding-right: .25rem;
        }
        .order-chat-message-list {
            display: grid;
            gap: .85rem;
        }
        .order-chat-message {
            border-radius: 18px;
            border: 1px solid;
            padding: .9rem 1rem;
            max-width: 86%;
        }
        .order-chat-message.is-mine {
            margin-left: auto;
            box-shadow: 0 10px 24px rgba(249, 115, 22, 0.08);
        }
        .order-chat-message.is-other {
            margin-right: auto;
        }
        .order-chat-message__meta {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            align-items: center;
            margin-bottom: .45rem;
            font-size: .8rem;
        }
        .order-chat-message__label {
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }
        .order-chat-message__time {
            opacity: .72;
            white-space: nowrap;
        }
        .order-chat-message__body {
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.45;
            font-weight: 500;
        }
        .order-chat-empty-state {
            border-radius: 18px;
            border: 1px dashed #ff5a1f;
            background: rgba(255,255,255,.7);
            color: #7c2d12;
            padding: 1rem 1.1rem;
        }
        .order-chat-widget__form {
            margin-top: 1rem;
            display: grid;
            gap: .75rem;
        }
        .order-chat-widget__label {
            font-weight: 800;
            color: #0f172a;
        }
        .order-chat-widget__form textarea {
            width: 100%;
            border-radius: 16px;
            border: 1px solid #cbd5e1;
            padding: .95rem 1rem;
            resize: vertical;
            min-height: 110px;
            outline: none;
            background: #fff;
        }
        .order-chat-widget__form textarea:focus {
            border-color: #ff5a1f;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.12);
        }
        .order-chat-widget__actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .order-chat-widget__actions small {
            color: #64748b;
        }
        .order-chat-message__seen {
            margin-top: .45rem;
            font-size: .74rem;
            font-weight: 800;
            opacity: .82;
            text-align: right;
        }
        @media (max-width: 992px) {
            .order-chat-widget__participants {
                grid-template-columns: 1fr;
            }
            .order-chat-message {
                max-width: 100%;
            }
        }
    </style>
    <script>
        (function () {
            const initWidget = (widget) => {
                if (!widget || widget.dataset.chatInitialized === '1') {
                    return;
                }

                widget.dataset.chatInitialized = '1';
                const messagesBox = widget.querySelector('[data-chat-messages]');
                if (!messagesBox) {
                    return;
                }

                const refreshUrl = messagesBox.dataset.refreshUrl;
                const badge = widget.querySelector('.order-chat-widget__badge span');
                const scrollToBottom = () => {
                    messagesBox.scrollTop = messagesBox.scrollHeight;
                };
                const updateBadge = (count) => {
                    if (!badge) {
                        return;
                    }

                    badge.textContent = count > 0
                        ? (count === 1 ? '1 nouveau message' : `${count} nouveaux messages`)
                        : `${messagesBox.querySelectorAll('.order-chat-message').length} messages`;
                };

                const refreshMessages = async () => {
                    try {
                        const response = await fetch(refreshUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        const data = await response.json();
                        if (!data.status || !data.html) {
                            return;
                        }

                        messagesBox.innerHTML = data.html;
                        scrollToBottom();
                        updateBadge(Number(data.unread_count ?? 0));
                    } catch (error) {
                        console.warn('Chat refresh failed', error);
                    }
                };

                scrollToBottom();
                updateBadge(messagesBox.querySelectorAll('.order-chat-message').length);
                refreshMessages();
                setInterval(refreshMessages, 8000);
            };

            const boot = () => {
                document.querySelectorAll('[data-order-chat-widget]').forEach(initWidget);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>
@endonce
