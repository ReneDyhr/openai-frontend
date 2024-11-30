<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * @property string $id
 * @property string $instructions
 * @property string $created_at
 * @property array $messages
 */
class Thread extends Model
{

    public static function create(string $message): Thread
    {
        $thread = OpenAI::threads()->createAndRun([
            'assistant_id' => env('OPENAI_ASSISTANT_ID'),
            'thread' => [
                'messages' =>
                    [
                        [
                            'role' => 'user',
                            'content' => $message,
                        ],
                    ],
            ],
        ]);

        $thread = new Thread([
            'id' => $thread->threadId,
            'created_at' => $thread->createdAt,
            'instructions' => $thread->instructions,
        ]);

        do {
            $allCompleted = true;
            $runs = OpenAI::threads()->runs()->list($thread->id);

            foreach ($runs->data as $run) {
                if ($run['status'] !== 'completed') {
                    $allCompleted = false;
                    break;
                }
            }

            if (!$allCompleted) {
                sleep(5); // Wait for 5 seconds before checking again
            }
        } while (!$allCompleted);

        $thread->loadMessages();

        return $thread;
    }

    public function loadMessages(): void
    {
        $messages = OpenAI::threads()->messages()->list($this->id, ['limit' => 100]);
        $this->messages = $messages->data;
        do {
            $messages = OpenAI::threads()->messages()->list($this->id, ['limit' => 100, 'after' => $messages->lastId]);
            $this->messages = array_merge($this->messages, $messages->data);
        } while ($messages->hasMore);
    }

    public function sendMessage(string $message)
    {
        $message = OpenAI::threads()->messages()->create($this->id, [
            'role' => 'user',
            'content' => $message,
        ]);

        $this->run();
        $this->activeRuns();
        $this->loadMessages();

        return OpenAI::threads()->messages()->retrieve($this->id, $message->id);
    }

    public function run()
    {
        return OpenAI::threads()->runs()->create($this->id, [
            'assistant_id' => env('OPENAI_ASSISTANT_ID'),
        ]);
    }

    public function activeRuns()
    {
        do {
            $allCompleted = true;
            $runs = OpenAI::threads()->runs()->list($this->id);

            foreach ($runs->data as $run) {
                if ($run->status !== 'completed') {
                    $allCompleted = false;
                    break;
                }
            }

            if (!$allCompleted) {
                sleep(5); // Wait for 5 seconds before checking again
            }
        } while (!$allCompleted);

        return !$allCompleted;
    }
}
