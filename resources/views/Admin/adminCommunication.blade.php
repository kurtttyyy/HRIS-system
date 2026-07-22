<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communication | Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;transition:margin-left .3s ease}
        main{transition:margin-left .3s ease}
        aside~main{margin-left:4rem}
        aside:hover~main{margin-left:18rem}
        .admin-display{font-family:"Arial Black","Segoe UI",Tahoma,Geneva,Verdana,sans-serif;letter-spacing:-.03em}
        .messenger-shell{background:linear-gradient(180deg,#171717 0%,#202020 100%)}
        .messenger-sidebar{background:linear-gradient(180deg,#161616 0%,#1c1c1c 100%)}
        .messenger-thread{background:radial-gradient(circle at top right, rgba(88,28,135,.25), transparent 24%),linear-gradient(180deg,#202020 0%,#181818 100%)}
        .messenger-scroll::-webkit-scrollbar{width:8px}
        .messenger-scroll::-webkit-scrollbar-thumb{background:#4b5563;border-radius:999px}
        .messenger-scroll::-webkit-scrollbar-track{background:transparent}
        .communication-reveal{opacity:0;transform:translateY(18px);transition:opacity .28s ease,transform .28s ease;will-change:opacity,transform}
        .communication-reveal.reveal-from-top{transform:translateY(-18px)}
        .communication-reveal.is-visible{animation:communication-fade-up .42s cubic-bezier(.22,.9,.2,1) forwards;animation-delay:var(--communication-delay,0ms)}
        .communication-card-motion{transition:transform .24s ease,box-shadow .24s ease,border-color .24s ease,background-color .24s ease}
        .communication-card-motion:hover{transform:translateY(-5px);box-shadow:0 18px 36px rgba(15,23,42,.12)}
        .communication-icon-pop{animation:communication-pop-in .65s cubic-bezier(.22,.9,.2,1) both;animation-delay:var(--communication-delay,0ms)}
        @keyframes communication-fade-up{to{opacity:1;transform:translateY(0)}}
        @keyframes communication-pop-in{0%{opacity:0;transform:scale(.82) rotate(-4deg)}100%{opacity:1;transform:scale(1) rotate(0)}}
        @media (prefers-reduced-motion:reduce){
            .communication-reveal,.communication-icon-pop{animation:none;opacity:1;transform:none}
            .communication-card-motion{transition:none}
            .communication-card-motion:hover{transform:none}
        }
        @media (max-width:1279px){
            #admin-chat-panel{position:fixed;inset:1rem;z-index:80;height:calc(100vh - 2rem)!important;min-height:0!important}
        }
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#f8fafc,_#eef2ff_40%,_#f8fafc_100%)] text-slate-900">
@php
    $directoryMembers = collect($employees ?? []);
    $conversationSummaries = collect($conversationSummaries ?? []);
    $selectedParticipant = $selectedParticipant ?? null;
    $selectedConversation = $selectedConversation ?? null;
    $messages = collect(optional($selectedConversation)->messages ?? []);
    $lastOwnMessageId = (int) optional($messages->filter(fn ($message) => (int) ($message->sender_user_id ?? 0) === (int) auth()->id())->last())->id;
    $availableCount = $directoryMembers->filter(fn ($member) => in_array(strtolower(trim((string) ($member->status ?? ''))), ['approved', 'available'], true))->count();
    $unreadMessageCount = (int) $directoryMembers->sum(fn ($member) => (int) ($member->unread_message_count ?? 0));
@endphp
<div class="flex min-h-screen">
    @include('components.adminSideBar')
    <main class="flex-1 transition-all duration-300">
        @include('components.adminHeader.dashboardHeader', [
            'headerTitle' => 'Communication Hub',
            'headerSubtitle' => 'Open employee threads, send updates, and keep conversations in one place.',
            'headerSearchPlaceholder' => 'Search employees or conversations...',
            'headerSearchInputId' => 'admin-communication-search',
        ])
        <div id="admin-communication-page" class="space-y-8 p-4 pt-20 md:p-8">
            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif
            <div class="grid gap-5 xl:grid-cols-[23rem_minmax(0,1fr)]">
            <section class="communication-reveal flex min-h-[38rem] flex-col rounded-[2rem] border border-slate-200 bg-white/95 p-5 shadow-sm xl:h-[calc(100vh-11rem)] xl:min-h-0" style="--communication-delay:0ms">
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Conversations</p>
                        <h3 class="mt-2 text-xl font-black tracking-tight text-slate-900">Messages</h3>
                        <p class="mt-1 text-sm text-slate-500">Select an employee to open a conversation.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            <i class="fa-solid fa-user-group text-emerald-500"></i>
                            {{ $availableCount }} available employee{{ $availableCount === 1 ? '' : 's' }}
                        </div>
                        <button
                            id="admin-all-filter"
                            type="button"
                            aria-pressed="true"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-900 bg-slate-900 px-3 py-2 text-xs font-semibold text-white shadow-sm"
                            style="--communication-delay:80ms"
                        >
                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                            All
                        </button>
                        <button
                            id="admin-unread-filter"
                            type="button"
                            aria-pressed="false"
                            class="inline-flex items-center gap-2 rounded-full border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 {{ $unreadMessageCount === 0 ? 'cursor-not-allowed opacity-60' : 'hover:border-rose-300 hover:bg-rose-100' }}"
                            style="--communication-delay:90ms"
                            @disabled($unreadMessageCount === 0)
                        >
                            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                            Unread
                            <span id="admin-unread-count" data-unread-total="{{ $unreadMessageCount }}" class="inline-flex min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                {{ $unreadMessageCount > 99 ? '99+' : $unreadMessageCount }}
                            </span>
                        </button>
                    </div>
                </div>
                <div id="admin-communication-directory-grid" class="messenger-scroll mt-4 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto pr-1">
                    @foreach ($directoryMembers as $employee)
                        @php
                            $employeeName = trim(implode(' ', array_filter([$employee->first_name ?? null, $employee->middle_name ?? null, $employee->last_name ?? null])));
                            $employeeName = $employeeName !== '' ? $employeeName : (string) ($employee->email ?? 'Employee');
                            $employeeInitials = strtoupper(substr(trim((string) ($employee->first_name ?? 'E')), 0, 1).substr(trim((string) ($employee->last_name ?? '')), 0, 1));
                            $department = trim((string) ($employee->department ?? optional($employee->employee)->department ?? 'General'));
                            $position = trim((string) ($employee->position ?? optional($employee->employee)->position ?? 'Employee'));
                            $isSelfEmployeeCard = (int) ($employee->id ?? 0) === (int) auth()->id();
                            $employeeUnreadCount = (int) ($employee->unread_message_count ?? 0);
                            $employeeHasUnreadMessages = (bool) ($employee->has_unread_messages ?? false);
                        @endphp
                        <article
                            data-communication-directory-card
                            data-employee-id="{{ (int) ($employee->id ?? 0) }}"
                            data-name="{{ strtolower($employeeName) }}"
                            data-email="{{ strtolower((string) ($employee->email ?? '')) }}"
                            data-position="{{ strtolower($position) }}"
                            data-department="{{ strtolower($department) }}"
                            data-unread="{{ $employeeHasUnreadMessages ? 'true' : 'false' }}"
                            data-unread-count="{{ $employeeUnreadCount }}"
                            class="communication-card-motion communication-reveal rounded-2xl border p-3 shadow-sm {{ (int) ($selectedParticipant?->id ?? 0) === (int) ($employee->id ?? 0) ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-100' : 'border-slate-200 bg-slate-50/70' }}"
                            style="--communication-delay: {{ 110 + (($loop->index % 6) * 35) }}ms;"
                        >
                            <div class="flex items-center gap-3">
                                <div class="communication-icon-pop flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-emerald-600 text-sm font-black text-white" style="--communication-delay: {{ 140 + (($loop->index % 6) * 35) }}ms;">{{ $employeeInitials !== '' ? $employeeInitials : 'EM' }}</div>
                                <div class="min-w-0 flex-1">
                                  <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div data-admin-employee-name-row class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-black text-slate-900">{{ $employeeName }}</p>
                                            @if ($employeeHasUnreadMessages)
                                                <span data-admin-unread-badge data-admin-name-unread class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-[11px] font-bold text-white">{{ $employeeUnreadCount > 99 ? '99+' : $employeeUnreadCount }} unread</span>
                                            @endif
                                        </div>
                                        <p class="truncate text-xs text-slate-500">{{ $position }} - {{ $department !== '' ? $department : 'General' }}</p>
                                    </div>
                                    <span class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400 ring-2 ring-emerald-100" title="Available"></span>
                                  </div>
                                  <div class="mt-2 flex items-center justify-between gap-2">
                                    <p data-admin-message-preview class="truncate text-xs {{ $employeeHasUnreadMessages ? 'font-semibold text-slate-700' : 'text-slate-400' }}">{{ $employee->latest_message_preview ?: ($employee->email ?: 'No messages yet') }}</p>
                                @if ($isSelfEmployeeCard)
                                    <span class="text-[11px] font-semibold text-slate-400">Current account</span>
                                @else
                                    <a href="{{ route('admin.adminCommunication', array_filter(['user' => $employee->id, 'tab_session' => request()->query('tab_session')])) }}#admin-chat-panel" data-admin-chat-connect class="relative inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                        @if ($employeeHasUnreadMessages)
                                            <span data-admin-unread-badge data-admin-connect-unread class="absolute -right-2 -top-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                                                {{ $employeeUnreadCount > 99 ? '99+' : $employeeUnreadCount }}
                                            </span>
                                        @endif
                                        <i class="fa-solid fa-comment"></i>Open
                                    </a>
                                @endif
                                  </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div id="admin-communication-empty" class="mt-6 hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                    No employee matched your search.
                </div>
            </section>

            <div id="admin-conversation-workspace" class="min-h-[38rem] xl:h-[calc(100vh-11rem)]">
            @if ($selectedParticipant)
                            @php
                                $participantName = trim(implode(' ', array_filter([$selectedParticipant->first_name ?? null, $selectedParticipant->middle_name ?? null, $selectedParticipant->last_name ?? null])));
                                $participantName = $participantName !== '' ? $participantName : (string) ($selectedParticipant->email ?? 'Employee');
                                $participantInitials = strtoupper(substr(trim((string) ($selectedParticipant->first_name ?? 'E')), 0, 1).substr(trim((string) ($selectedParticipant->last_name ?? '')), 0, 1));
                            @endphp
                            <div id="admin-chat-panel" class="flex h-full min-h-[38rem] w-full flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.12)]">
                            <div class="border-b border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="relative flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-slate-300 to-slate-500 text-sm font-black text-slate-950">{{ $participantInitials !== '' ? $participantInitials : 'EM' }}
                                            <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-400"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-xl font-bold text-slate-900">{{ $participantName }}</p>
                                            <p class="text-sm text-slate-500">Active now</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-violet-400">
                                        <button type="button" data-admin-chat-close class="inline-flex h-9 w-9 items-center justify-center rounded-full text-violet-500 transition hover:bg-violet-50 hover:text-violet-700" aria-label="Close chat">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="admin-message-thread" class="messenger-scroll min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50 px-4 py-4 md:px-6">
                                @if ($selectedConversation?->has_older_messages)
                                    <div class="flex justify-center">
                                        <a href="{{ request()->fullUrlWithQuery(['message_limit' => $selectedConversation->next_message_limit]) }}" data-admin-chat-connect class="rounded-full bg-white px-4 py-2 text-xs font-bold text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-100">Load older messages</a>
                                    </div>
                                @endif
                                @forelse ($messages as $message)
                                    @php
                                        $isOwnMessage = (int) ($message->sender_user_id ?? 0) === (int) auth()->id();
                                        $senderName = trim(implode(' ', array_filter([$message->sender->first_name ?? null, $message->sender->last_name ?? null])));
                                        $senderName = $senderName !== '' ? $senderName : ($isOwnMessage ? 'You' : $participantName);
                                    @endphp
                                    <div data-message-id="{{ (int) ($message->id ?? 0) }}" class="flex items-end gap-2 {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                                        @unless ($isOwnMessage)
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-slate-300 to-slate-500 text-[9px] font-bold text-slate-950">{{ $participantInitials !== '' ? $participantInitials : 'EM' }}</div>
                                        @endunless
                                        <div class="flex max-w-[78%] flex-col {{ $isOwnMessage ? 'items-end' : 'items-start' }}">
                                        <div class="w-fit max-w-full rounded-[1.45rem] px-4 py-2.5 shadow-sm {{ $isOwnMessage ? 'bg-gradient-to-r from-blue-600 to-violet-600 text-white' : 'border border-slate-200 bg-white text-slate-800' }}">
                                            @php
                                                $messageAttachments = collect($message->attachments ?? []);
                                                $messageImageCount = $messageAttachments->count() + (!empty($message->attachment_path) ? 1 : 0);
                                                $singleAttachment = $messageImageCount === 1
                                                    ? ($messageAttachments->first() ?? $message)
                                                    : null;
                                                $singleAttachmentMime = strtolower((string) ($singleAttachment->mime ?? $singleAttachment->attachment_mime ?? ''));
                                                $singleAttachmentName = strtolower((string) ($singleAttachment->name ?? $singleAttachment->attachment_name ?? ''));
                                                $singleAttachmentIsGif = $singleAttachment
                                                    && ($singleAttachmentMime === 'image/gif' || str_ends_with($singleAttachmentName, '.gif'));
                                            @endphp
                                            @if ($messageImageCount > 0)
                                                <div class="mb-2 grid {{ $messageImageCount === 1 ? ($singleAttachmentIsGif ? 'w-40 max-w-full grid-cols-1' : 'w-60 max-w-full grid-cols-1') : 'grid-cols-2' }} gap-1.5">
                                                    @foreach ($messageAttachments as $attachment)
                                                        @php
                                                            $isGif = strtolower((string) ($attachment->mime ?? '')) === 'image/gif'
                                                                || str_ends_with(strtolower((string) ($attachment->name ?? '')), '.gif');
                                                        @endphp
                                                        <a href="{{ route('admin.communication.attachment.view', array_filter(['attachment' => $attachment->id, 'tab_session' => request()->query('tab_session')])) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl bg-black/20">
                                                            <img loading="lazy" decoding="async" src="{{ route('admin.communication.attachment.view', array_filter(['attachment' => $attachment->id, 'tab_session' => request()->query('tab_session')])) }}" alt="{{ $attachment->name ?: 'Chat image' }}" class="{{ $isGif ? ($messageImageCount === 1 ? 'h-40 w-40 max-w-full object-contain' : 'h-24 w-full object-contain') : ($messageImageCount === 1 ? 'h-64 w-60 max-w-full object-contain' : 'h-32 w-full object-cover') }}">
                                                        </a>
                                                    @endforeach
                                                    @if (!empty($message->attachment_path))
                                                        @php
                                                            $legacyAttachmentIsGif = strtolower((string) ($message->attachment_mime ?? '')) === 'image/gif'
                                                                || str_ends_with(strtolower((string) ($message->attachment_name ?? '')), '.gif');
                                                        @endphp
                                                        <a href="{{ route('admin.communication.message.attachment', array_filter(['message' => $message->id, 'tab_session' => request()->query('tab_session')])) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl bg-black/20">
                                                            <img loading="lazy" decoding="async" src="{{ route('admin.communication.message.attachment', array_filter(['message' => $message->id, 'tab_session' => request()->query('tab_session')])) }}" alt="{{ $message->attachment_name ?: 'Chat image' }}" class="{{ $legacyAttachmentIsGif ? ($messageImageCount === 1 ? 'h-40 w-40 max-w-full object-contain' : 'h-24 w-full object-contain') : ($messageImageCount === 1 ? 'h-64 w-60 max-w-full object-contain' : 'h-32 w-full object-cover') }}">
                                                        </a>
                                                    @endif
                                                </div>
                                            @endif
                                            @if (trim((string) ($message->body ?? '')) !== '')
                                                <p class="whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>
                                            @endif
                                        </div>
                                        @if ($isOwnMessage && (int) ($message->id ?? 0) === $lastOwnMessageId)
                                            <p data-message-receipt class="mr-1 mt-1 text-right text-[10px] font-semibold text-slate-500" title="{{ $message->read_at ? 'Read '.$message->read_at->format('M j, Y g:i A') : 'Not read yet' }}">{{ $message->read_at ? 'Seen' : 'Sent' }}</p>
                                        @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="flex min-h-[16rem] items-center justify-center">
                                        <div class="max-w-sm text-center">
                                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-600"><i class="fa-solid fa-comments text-xl"></i></div>
                                            <h4 class="mt-4 text-lg font-black text-slate-900">Start the conversation.</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-500">Send the first message and the employee will be able to respond from their own communication page.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <form method="POST" action="{{ route('admin.communication.send') }}" data-admin-chat-message-form class="border-t border-slate-200 bg-white px-4 py-3">
                                @csrf
                                @if (request()->filled('tab_session'))
                                    <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                @endif
                                <input type="hidden" name="participant_user_id" value="{{ $selectedParticipant->id }}">
                                @if ($selectedConversation)<input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">@endif
                                <div data-chat-image-preview class="mb-3 hidden rounded-2xl bg-slate-100 p-2">
                                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                        <button type="button" data-chat-image-trigger class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-white text-xl text-slate-600 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50" aria-label="Add more images">
                                            <i class="fa-regular fa-square-plus"></i>
                                        </button>
                                        <div data-chat-image-preview-list class="flex items-center gap-2"></div>
                                    </div>
                                </div>
                                <div class="flex items-end gap-3">
                                    <button type="button" data-chat-image-trigger class="flex items-center pb-2 text-blue-500 transition hover:text-blue-400" aria-label="Choose an image">
                                        <i class="fa-regular fa-image"></i>
                                    </button>
                                    <input data-chat-image-input name="attachments[]" type="file" accept="image/jpeg,image/png,image/gif,image/webp" multiple class="hidden">
                                    <div class="relative flex-1 rounded-full bg-slate-100 py-2 pl-4 pr-2 ring-1 ring-slate-200">
                                        <div class="flex items-center gap-2">
                                            <textarea name="body" rows="1" maxlength="4000" class="min-w-0 flex-1 resize-none bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400" placeholder="Aa">{{ old('body') }}</textarea>
                                            <button type="button" data-chat-emoji-trigger class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-fuchsia-500 transition hover:bg-fuchsia-500/15 hover:text-fuchsia-400" aria-label="Choose an emoji" aria-expanded="false">
                                                <i class="fa-solid fa-face-smile text-lg"></i>
                                            </button>
                                        </div>
                                        <div data-chat-emoji-picker class="absolute bottom-full right-0 z-20 mb-2 hidden w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl">
                                            <div class="grid grid-cols-6 gap-1" aria-label="Emoji picker">
                                                @foreach (['😀','😂','😊','😍','🥰','😎','🤗','🤔','😢','😭','😅','😴','👍','👏','🙏','💪','❤️','🎉'] as $emoji)
                                                    <button type="button" data-chat-emoji="{{ $emoji }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-xl transition hover:bg-slate-100">{{ $emoji }}</button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center pb-2 text-blue-500">
                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-r from-blue-600 to-violet-600 text-white"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                                    </div>
                                </div>
                            </form>
                            </div>
            @else
                <div data-admin-chat-placeholder class="flex h-full min-h-[38rem] items-center justify-center rounded-[2rem] border border-dashed border-slate-300 bg-white/75 p-8 text-center shadow-sm">
                    <div class="max-w-sm">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-100 to-cyan-100 text-3xl text-emerald-700"><i class="fa-solid fa-comments"></i></div>
                        <h3 class="mt-6 text-2xl font-black text-slate-900">Your conversations</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-500">Choose an employee from the list to read previous messages or start a new conversation.</p>
                    </div>
                </div>
            @endif
            </div>
            </div>
        </div>
    </main>
</div>
<script>
(function(){
    const initCommunicationPageAnimation = () => {
        const page = document.getElementById('admin-communication-page');
        if (!page) return;

        const revealItems = Array.from(page.querySelectorAll('.communication-reveal'));
        if (!revealItems.length) return;

        if (!('IntersectionObserver' in window)) {
            revealItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        let lastScrollY = window.scrollY;
        let scrollDirection = 'down';

        window.addEventListener('scroll', () => {
            const currentScrollY = window.scrollY;
            scrollDirection = currentScrollY < lastScrollY ? 'up' : 'down';
            lastScrollY = currentScrollY;
        }, { passive: true });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.toggle('reveal-from-top', scrollDirection === 'up');
                    entry.target.classList.add('is-visible');
                    return;
                }

                entry.target.classList.remove('is-visible');
            });
        }, {
            root: null,
            threshold: 0.12,
            rootMargin: '-8% 0px -8% 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCommunicationPageAnimation, { once: true });
    } else {
        initCommunicationPageAnimation();
    }
})();

function initializeAdminChatPanel() {
    const thread = document.getElementById('admin-message-thread');
    if (thread) {
        thread.scrollTop = thread.scrollHeight;
    }
}

async function loadAdminChatPanel(url, historyMode = 'push') {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Chat request failed.');
    }

    const html = await response.text();
    const parsedDocument = new DOMParser().parseFromString(html, 'text/html');
    const incomingPanel = parsedDocument.getElementById('admin-chat-panel');
    if (!incomingPanel) {
        throw new Error('Chat panel was not returned.');
    }

    const panel = document.importNode(incomingPanel, true);
    const currentPanel = document.getElementById('admin-chat-panel');
    const workspace = document.getElementById('admin-conversation-workspace');
    if (currentPanel) {
        currentPanel.replaceWith(panel);
    } else if (workspace) {
        workspace.replaceChildren(panel);
    } else {
        throw new Error('Conversation workspace was not found.');
    }

    initializeAdminChatPanel();
    window.refreshAdminCommunicationUnreadCount?.();

    const nextUrl = new URL(url, window.location.href);
    nextUrl.hash = 'admin-chat-panel';
    if (historyMode === 'replace') {
        history.replaceState({}, '', nextUrl);
    } else {
        history.pushState({}, '', nextUrl);
    }
}

initializeAdminChatPanel();

const adminChatCsrfUrl = @json(route('csrf.token'));
async function refreshAdminChatCsrfToken(form) {
    const response = await fetch(adminChatCsrfUrl, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        cache: 'no-store',
    });
    const data = await response.json().catch(() => ({}));
    const token = response.ok && typeof data.token === 'string' ? data.token : '';
    if (token) {
        const tokenInput = form.querySelector('input[name="_token"]');
        if (tokenInput) tokenInput.value = token;
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    }
    return token;
}

document.addEventListener('click', async function (event) {
    const closeButton = event.target.closest('[data-admin-chat-close]');
    if (closeButton) {
        event.preventDefault();
        closeButton.closest('#admin-chat-panel')?.remove();

        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('user');
        cleanUrl.searchParams.delete('conversation');
        cleanUrl.searchParams.delete('reset_chat');
        cleanUrl.hash = '';
        window.location.assign(cleanUrl.toString());
        return;
    }

    const link = event.target.closest('a[data-admin-chat-connect]');
    if (!link || event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    event.preventDefault();
    if (link.dataset.loading === 'true') return;

    const originalContent = link.innerHTML;
    link.dataset.loading = 'true';
    link.setAttribute('aria-busy', 'true');
    link.classList.add('pointer-events-none', 'opacity-70');
    link.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>Opening...';
    let chatLoaded = false;

    try {
        await loadAdminChatPanel(link.href);
        chatLoaded = true;
    } catch (error) {
        window.location.assign(link.href);
        return;
    } finally {
        link.dataset.loading = 'false';
        link.removeAttribute('aria-busy');
        link.classList.remove('pointer-events-none', 'opacity-70');
        link.innerHTML = originalContent;
        if (chatLoaded) {
            const card = link.closest('[data-communication-directory-card]');
            const readCount = Number(card?.dataset.unreadCount || 0);
            card?.querySelectorAll('[data-admin-unread-badge]').forEach((badge) => badge.remove());
            if (card) {
                card.dataset.unread = 'false';
                card.dataset.unreadCount = '0';
            }

            const unreadCount = document.getElementById('admin-unread-count');
            const unreadFilter = document.getElementById('admin-unread-filter');
            if (unreadCount && readCount > 0) {
                const nextTotal = Math.max(Number(unreadCount.dataset.unreadTotal || 0) - readCount, 0);
                unreadCount.dataset.unreadTotal = String(nextTotal);
                unreadCount.textContent = nextTotal > 99 ? '99+' : String(nextTotal);
                window.updateAdminCommunicationUnreadCount?.(nextTotal);
                if (nextTotal === 0 && unreadFilter) {
                    unreadFilter.disabled = true;
                    unreadFilter.classList.add('cursor-not-allowed', 'opacity-60');
                }
            }

            document.getElementById('admin-communication-search')?.dispatchEvent(new Event('input'));
        }
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('form[data-admin-chat-message-form]');
    if (!form) return;

    event.preventDefault();

    const textarea = form.querySelector('textarea[name="body"]');
    const attachmentInput = form.querySelector('[data-chat-image-input]');
    const submitButton = form.querySelector('button[type="submit"]');
    const messageBody = (textarea?.value || '').trim();
    const hasAttachment = Boolean(attachmentInput?.files?.length);
    if (!messageBody && !hasAttachment) {
        textarea?.focus();
        return;
    }

    if (form.dataset.sending === 'true') return;
    form.dataset.sending = 'true';

    const originalButtonContent = submitButton?.innerHTML || '';
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i>';
        submitButton.classList.add('cursor-not-allowed', 'opacity-70');
    }

    form.querySelector('[data-admin-chat-send-error]')?.remove();
    let messageSaved = false;

    try {
        const freshToken = await refreshAdminChatCsrfToken(form);
        const formData = new FormData(form);
        if (freshToken) formData.set('_token', freshToken);
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(freshToken ? { 'X-CSRF-TOKEN': freshToken } : {}),
            },
            credentials: 'same-origin',
            body: formData,
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const firstError = data.errors && typeof data.errors === 'object'
                ? Object.values(data.errors).flat().find(Boolean)
                : '';
            throw new Error(firstError || data.message || 'The message could not be sent.');
        }

        if (!data.chat_url) {
            throw new Error('The message was sent, but the chat could not be refreshed.');
        }

        messageSaved = true;
        await loadAdminChatPanel(data.chat_url, 'replace');
    } catch (error) {
        const errorMessage = messageSaved
            ? 'Message sent. Reopen the chat to refresh the conversation.'
            : (error.message || 'The message could not be sent. Please try again.');
        const errorNotice = document.createElement('p');
        errorNotice.dataset.adminChatSendError = 'true';
        errorNotice.className = 'mb-2 rounded-lg bg-rose-500/15 px-3 py-2 text-xs font-medium text-rose-300';
        errorNotice.textContent = errorMessage;
        form.prepend(errorNotice);
    } finally {
        form.dataset.sending = 'false';
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonContent;
            submitButton.classList.remove('cursor-not-allowed', 'opacity-70');
        }
    }
});

(function(){
    const searchInput = document.getElementById('admin-communication-search');
    const cards = Array.from(document.querySelectorAll('[data-communication-directory-card]'));
    const emptyMessage = document.getElementById('admin-communication-empty');
    const directoryGrid = document.getElementById('admin-communication-directory-grid');
    const allFilter = document.getElementById('admin-all-filter');
    const unreadFilter = document.getElementById('admin-unread-filter');
    if (!searchInput || !cards.length || !directoryGrid) return;
    let unreadOnly = false;

    const applyDirectorySearch = () => {
        const query = (searchInput.value || '').toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach((card) => {
            const searchableText = [
                card.dataset.name || '',
                card.dataset.email || '',
                card.dataset.position || '',
                card.dataset.department || '',
            ].join(' ');

            const matchesSearch = query === '' || searchableText.includes(query);
            const matchesUnread = !unreadOnly || card.dataset.unread === 'true';
            const matches = matchesSearch && matchesUnread;
            card.classList.toggle('hidden', !matches);
            if (matches) visibleCount += 1;
        });

        const hasVisibleCards = visibleCount > 0;
        directoryGrid.classList.toggle('hidden', !hasVisibleCards);
        if (emptyMessage) {
            emptyMessage.classList.toggle('hidden', hasVisibleCards);
            emptyMessage.textContent = unreadOnly
                ? 'No employees with unread messages matched your search.'
                : 'No employee matched your search.';
        }
    };

    searchInput.addEventListener('input', applyDirectorySearch);
    const updateFilterButtons = () => {
        allFilter?.setAttribute('aria-pressed', unreadOnly ? 'false' : 'true');
        unreadFilter?.setAttribute('aria-pressed', unreadOnly ? 'true' : 'false');

        allFilter?.classList.toggle('border-slate-900', !unreadOnly);
        allFilter?.classList.toggle('bg-slate-900', !unreadOnly);
        allFilter?.classList.toggle('text-white', !unreadOnly);
        allFilter?.classList.toggle('shadow-sm', !unreadOnly);
        allFilter?.classList.toggle('border-slate-200', unreadOnly);
        allFilter?.classList.toggle('bg-slate-50', unreadOnly);
        allFilter?.classList.toggle('text-slate-600', unreadOnly);

        unreadFilter.classList.toggle('border-rose-600', unreadOnly);
        unreadFilter.classList.toggle('bg-rose-600', unreadOnly);
        unreadFilter.classList.toggle('text-white', unreadOnly);
        unreadFilter.classList.toggle('shadow-md', unreadOnly);
        unreadFilter.classList.toggle('border-rose-200', !unreadOnly);
        unreadFilter.classList.toggle('bg-rose-50', !unreadOnly);
        unreadFilter.classList.toggle('text-rose-700', !unreadOnly);
    };

    allFilter?.addEventListener('click', () => {
        unreadOnly = false;
        updateFilterButtons();
        applyDirectorySearch();
    });

    unreadFilter?.addEventListener('click', () => {
        unreadOnly = true;
        updateFilterButtons();
        applyDirectorySearch();
    });
    updateFilterButtons();
    applyDirectorySearch();
})();

(function () {
    let pollInFlight = false;

    const synchronizeUnreadButton = (incomingDocument) => {
        const currentCount = document.getElementById('admin-unread-count');
        const incomingCount = incomingDocument.getElementById('admin-unread-count');
        const unreadFilter = document.getElementById('admin-unread-filter');
        if (!currentCount || !incomingCount || !unreadFilter) return;

        const previousTotal = Number(currentCount.dataset.unreadTotal || 0);
        const nextTotal = Number(incomingCount.dataset.unreadTotal || 0);
        currentCount.dataset.unreadTotal = String(nextTotal);
        currentCount.textContent = nextTotal > 99 ? '99+' : String(nextTotal);
        window.updateAdminCommunicationUnreadCount?.(nextTotal);

        unreadFilter.disabled = nextTotal === 0;
        unreadFilter.classList.toggle('cursor-not-allowed', nextTotal === 0);
        unreadFilter.classList.toggle('opacity-60', nextTotal === 0);
        unreadFilter.classList.toggle('hover:border-rose-300', nextTotal > 0);
        unreadFilter.classList.toggle('hover:bg-rose-100', nextTotal > 0);

        if (nextTotal > previousTotal) {
            unreadFilter.classList.add('ring-4', 'ring-rose-200');
            window.setTimeout(() => unreadFilter.classList.remove('ring-4', 'ring-rose-200'), 900);
        }
    };

    const synchronizeDirectoryCards = (incomingDocument) => {
        document.querySelectorAll('[data-communication-directory-card]').forEach((card) => {
            const employeeId = card.dataset.employeeId || '';
            if (!employeeId) return;

            const incomingCard = incomingDocument.querySelector(
                `[data-communication-directory-card][data-employee-id="${employeeId}"]`
            );
            if (!incomingCard) return;

            card.dataset.unread = incomingCard.dataset.unread || 'false';
            card.dataset.unreadCount = incomingCard.dataset.unreadCount || '0';

            const currentNameRow = card.querySelector('[data-admin-employee-name-row]');
            const incomingNameBadge = incomingCard.querySelector('[data-admin-name-unread]');
            currentNameRow?.querySelector('[data-admin-name-unread]')?.remove();
            if (currentNameRow && incomingNameBadge) {
                currentNameRow.appendChild(document.importNode(incomingNameBadge, true));
            }

            const currentConnect = card.querySelector('[data-admin-chat-connect]');
            const incomingConnectBadge = incomingCard.querySelector('[data-admin-connect-unread]');
            currentConnect?.querySelector('[data-admin-connect-unread]')?.remove();
            if (currentConnect && incomingConnectBadge) {
                currentConnect.prepend(document.importNode(incomingConnectBadge, true));
            }

            const currentPreview = card.querySelector('[data-admin-message-preview]');
            const incomingPreview = incomingCard.querySelector('[data-admin-message-preview]');
            if (currentPreview && incomingPreview) {
                currentPreview.textContent = incomingPreview.textContent;
                currentPreview.className = incomingPreview.className;
            }
        });

        document.getElementById('admin-communication-search')?.dispatchEvent(new Event('input'));
    };

    const synchronizeOpenThread = (incomingDocument) => {
        const currentThread = document.getElementById('admin-message-thread');
        const incomingThread = incomingDocument.getElementById('admin-message-thread');
        if (!currentThread || !incomingThread || currentThread.innerHTML === incomingThread.innerHTML) return;

        const wasNearBottom = currentThread.scrollHeight - currentThread.scrollTop - currentThread.clientHeight < 80;
        currentThread.innerHTML = incomingThread.innerHTML;
        if (wasNearBottom) {
            currentThread.scrollTop = currentThread.scrollHeight;
        }
    };

    const pollAdminCommunication = async () => {
        if (pollInFlight || document.hidden) return;
        if (document.querySelector('[data-admin-chat-connect][data-loading="true"]')) return;
        if (document.querySelector('[data-admin-chat-message-form][data-sending="true"]')) return;

        pollInFlight = true;
        try {
            const response = await fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;

            const html = await response.text();
            const incomingDocument = new DOMParser().parseFromString(html, 'text/html');
            synchronizeUnreadButton(incomingDocument);
            synchronizeDirectoryCards(incomingDocument);
            synchronizeOpenThread(incomingDocument);
            window.refreshAdminCommunicationUnreadCount?.();
        } catch (error) {
            // Keep the current page usable and try again on the next interval.
        } finally {
            pollInFlight = false;
        }
    };

    window.setInterval(pollAdminCommunication, 4000);
    const visibleUnreadCount = document.getElementById('admin-unread-count');
    if (visibleUnreadCount) {
        window.updateAdminCommunicationUnreadCount?.(
            Number(visibleUnreadCount.dataset.unreadTotal || visibleUnreadCount.textContent || 0)
        );
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) pollAdminCommunication();
    });
})();
</script>
@include('components.chatImageUploadScript')
@include('components.chatEmojiPickerScript')
</body>
</html>
