<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PeopleHub – HR Dashboard</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <style>
    body { font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, sans-serif; transition: margin-left 0.3s ease; }
    main { transition: margin-left 0.3s ease; }
    aside ~ main { margin-left: 16rem; }
  </style>
</head>
<body class="bg-slate-100">

<div class="flex min-h-screen">

  <!-- Sidebar -->
    @include('components.adminSideBar')


  <!-- Main Content -->
  <main class="flex-1 ml-16 transition-all duration-300">

    <!-- Header -->
     @include('components.adminHeader.interviewHeader')

    <!-- Dashboard Content -->
    <div class="p-8 space-y-6">
              <!-- Back -->
      <a href="{{ route('admin.adminInterview') }}" class="text-sm text-slate-500 flex items-center gap-2 mb-4">
        ← Back to Interviews
      </a>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

  <!-- LEFT COLUMN -->
  <div class="xl:col-span-2 space-y-6">

    <!-- Interview Card -->
    <div class="bg-white rounded-2xl border p-6">



      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <span class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-600 font-medium">
              Technical Interview
            </span>
            <span class="text-sm text-slate-500">Stage 2 of 3</span>
          </div>

          <h2 class="text-2xl font-bold text-slate-800">
            Interview with Sarah Mitchell
          </h2>
          <p class="text-slate-500">
            Senior Frontend Developer Position
          </p>
        </div>

        <!-- Avatar -->
        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-sky-400 to-indigo-500 flex items-center justify-center text-white font-bold text-lg">
          SM
        </div>
      </div>

      <!-- Info Row -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
        <div>
          <p class="text-sm text-slate-500">Date</p>
          <p class="font-semibold">Jan 15, 2024</p>
        </div>
        <div>
          <p class="text-sm text-slate-500">Time</p>
          <p class="font-semibold">10:00 AM</p>
        </div>
        <div>
          <p class="text-sm text-slate-500">Duration</p>
          <p class="font-semibold">60 minutes</p>
        </div>
        <div>
          <p class="text-sm text-slate-500">Status</p>
          <p class="font-semibold text-indigo-600">In Progress</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap gap-4 mt-6">

        <button class="px-6 py-3 rounded-xl border font-semibold text-slate-700 hover:bg-slate-50">
          Reschedule
        </button>
        <button class="px-6 py-3 rounded-xl border border-red-300 text-red-600 hover:bg-red-50 font-semibold">
          Cancel
        </button>
      </div>
    </div>

    <!-- Interview Notes -->
    <div class="bg-white rounded-2xl border p-6">
      <h3 class="text-lg font-semibold mb-4">Interview Notes</h3>

      <textarea
        class="w-full h-40 border rounded-xl p-4 text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        placeholder="Add notes about the interview..."
      ></textarea>

      <div class="flex items-center justify-between mt-4">
        <p class="text-sm text-slate-400">
          Notes are saved automatically
        </p>
        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-semibold">
          Save Notes
        </button>
      </div>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

<!-- INTERVIEW QUESTIONS -->
<div class="xl:col-span-3 space-y-6">

  <div class="bg-white rounded-2xl border p-6">
    <h3 class="text-lg font-semibold mb-6">Interview Questions</h3>

    <!-- Question 1 -->
    <div class="bg-slate-50 rounded-xl p-5 mb-6">
      <div class="flex items-center justify-between mb-3">
        <p class="font-semibold">
          1. Tell me about your experience with React and TypeScript
        </p>
        <span class="px-3 py-1 rounded-full text-xs bg-indigo-100 text-indigo-600 font-medium">
          Technical
        </span>
      </div>

      <textarea
        class="w-full min-h-[120px] max-h-[400px] border rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
        placeholder="Candidate's response..."
      ></textarea>

      <div class="flex items-center gap-2 mt-4 flex-wrap">
        <span class="text-sm text-slate-500 mr-2">Rating:</span>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">1</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">2</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">3</button>
        <button class="w-8 h-8 rounded-md bg-yellow-400 text-white font-semibold">4</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">5</button>
      </div>
    </div>

    <!-- Repeat similar changes for Question 2 and 3 -->
    <div class="bg-slate-50 rounded-xl p-5 mb-6">
      <div class="flex items-center justify-between mb-3">
        <p class="font-semibold">
          2. Describe a challenging project you worked on
        </p>
        <span class="px-3 py-1 rounded-full text-xs bg-purple-100 text-purple-600 font-medium">
          Behavioral
        </span>
      </div>

      <textarea
        class="w-full min-h-[120px] max-h-[400px] border rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
        placeholder="Candidate's response..."
      ></textarea>

      <div class="flex items-center gap-2 mt-4 flex-wrap">
        <span class="text-sm text-slate-500 mr-2">Rating:</span>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">1</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">2</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">3</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">4</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">5</button>
      </div>
    </div>

    <div class="bg-slate-50 rounded-xl p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="font-semibold">
          3. How do you handle code reviews and feedback?
        </p>
        <span class="px-3 py-1 rounded-full text-xs bg-emerald-100 text-emerald-600 font-medium">
          Soft Skills
        </span>
      </div>

      <textarea
        class="w-full min-h-[120px] max-h-[400px] border rounded-xl p-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
        placeholder="Candidate's response..."
      ></textarea>

      <div class="flex items-center gap-2 mt-4 flex-wrap">
        <span class="text-sm text-slate-500 mr-2">Rating:</span>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">1</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">2</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">3</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">4</button>
        <button class="w-8 h-8 rounded-md bg-slate-200 text-sm">5</button>
      </div>
    </div>

  </div>
</div>

</div>
</div>



  <!-- RIGHT COLUMN -->
  <div class="space-y-6">

    <!-- Candidate Info -->
    <div class="bg-white rounded-2xl border p-6">
      <h3 class="font-semibold mb-4">Candidate Information</h3>

      <div class="space-y-3 text-sm">
        <div>
          <p class="text-slate-500">Email</p>
          <p class="font-medium">sarah.m@email.com</p>
        </div>
        <div>
          <p class="text-slate-500">Phone</p>
          <p class="font-medium">+1 (555) 123-4567</p>
        </div>
        <div>
          <p class="text-slate-500">Location</p>
          <p class="font-medium">San Francisco, CA</p>
        </div>
        <div>
          <p class="text-slate-500">Experience</p>
          <p class="font-medium">7 years</p>
        </div>
        <div>
          <p class="text-slate-500">Current Rating</p>
          <div class="text-yellow-400">
            ★ ★ ★ ★ ★
          </div>
        </div>
      </div>

      <button class="w-full mt-4 border border-indigo-300 text-indigo-600 py-2 rounded-lg font-semibold hover:bg-indigo-50">
        View Full Profile
      </button>
    </div>

    <!-- Interview Panel -->
    <div class="bg-white rounded-2xl border p-6">
      <h3 class="font-semibold mb-4">Interview Panel</h3>

      <div class="space-y-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
            JD
          </div>
          <div>
            <p class="font-medium">John Doe</p>
            <p class="text-sm text-slate-500">Engineering Manager</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">
            JS
          </div>
          <div>
            <p class="font-medium">Jane Smith</p>
            <p class="text-sm text-slate-500">Senior Developer</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Meeting Details -->
    <div class="bg-white rounded-2xl border p-6">
      <h3 class="font-semibold mb-4">Meeting Details</h3>
      <ul class="space-y-3 text-sm text-slate-600">
        <li class="flex items-center gap-2">
          <i class="fa-solid fa-video"></i> Video Conference
        </li>
        <li class="flex items-center gap-2 text-indigo-600 cursor-pointer">
          <i class="fa-solid fa-link"></i> Copy Meeting Link
        </li>
        <li class="flex items-center gap-2">
          <i class="fa-solid fa-calendar"></i> Calendar Invite Sent
        </li>
      </ul>
    </div>
      <!-- OVERALL EVALUATION -->
  <div class="space-y-6">

    <div class="bg-white rounded-2xl border p-6">
      <h3 class="font-semibold mb-6">Overall Evaluation</h3>

      <!-- Technical Skills -->
      <div class="mb-5">
        <div class="flex justify-between text-sm mb-1">
          <span>Technical Skills</span>
          <span class="font-semibold">4/5</span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-200">
          <div class="h-2 rounded-full bg-indigo-500 w-4/5"></div>
        </div>
      </div>

      <!-- Communication -->
      <div class="mb-5">
        <div class="flex justify-between text-sm mb-1">
          <span>Communication</span>
          <span class="font-semibold">5/5</span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-200">
          <div class="h-2 rounded-full bg-emerald-500 w-full"></div>
        </div>
      </div>

      <!-- Problem Solving -->
      <div class="mb-6">
        <div class="flex justify-between text-sm mb-1">
          <span>Problem Solving</span>
          <span class="font-semibold">4/5</span>
        </div>
        <div class="w-full h-2 rounded-full bg-slate-200">
          <div class="h-2 rounded-full bg-purple-500 w-4/5"></div>
        </div>
      </div>

      <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-semibold">
        Submit Evaluation
      </button>
    </div>

  </div>

  </div>

</div>




    </div>
  </main>
</div>

</body>
</html>


