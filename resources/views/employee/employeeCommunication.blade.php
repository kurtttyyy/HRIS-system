<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Directory | Employee Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body{font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif;transition:margin-left .3s ease}
        main{transition:margin-left .3s ease}
        aside:not(:hover)~main{margin-left:4rem}
        aside:hover~main{margin-left:14rem}
        .messenger-shell{background:linear-gradient(180deg,#171717 0%,#202020 100%)}
        .messenger-sidebar{background:linear-gradient(180deg,#161616 0%,#1c1c1c 100%)}
        .messenger-thread{background:radial-gradient(circle at top right, rgba(88,28,135,.25), transparent 24%),linear-gradient(180deg,#202020 0%,#181818 100%)}
        .messenger-scroll::-webkit-scrollbar{width:8px}
        .messenger-scroll::-webkit-scrollbar-thumb{background:#4b5563;border-radius:999px}
        .messenger-scroll::-webkit-scrollbar-track{background:transparent}
        #employee-communication-page .employee-communication-reveal{opacity:0;transform:translateY(24px);transition:opacity .7s ease,transform .7s cubic-bezier(.22,1,.36,1);transition-delay:var(--employee-communication-delay,0ms)}
        #employee-communication-page .employee-communication-reveal.is-visible{opacity:1;transform:translateY(0)}
        #employee-communication-page .employee-communication-card-motion{transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease}
        #employee-communication-page .employee-communication-card-motion:hover{transform:translateY(-4px);box-shadow:0 18px 36px rgba(15,23,42,.12)}
        #employee-communication-page .employee-communication-icon-pop{opacity:0;transform:scale(.86) rotate(-4deg);transition:opacity .55s ease,transform .55s cubic-bezier(.22,1,.36,1);transition-delay:var(--employee-communication-delay,120ms)}
        #employee-communication-page .is-visible .employee-communication-icon-pop,#employee-communication-page .employee-communication-icon-pop.is-visible{opacity:1;transform:scale(1) rotate(0deg)}
        #chat-panel.employee-communication-chat-pop{animation:employee-communication-chat-pop .45s cubic-bezier(.22,1,.36,1) both}
        @keyframes employee-communication-chat-pop{from{opacity:0;transform:translateY(18px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
        @media (prefers-reduced-motion:reduce){#employee-communication-page .employee-communication-reveal,#employee-communication-page .employee-communication-icon-pop,#employee-communication-page .employee-communication-card-motion{opacity:1;transform:none;transition:none}#chat-panel.employee-communication-chat-pop{animation:none}}
    </style>
</head>
<body class="bg-[radial-gradient(circle_at_top,_#f0fdf4,_#eff6ff_35%,_#f8fafc_75%)] text-slate-900">
@php
    $directoryMembers = collect($admins ?? []);
    $conversationSummaries = collect($conversationSummaries ?? []);
    $selectedParticipant = $selectedParticipant ?? null;
    $selectedConversation = $selectedConversation ?? null;
    $messages = collect(optional($selectedConversation)->messages ?? []);
    $availableCount = $directoryMembers->filter(fn ($member) => in_array(strtolower(trim((string) ($member->status ?? ''))), ['approved', 'available'], true))->count();
@endphp
<div class="flex min-h-screen">
    @include('components.employeeSideBar')
    <main class="flex-1 ml-16 transition-all duration-300">
        @include('components.employeeHeader.communicationHeader')
        <div id="employee-communication-page" class="px-4 pb-8 pt-6 md:px-8 md:pb-10">
            @if (session('success'))
                <div class="employee-communication-reveal mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" style="--employee-communication-delay: 0ms;">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="employee-communication-reveal mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700" style="--employee-communication-delay: 0ms;">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="employee-communication-reveal mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" style="--employee-communication-delay: 0ms;">{{ $errors->first() }}</div>
            @endif

            <section class="employee-communication-reveal rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-[0_18px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl md:p-6" style="--employee-communication-delay: 0ms;">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Directory Controls</p>
                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Find people by name, role, or status.</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Use the search box and quick filters to narrow the list without leaving the page.</p>
                    </div>
                    <div class="flex flex-col gap-3 xl:min-w-[560px] xl:max-w-[640px] xl:flex-row">
                        <label class="employee-communication-card-motion group flex flex-1 items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <i class="fa fa-search text-slate-400"></i>
                            <input id="directory-search" type="text" placeholder="Search by employee name, role, or account type" class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400">
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="directory-filter rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white" data-filter="all">All <span class="ml-1 text-white/70">{{ $directoryMembers->count() }}</span></button>
                            <button type="button" class="directory-filter rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700" data-filter="available">Available <span class="ml-1 text-emerald-500">{{ $availableCount }}</span></button>
                            <button type="button" class="directory-filter rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-600" data-filter="other">Other</button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="employee-communication-reveal mt-6" style="--employee-communication-delay: 120ms;">
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">Directory Cards</p>
                        <h3 class="mt-2 text-2xl font-black tracking-tight text-slate-900">Meet the people behind the system.</h3>
                    </div>
                    <div class="employee-communication-card-motion inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-sm text-slate-600 shadow-sm">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span id="directory-results-count">{{ $directoryMembers->count() }}</span>
                        <span>visible member<span id="directory-results-plural">{{ $directoryMembers->count() === 1 ? '' : 's' }}</span></span>
                    </div>
                </div>
                <div id="directory-grid" class="grid grid-cols-1 gap-6 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach($admins as $admin)
                        @php
                            $fullName = trim(implode(' ', array_filter([$admin->first_name ?? '', $admin->middle_name ?? '', $admin->last_name ?? ''])));
                            $initials = strtoupper(substr((string) ($admin->first_name ?? ''), 0, 1) . substr((string) ($admin->last_name ?? ''), 0, 1));
                            $displayStatus = trim((string) ($admin->status ?? ''));
                            if (strtolower($displayStatus) === 'approved') { $displayStatus = 'Available'; }
                            $isAvailable = strtolower($displayStatus) === 'available';
                            $jobRole = trim((string) ($admin->job_role ?? 'Administrator'));
                            $role = trim((string) ($admin->role ?? 'Admin'));
                            $email = trim((string) ($admin->email ?? ''));
                            $adminUnreadCount = (int) ($admin->unread_message_count ?? 0);
                            $adminHasUnreadMessages = (bool) ($admin->has_unread_messages ?? false);
                        @endphp
                        <article class="directory-card employee-communication-card-motion employee-communication-reveal rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-[0_18px_60px_rgba(15,23,42,0.08)]" style="--employee-communication-delay: {{ 160 + (($loop->index % 6) * 40) }}ms;" data-admin-id="{{ (int) ($admin->id ?? 0) }}" data-name="{{ strtolower($fullName) }}" data-role="{{ strtolower($jobRole.' '.$role) }}" data-status="{{ $isAvailable ? 'available' : 'other' }}" data-unread="{{ $adminHasUnreadMessages ? 'true' : 'false' }}" data-unread-count="{{ $adminUnreadCount }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="employee-communication-icon-pop flex h-20 w-20 items-center justify-center rounded-[1.6rem] bg-gradient-to-br from-emerald-500 via-teal-500 to-sky-500 text-2xl font-black text-white">{{ $initials !== '' ? $initials : 'AD' }}</div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $role !== '' ? $role : 'Admin' }}</p>
                                        <div data-employee-admin-name-row class="mt-1 flex flex-wrap items-center gap-2">
                                            <h4 class="text-xl font-black leading-tight text-slate-900">{{ $fullName !== '' ? $fullName : 'Admin User' }}</h4>
                                            @if ($adminHasUnreadMessages)
                                                 <span data-unread-badge data-employee-name-unread class="inline-flex items-center rounded-full bg-rose-500 px-2.5 py-1 text-[11px] font-bold text-white">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }} unread</span>
                                             @endif
                                        </div>
                                        <p class="mt-1 text-sm font-medium text-slate-500">{{ $jobRole }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $isAvailable ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    <span class="h-2 w-2 rounded-full {{ $isAvailable ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $displayStatus !== '' ? $displayStatus : 'No Status' }}
                                </span>
                            </div>
                            <div class="employee-communication-card-motion mt-5 rounded-[1.5rem] border border-slate-200 bg-white/80 px-4 py-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Contact</p>
                                <p class="mt-1 truncate text-sm text-slate-600">{{ $email !== '' ? $email : 'Email not available' }}</p>
                            </div>
                            <div class="mt-6 flex flex-wrap gap-3">
                                <a href="mailto:{{ $email }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white"><i class="fa fa-user"></i>View Profile</a>
                                 <a href="{{ route('employee.employeeCommunication', array_filter(['user' => $admin->id, 'tab_session' => request()->query('tab_session')])) }}#chat-panel" data-chat-connect class="relative inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                     @if ($adminHasUnreadMessages)
                                         <span data-unread-badge data-employee-connect-unread class="absolute -right-2 -top-2 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
                                     @endif
                                    <i class="fa fa-comment"></i>Connect
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @if ($selectedParticipant)
                            @php
                                $participantName = trim(implode(' ', array_filter([$selectedParticipant->first_name ?? null, $selectedParticipant->middle_name ?? null, $selectedParticipant->last_name ?? null])));
                                $participantName = $participantName !== '' ? $participantName : (string) ($selectedParticipant->email ?? 'Admin');
                                $participantInitials = strtoupper(substr(trim((string) ($selectedParticipant->first_name ?? 'A')), 0, 1).substr(trim((string) ($selectedParticipant->last_name ?? '')), 0, 1));
                            @endphp
                            <div id="chat-panel" class="employee-communication-chat-pop fixed bottom-5 right-5 z-50 w-[370px] max-w-[calc(100vw-1.5rem)] overflow-hidden rounded-t-2xl rounded-b-[1.35rem] border border-slate-800 bg-[#1f1f1f] shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                            <div class="border-b border-slate-700 bg-[#242424] px-4 py-3">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="relative flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-slate-300 to-slate-500 text-sm font-black text-slate-950">{{ $participantInitials !== '' ? $participantInitials : 'AD' }}
                                            <span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-[#242424] bg-emerald-400"></span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-xl font-bold text-white">{{ $participantName }}</p>
                                            <p class="text-sm text-slate-400">Active now</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 text-violet-400">
                                        <button id="chat-panel-close" type="button" class="text-violet-400" aria-label="Close chat"><i class="fa-solid fa-xmark"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div id="message-thread" class="messenger-scroll h-[340px] space-y-4 overflow-y-auto bg-[#1f1f1f] px-4 py-4">
                                @forelse ($messages as $message)
                                    @php
                                        $isOwnMessage = (int) ($message->sender_user_id ?? 0) === (int) auth()->id();
                                        $senderName = trim(implode(' ', array_filter([$message->sender->first_name ?? null, $message->sender->last_name ?? null])));
                                        $senderName = $senderName !== '' ? $senderName : ($isOwnMessage ? 'You' : $participantName);
                                    @endphp
                                    <div data-message-id="{{ (int) ($message->id ?? 0) }}" class="flex items-end gap-2 {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                                        @unless ($isOwnMessage)
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-slate-300 to-slate-500 text-[9px] font-bold text-slate-950">{{ $participantInitials !== '' ? $participantInitials : 'AD' }}</div>
                                         @endunless
                                         <div class="max-w-[78%] rounded-[1.45rem] px-4 py-2.5 shadow-sm {{ $isOwnMessage ? 'bg-gradient-to-r from-violet-600 to-fuchsia-500 text-white' : 'bg-[#303030] text-slate-100' }}">
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
                                                         <a href="{{ route('employee.communication.attachment.view', array_filter(['attachment' => $attachment->id, 'preview' => 1, 'tab_session' => request()->query('tab_session')])) }}" class="block overflow-hidden rounded-xl bg-black/20">
                                                             <img src="{{ route('employee.communication.attachment.view', array_filter(['attachment' => $attachment->id, 'tab_session' => request()->query('tab_session')])) }}" alt="{{ $attachment->name ?: 'Chat image' }}" class="{{ $isGif ? ($messageImageCount === 1 ? 'h-40 w-40 max-w-full object-contain' : 'h-24 w-full object-contain') : ($messageImageCount === 1 ? 'h-64 w-60 max-w-full object-contain' : 'h-32 w-full object-cover') }}">
                                                         </a>
                                                     @endforeach
                                                     @if (!empty($message->attachment_path))
                                                         @php
                                                             $legacyAttachmentIsGif = strtolower((string) ($message->attachment_mime ?? '')) === 'image/gif'
                                                                 || str_ends_with(strtolower((string) ($message->attachment_name ?? '')), '.gif');
                                                         @endphp
                                                         <a href="{{ route('employee.communication.message.attachment', array_filter(['message' => $message->id, 'preview' => 1, 'tab_session' => request()->query('tab_session')])) }}" class="block overflow-hidden rounded-xl bg-black/20">
                                                             <img src="{{ route('employee.communication.message.attachment', array_filter(['message' => $message->id, 'tab_session' => request()->query('tab_session')])) }}" alt="{{ $message->attachment_name ?: 'Chat image' }}" class="{{ $legacyAttachmentIsGif ? ($messageImageCount === 1 ? 'h-40 w-40 max-w-full object-contain' : 'h-24 w-full object-contain') : ($messageImageCount === 1 ? 'h-64 w-60 max-w-full object-contain' : 'h-32 w-full object-cover') }}">
                                                         </a>
                                                     @endif
                                                 </div>
                                             @endif
                                             @if (trim((string) ($message->body ?? '')) !== '')
                                                 <p class="whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>
                                             @endif
                                         </div>
                                    </div>
                                @empty
                                    <div data-chat-empty-state class="flex min-h-[16rem] items-center justify-center">
                                        <div class="max-w-sm text-center">
                                            <div class="employee-communication-icon-pop is-visible mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 text-violet-400"><i class="fa-solid fa-comment-dots text-xl"></i></div>
                                            <h4 class="mt-4 text-lg font-black text-white">Start the conversation.</h4>
                                            <p class="mt-2 text-sm leading-6 text-slate-400">Your first message creates the chat thread and lets the admin reply from their own inbox.</p>
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                             <form method="POST" action="{{ route('employee.communication.send') }}" data-chat-message-form class="border-t border-slate-700 bg-[#1f1f1f] px-4 py-3">
                                @csrf
                                @if (request()->filled('tab_session'))
                                    <input type="hidden" name="tab_session" value="{{ request()->query('tab_session') }}">
                                @endif
                                 <input type="hidden" name="participant_user_id" value="{{ $selectedParticipant->id }}">
                                 @if ($selectedConversation)<input type="hidden" name="conversation_id" value="{{ $selectedConversation->id }}">@endif
                                 <div data-chat-image-preview class="mb-3 hidden rounded-2xl bg-[#3a3a3a] p-2">
                                     <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                         <button type="button" data-chat-image-trigger class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#202020] text-xl text-white transition hover:bg-[#171717]" aria-label="Add more images">
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
                                     <div class="relative flex-1 rounded-full bg-[#3a3a3a] py-2 pl-4 pr-2">
                                         <div class="flex items-center gap-2">
                                             <textarea name="body" rows="1" maxlength="4000" class="min-w-0 flex-1 resize-none bg-transparent text-sm text-white outline-none placeholder:text-slate-500" placeholder="Aa">{{ old('body') }}</textarea>
                                             <button type="button" data-chat-emoji-trigger class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-fuchsia-500 transition hover:bg-fuchsia-500/15 hover:text-fuchsia-400" aria-label="Choose an emoji" aria-expanded="false">
                                                 <i class="fa-solid fa-face-smile text-lg"></i>
                                             </button>
                                         </div>
                                         <div data-chat-emoji-picker class="absolute bottom-full right-0 z-20 mb-2 hidden w-56 rounded-2xl border border-slate-600 bg-[#292929] p-2 shadow-2xl">
                                             <div class="grid grid-cols-6 gap-1" aria-label="Emoji picker">
                                                 @foreach (['😀','😂','😊','😍','🥰','😎','🤗','🤔','😢','😭','😅','😴','👍','👏','🙏','💪','❤️','🎉'] as $emoji)
                                                     <button type="button" data-chat-emoji="{{ $emoji }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-xl transition hover:bg-slate-600">{{ $emoji }}</button>
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
            @endif
        </div>
    </main>
</div>
<script>
const initEmployeeCommunicationAnimation=()=>{const page=document.getElementById('employee-communication-page');if(!page)return;const animatedItems=page.querySelectorAll('.employee-communication-reveal');if(!('IntersectionObserver'in window)){animatedItems.forEach((item)=>item.classList.add('is-visible'));return}const observer=new IntersectionObserver((entries)=>{entries.forEach((entry)=>{if(entry.isIntersecting){entry.target.classList.add('is-visible');observer.unobserve(entry.target)}})},{threshold:.14,rootMargin:'0px 0px -40px 0px'});animatedItems.forEach((item)=>observer.observe(item))};if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',initEmployeeCommunicationAnimation,{once:true})}else{initEmployeeCommunicationAnimation()}
const sidebar=document.querySelector('aside');const main=document.querySelector('main');if(sidebar&&main){sidebar.addEventListener('mouseenter',function(){main.classList.remove('ml-16');main.classList.add('ml-56')});sidebar.addEventListener('mouseleave',function(){main.classList.remove('ml-56');main.classList.add('ml-16')})}
const searchInput=document.getElementById('directory-search');const filterButtons=Array.from(document.querySelectorAll('.directory-filter'));const directoryCards=Array.from(document.querySelectorAll('.directory-card'));const resultsCount=document.getElementById('directory-results-count');const resultsPlural=document.getElementById('directory-results-plural');let activeFilter='all';
function applyDirectoryFilters(){const query=(searchInput?.value||'').trim().toLowerCase();let visibleCount=0;directoryCards.forEach((card)=>{const name=card.dataset.name||'';const role=card.dataset.role||'';const status=card.dataset.status||'';const matchesQuery=query===''||name.includes(query)||role.includes(query);const matchesStatus=activeFilter==='all'||status===activeFilter;const isVisible=matchesQuery&&matchesStatus;card.classList.toggle('hidden',!isVisible);if(isVisible){visibleCount+=1}});if(resultsCount){resultsCount.textContent=String(visibleCount)}if(resultsPlural){resultsPlural.textContent=visibleCount===1?'':'s'}}
filterButtons.forEach((button)=>{button.addEventListener('click',function(){activeFilter=button.dataset.filter||'all';filterButtons.forEach((item)=>{item.classList.remove('bg-slate-900','text-white','bg-emerald-600');item.classList.add('bg-slate-100','text-slate-600')});if(activeFilter==='available'){button.classList.remove('bg-slate-100','text-slate-600','bg-emerald-50','text-emerald-700');button.classList.add('bg-emerald-600','text-white')}else{button.classList.remove('bg-slate-100','text-slate-600');button.classList.add('bg-slate-900','text-white')}filterButtons.forEach((item)=>{if(item!==button&&item.dataset.filter==='available'){item.classList.remove('bg-emerald-600','text-white');item.classList.add('bg-emerald-50','text-emerald-700')}});applyDirectoryFilters()})});if(searchInput){searchInput.addEventListener('input',applyDirectoryFilters)}applyDirectoryFilters();
 function initializeEmployeeChatPanel() {
     const thread = document.getElementById('message-thread');
     if (thread) {
         thread.scrollTop = thread.scrollHeight;
     }

     const closeBtn = document.getElementById('chat-panel-close');
     const panel = document.getElementById('chat-panel');
     if (closeBtn && panel) {
         closeBtn.addEventListener('click', function () {
             panel.remove();

             const cleanUrl = new URL(window.location.href);
             cleanUrl.searchParams.delete('user');
             cleanUrl.searchParams.delete('conversation');
             cleanUrl.searchParams.delete('reset_chat');
             cleanUrl.hash = '';
             history.replaceState({}, '', cleanUrl);
         }, { once: true });
     }
 }

 async function loadEmployeeChatPanel(url, historyMode = 'push') {
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
     const incomingPanel = parsedDocument.getElementById('chat-panel');
     if (!incomingPanel) {
         throw new Error('Chat panel was not returned.');
     }

     const panel = document.importNode(incomingPanel, true);
     const currentPanel = document.getElementById('chat-panel');
     if (currentPanel) {
         currentPanel.replaceWith(panel);
     } else {
         document.body.appendChild(panel);
     }

     initializeEmployeeChatPanel();

     const nextUrl = new URL(url, window.location.href);
     nextUrl.hash = 'chat-panel';
     if (historyMode === 'replace') {
         history.replaceState({}, '', nextUrl);
     } else {
         history.pushState({}, '', nextUrl);
     }
 }

 function appendSentEmployeeMessage(message) {
     const thread = document.getElementById('message-thread');
     if (!thread || !message?.id) return;
     if (thread.querySelector(`[data-message-id="${message.id}"]`)) return;

     thread.querySelector('[data-chat-empty-state]')?.remove();

     const row = document.createElement('div');
     row.dataset.messageId = String(message.id);
     row.className = 'flex items-end gap-2 justify-end';

     const bubble = document.createElement('div');
     bubble.className = 'max-w-[78%] rounded-[1.45rem] bg-gradient-to-r from-violet-600 to-fuchsia-500 px-4 py-2.5 text-white shadow-sm';

     const attachments = Array.isArray(message.attachments) ? message.attachments : [];
     if (attachments.length > 0) {
         const isGifAttachment = (attachment) => {
             const mime = String(attachment?.mime || '').toLowerCase();
             const name = String(attachment?.name || '').toLowerCase();
             return mime === 'image/gif' || name.endsWith('.gif');
         };
         const singleAttachmentIsGif = attachments.length === 1 && isGifAttachment(attachments[0]);
         const imageGrid = document.createElement('div');
         imageGrid.className = `mb-2 grid gap-1.5 ${attachments.length === 1 ? (singleAttachmentIsGif ? 'w-40 max-w-full grid-cols-1' : 'w-60 max-w-full grid-cols-1') : 'grid-cols-2'}`;

         attachments.forEach((attachment) => {
             const imageLink = document.createElement('a');
             imageLink.href = attachment.preview_url || attachment.url;
             imageLink.className = 'block overflow-hidden rounded-xl bg-black/20';

             const image = document.createElement('img');
             image.src = attachment.url;
             image.alt = attachment.name || 'Chat image';
             image.className = isGifAttachment(attachment)
                 ? (attachments.length === 1 ? 'h-40 w-40 max-w-full object-contain' : 'h-24 w-full object-contain')
                 : (attachments.length === 1 ? 'h-64 w-60 max-w-full object-contain' : 'h-32 w-full object-cover');
             imageLink.appendChild(image);
             imageGrid.appendChild(imageLink);
         });

         bubble.appendChild(imageGrid);
     }

     if ((message.body || '').trim() !== '') {
         const text = document.createElement('p');
         text.className = 'whitespace-pre-line text-sm leading-6';
         text.textContent = message.body;
         bubble.appendChild(text);
     }

     row.appendChild(bubble);
     thread.appendChild(row);
     thread.scrollTop = thread.scrollHeight;
 }

 initializeEmployeeChatPanel();

 const employeeChatCsrfUrl = @json(route('csrf.token'));
 async function refreshEmployeeChatCsrfToken(form) {
     const response = await fetch(employeeChatCsrfUrl, {
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
     const link = event.target.closest('a[data-chat-connect]');
     if (!link || event.defaultPrevented || event.button !== 0) return;
     if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

     event.preventDefault();
     if (link.dataset.loading === 'true') return;

     const originalContent = link.innerHTML;
     link.dataset.loading = 'true';
     link.setAttribute('aria-busy', 'true');
     link.classList.add('pointer-events-none', 'opacity-70');
     link.innerHTML = '<i class="fa fa-spinner fa-spin"></i>Opening...';
     let chatLoaded = false;

     try {
         await loadEmployeeChatPanel(link.href);
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
             const card = link.closest('.directory-card');
             card?.querySelectorAll('[data-unread-badge]').forEach((badge) => badge.remove());
             if (card) {
                 card.dataset.unread = 'false';
                 card.dataset.unreadCount = '0';
             }
         }
     }
 });

 document.addEventListener('submit', async function (event) {
     const form = event.target.closest('form[data-chat-message-form]');
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

     form.querySelector('[data-chat-send-error]')?.remove();

     try {
         const freshToken = await refreshEmployeeChatCsrfToken(form);
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

         if (!data.chat_url || !data.sent_message) {
             throw new Error('The message was sent, but its confirmation was incomplete.');
         }

         appendSentEmployeeMessage(data.sent_message);
         if (textarea) {
             textarea.value = '';
         }
         window.resetChatImagePreview?.(form);

         let conversationInput = form.querySelector('input[name="conversation_id"]');
         if (!conversationInput) {
             conversationInput = document.createElement('input');
             conversationInput.type = 'hidden';
             conversationInput.name = 'conversation_id';
             form.appendChild(conversationInput);
         }
         conversationInput.value = String(data.conversation_id || '');

         const nextUrl = new URL(data.chat_url, window.location.href);
         nextUrl.hash = 'chat-panel';
         history.replaceState({}, '', nextUrl);
     } catch (error) {
         const errorNotice = document.createElement('p');
         errorNotice.dataset.chatSendError = 'true';
         errorNotice.className = 'mb-2 rounded-lg bg-rose-500/15 px-3 py-2 text-xs font-medium text-rose-300';
         errorNotice.textContent = error.message || 'The message could not be sent. Please try again.';
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

 (function () {
     let pollInFlight = false;

     const synchronizeEmployeeSidebar = (incomingDocument) => {
         const currentNav = document.querySelector('[data-employee-communication-nav]');
         const incomingNav = incomingDocument.querySelector('[data-employee-communication-nav]');
         if (currentNav && incomingNav && currentNav.innerHTML !== incomingNav.innerHTML) {
             currentNav.innerHTML = incomingNav.innerHTML;
         }
     };

     const synchronizeAdminCards = (incomingDocument) => {
         document.querySelectorAll('.directory-card[data-admin-id]').forEach((card) => {
             const adminId = card.dataset.adminId || '';
             if (!adminId) return;

             const incomingCard = incomingDocument.querySelector(
                 `.directory-card[data-admin-id="${adminId}"]`
             );
             if (!incomingCard) return;

             const previousUnreadCount = Number(card.dataset.unreadCount || 0);
             const nextUnreadCount = Number(incomingCard.dataset.unreadCount || 0);
             card.dataset.unread = incomingCard.dataset.unread || 'false';
             card.dataset.unreadCount = String(nextUnreadCount);

             const currentNameRow = card.querySelector('[data-employee-admin-name-row]');
             const incomingNameBadge = incomingCard.querySelector('[data-employee-name-unread]');
             currentNameRow?.querySelector('[data-employee-name-unread]')?.remove();
             if (currentNameRow && incomingNameBadge) {
                 currentNameRow.appendChild(document.importNode(incomingNameBadge, true));
             }

             const currentConnect = card.querySelector('[data-chat-connect]');
             const incomingConnectBadge = incomingCard.querySelector('[data-employee-connect-unread]');
             currentConnect?.querySelector('[data-employee-connect-unread]')?.remove();
             if (currentConnect && incomingConnectBadge) {
                 currentConnect.prepend(document.importNode(incomingConnectBadge, true));
             }

             if (nextUnreadCount > previousUnreadCount) {
                 card.classList.add('ring-4', 'ring-rose-200');
                 window.setTimeout(() => card.classList.remove('ring-4', 'ring-rose-200'), 900);
             }
         });
     };

     const synchronizeEmployeeThread = (incomingDocument) => {
         const currentThread = document.getElementById('message-thread');
         const incomingThread = incomingDocument.getElementById('message-thread');
         if (!currentThread || !incomingThread) return;

         const currentMessageIds = Array.from(currentThread.querySelectorAll('[data-message-id]'))
             .map((message) => message.dataset.messageId || '')
             .join(',');
         const incomingMessageIds = Array.from(incomingThread.querySelectorAll('[data-message-id]'))
             .map((message) => message.dataset.messageId || '')
             .join(',');
         const currentImageSources = Array.from(currentThread.querySelectorAll('img[src]'))
             .map((image) => image.getAttribute('src') || '')
             .join(',');
         const incomingImageSources = Array.from(incomingThread.querySelectorAll('img[src]'))
             .map((image) => image.getAttribute('src') || '')
             .join(',');
         if (
             (currentMessageIds !== ''
                 && currentMessageIds === incomingMessageIds
                 && currentImageSources === incomingImageSources)
             || currentThread.innerHTML === incomingThread.innerHTML
         ) {
             return;
         }

         const wasNearBottom = currentThread.scrollHeight - currentThread.scrollTop - currentThread.clientHeight < 80;
         currentThread.innerHTML = incomingThread.innerHTML;
         if (wasNearBottom) {
             currentThread.scrollTop = currentThread.scrollHeight;
         }
     };

     const pollEmployeeCommunication = async () => {
         if (pollInFlight || document.hidden) return;
         if (document.querySelector('[data-chat-connect][data-loading="true"]')) return;
         if (document.querySelector('[data-chat-message-form][data-sending="true"]')) return;

         pollInFlight = true;
         try {
             const pollUrl = new URL(window.location.href);
             pollUrl.searchParams.set('background_poll', '1');
             pollUrl.hash = '';

             const response = await fetch(pollUrl, {
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
             synchronizeEmployeeSidebar(incomingDocument);
             synchronizeAdminCards(incomingDocument);
             synchronizeEmployeeThread(incomingDocument);
         } catch (error) {
             // Keep the page usable and try again on the next interval.
         } finally {
             pollInFlight = false;
         }
     };

     window.setInterval(pollEmployeeCommunication, 4000);
     document.addEventListener('visibilitychange', () => {
         if (!document.hidden) pollEmployeeCommunication();
     });
 })();
</script>
@include('components.chatImageUploadScript')
@include('components.chatEmojiPickerScript')
</body>
</html>
