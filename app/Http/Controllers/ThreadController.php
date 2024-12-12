<?php

namespace App\Http\Controllers;

use App\Models\Thread;
use App\OpenAI\Thread as OpenAIThread;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function store(Request $request)
    {
        // Validate message
        $request->validate([
            'message' => 'required|string',
        ]);

        $thread = OpenAIThread::create($request->input('message'))->save();
        return response()->json($thread, 201);
    }

    public function show($id)
    {
        $modelThread = Thread::with('messages')->get()->find($id);
        if (!$modelThread) {
            return response()->json(['message' => 'Thread not found'], 404);
        }

        return response()->json($modelThread);
    }
}
