<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * @property string $id
 * @property string $bytes
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
        $allVectorFiles = [];
        $limit = 100;

        $vectorFiles = OpenAI::vectorStores()->files()->list(env('OPENAI_VECTOR_STORE_ID'), ['limit' => $limit]);
        $allVectorFiles = array_merge($allVectorFiles, $vectorFiles->data);
        do {
            $vectorFiles = OpenAI::vectorStores()->files()->list(env('OPENAI_VECTOR_STORE_ID'), ['limit' => $limit, 'after' => $vectorFiles->lastId]);
            $allVectorFiles = array_merge($allVectorFiles, $vectorFiles->data);
        } while ($vectorFiles->hasMore);

        $files = OpenAI::files()->list();
        $models = [];
        foreach ($vectorFiles->data as $vectorFile) {
            foreach ($files->data as $file) {
                if ($vectorFile->id === $file->id) {
                    $models[] = new File([
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
                return $file;
            }
        }
        return null;
    }
}
