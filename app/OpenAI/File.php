<?php

namespace App\OpenAI;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * @property string $id
 * @property ?int $bytes
 * @property string $created_at
 * @property string $filename
 * @property string $status
 * @property string $vector_status
 * @property string $vector_last_error
 */
class File extends Model
{

    public static function getVectorStore()
    {
        return OpenAI::vectorStores()->retrieve(env('OPENAI_VECTOR_STORE_ID'));
    }

    public static function getFiles()
    {
        $cacheVectorKey = 'openai_vector_files';

        $allVectorFiles = Cache::remember($cacheVectorKey, env('CACHE_TTL', 600), function () {
            $allVectorFiles = [];
            $limit = 100;

            $vectorFiles = OpenAI::vectorStores()->files()->list(env('OPENAI_VECTOR_STORE_ID'), ['limit' => $limit]);
            /**
             * @var array<id: string, object: string, created_at: int, bytes: ?int, filename: string, purpose: string, status: string, status_details: array<array-key, mixed>|string|null> $allVectorFiles
             */
            $allVectorFiles = array_merge($allVectorFiles, $vectorFiles->data);
            do {
                $vectorFiles = OpenAI::vectorStores()->files()->list(env('OPENAI_VECTOR_STORE_ID'), ['limit' => $limit, 'after' => $vectorFiles->lastId]);
                $allVectorFiles = array_merge($allVectorFiles, $vectorFiles->data);
            } while ($vectorFiles->hasMore);

            return $allVectorFiles;
        });

        $cacheKey = 'openai_files';
        $files = Cache::remember($cacheKey, env('CACHE_TTL', 600), function () {
            return OpenAI::files()->list();
        });

        $models = [];
        foreach ($allVectorFiles as $vectorFile) {
            foreach ($files->data as $file) {
                if ($vectorFile->id === $file->id) {
                    $models[] = new self([
                        'id' => $file->id,
                        'bytes' => $file->bytes,
                        'created_at' => $file->createdAt,
                        'filename' => $file->filename,
                        'status' => $file->status,
                        'vector_status' => $vectorFile->status,
                        'vector_last_error' => $vectorFile->lastError,
                    ]);
                    break;
                }
            }
        }
        return $models;
    }

    public static function findFile(string $filename)
    {
        $files = self::getFiles();
        foreach ($files as $file) {
            if ($file->filename === $filename) {
                return new self([
                    'id' => $file->id,
                    'bytes' => $file->bytes,
                    'created_at' => $file->created_at,
                    'filename' => $file->filename,
                    'status' => $file->status,
                    'vector_status' => $file->vector_status,
                    'vector_last_error' => $file->vector_last_error,
                ]);
            }
        }
        return null;
    }

    public static function findFileById(string $id)
    {
        $files = self::getFiles();
        foreach ($files as $file) {
            if ($file->id === $id) {
                return new self([
                    'id' => $file->id,
                    'bytes' => $file->bytes,
                    'created_at' => $file->created_at,
                    'filename' => $file->filename,
                    'status' => $file->status,
                    'vector_status' => $file->vector_status,
                    'vector_last_error' => $file->vector_last_error,
                ]);
            }
        }
        return null;
    }

    public function delete()
    {
        OpenAI::vectorStores()->files()->delete(env('OPENAI_VECTOR_STORE_ID'), $this->id);
        $file = OpenAI::files()->delete($this->id);

        Cache::delete('openai_files');
        Cache::delete('openai_vector_files');

        return $file;
    }

    public static function create(string $filePath, bool $overwrite = true)
    {
        $filename = basename($filePath);

        $check = self::findFile($filename);
        if ($check && !$overwrite) {
            throw new \Exception('File already exists');
        } elseif ($check && $overwrite) {
            $check->delete();
        }
        $file = OpenAI::files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($filePath, 'r'),
        ]);

        OpenAI::vectorStores()->files()->create(env('OPENAI_VECTOR_STORE_ID'), ['file_id' => $file->id]);

        do {
            $vectorFile = OpenAI::vectorStores()->files()->retrieve(env('OPENAI_VECTOR_STORE_ID'), $file->id);
            sleep(5);
        } while ($vectorFile->status !== 'completed');

        Cache::delete('openai_files');
        Cache::delete('openai_vector_files');

        return new self([
            'id' => $file->id,
            'bytes' => $file->bytes,
            'created_at' => $file->createdAt,
            'filename' => $file->filename,
            'status' => $file->status,
            'vector_status' => 'processed',
            'vector_last_error' => null,
        ]);
    }
}
