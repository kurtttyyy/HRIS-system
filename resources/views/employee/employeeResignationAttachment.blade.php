<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resignation Letter Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <div class="flex min-h-screen">
        @include('components.employeeSideBar')

        <main class="ml-16 flex-1 p-4 transition-all duration-300 md:p-8">
            <section class="flex min-h-[calc(100vh-4rem)] flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 md:flex-row md:items-center md:justify-between md:px-6">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Resignation Letter</p>
                        <h1 class="mt-1 truncate text-2xl font-black text-slate-900">{{ $resignation->attachment_name ?: 'Uploaded resignation file' }}</h1>
                    </div>

                    <a href="{{ route('employee.employeeResignation', array_filter(['tab_session' => request()->query('tab_session')])) }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <i class="fa fa-arrow-left"></i>
                        Back
                    </a>
                </div>

                <div class="min-h-0 flex-1 bg-slate-50 p-3 md:p-5">
                    @if(($previewMode ?? 'iframe') === 'iframe')
                        <object
                            data="{{ route('employee.resignationAttachment.view', $resignation->id) }}"
                            type="application/pdf"
                            class="h-[calc(100vh-11rem)] w-full rounded-2xl border border-slate-200 bg-white"
                        >
                            <div class="flex h-[calc(100vh-11rem)] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 text-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                    <i class="fa fa-file-pdf-o text-2xl"></i>
                                </div>
                                <h2 class="mt-4 text-xl font-black text-slate-900">PDF preview unavailable</h2>
                                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Your browser could not display this PDF inside the page.</p>
                            </div>
                        </object>
                    @elseif(!empty($previewText))
                        <div class="h-[calc(100vh-11rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm md:px-10 md:py-8">
                            <div class="mx-auto max-w-4xl whitespace-pre-wrap text-sm leading-7 text-slate-700 md:text-base">{{ $previewText }}</div>
                        </div>
                    @else
                        <div class="flex h-[calc(100vh-11rem)] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white px-6 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-600">
                                <i class="fa fa-file-text-o text-2xl"></i>
                            </div>
                            <h2 class="mt-4 text-xl font-black text-slate-900">Preview unavailable</h2>
                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">{{ $previewError ?? 'This file cannot be previewed in the browser.' }}</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>

    <script>
        const sidebar = document.querySelector('aside');
        const main = document.querySelector('main');
        if (sidebar && main) {
            sidebar.addEventListener('mouseenter', function() {
                main.classList.remove('ml-16');
                main.classList.add('ml-64');
            });
            sidebar.addEventListener('mouseleave', function() {
                main.classList.remove('ml-64');
                main.classList.add('ml-16');
            });
        }
    </script>
</body>
</html>
