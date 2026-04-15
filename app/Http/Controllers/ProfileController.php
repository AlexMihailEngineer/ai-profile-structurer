<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Skill;
use App\Services\ProfileParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Models\ParsingTask;
use App\Jobs\ParseProfileJob;

class ProfileController extends Controller
{
    /**
     * Show the main dashboard/form where users paste the profile.
     */
    public function index(): Response
    {
        return Inertia::render('profile/create');
    }

    /**
     * Handle the raw text, send it to the LLM, and return the structured JSON.
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate(['raw_text' => 'required|string|min:50']);

        // 1. Create a tracking record
        $task = ParsingTask::create([
            'raw_text' => $request->input('raw_text'),
            'status' => 'pending'
        ]);

        // 2. Dispatch the job to Redis
        ParseProfileJob::dispatch($task);

        // 3. Immediately return the task ID to the React frontend
        return response()->json([
            'status' => 'success',
            'task_id' => $task->id,
        ]);
    }

    /**
     * The final step: Save the reviewed/corrected data to PostgreSQL.
     */
    public function store(Request $request)
    {
        // 1. Validate the reviewed data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'headline' => 'nullable|string|max:255',
            'about' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'raw_text' => 'required|string',
            'skills' => 'array',
            'experiences' => 'array',
            'experiences.*.company' => 'required|string',
            'experiences.*.title' => 'required|string',
            'experiences.*.description' => 'nullable|string',
            'experiences.*.start_date' => 'nullable|string',
            'experiences.*.end_date' => 'nullable|string',
        ]);

        // 2. Use a transaction to ensure data integrity
        DB::transaction(function () use ($validated) {
            // Create the main profile
            $profile = Profile::create([
                'name' => $validated['name'],
                'headline' => $validated['headline'],
                'about' => $validated['about'],
                'location' => $validated['location'],
                'raw_text' => $validated['raw_text'],
                // We store the 'clean' version for future ML training
                'parsed_json' => $validated,
            ]);

            // Create experiences via relationship
            foreach ($validated['experiences'] as $exp) {
                $profile->experiences()->create($exp);
            }

            // Handle skills (Many-to-Many)
            $skillIds = [];
            foreach ($validated['skills'] as $skillName) {
                // firstOrCreate prevents duplicate skills in the master list
                $skill = Skill::firstOrCreate([
                    'name' => strtolower(trim($skillName))
                ]);
                $skillIds[] = $skill->id;
            }

            // Attach skills to the profile in the pivot table
            $profile->skills()->sync($skillIds);
        });

        // 3. Redirect to a list view (or wherever you'd like to see the result)
        // return redirect()->route('profiles.index')
        //     ->with('message', 'profile structured and saved successfully.');
    }

    /**
     * New endpoint for the frontend to "poll" for results.
     */
    public function checkStatus(ParsingTask $task): JsonResponse
    {
        return response()->json([
            'status' => $task->status,
            'data' => $task->payload,
            'error' => $task->error_message
        ]);
    }
}
