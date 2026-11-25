<?php

namespace App\Services\Catalog;

use App\Support\TokenGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The life of an uploaded product CSV between "preview" and "import": a random token names the
 * file (imports/<uuid>.csv on config('store.imports.disk')); only a well-formed token whose file
 * exists is accepted, which also rules out any path tricks.
 */
class ImportTokenManager
{
    private const FOLDER = 'imports';

    public function store(UploadedFile $file): string
    {
        $token = TokenGenerator::importToken();
        $file->storeAs(self::FOLDER, $token.'.csv', $this->disk());

        return $token;
    }

    /**
     * Runs $work with the file's path, then deletes the file whatever happens — the whole
     * "import once" lifecycle in one call. Unknown or malformed tokens give 404.
     *
     * @template T
     * @param  callable(string $path): T  $work
     * @return T
     */
    public function consume(mixed $token, callable $work): mixed
    {
        abort_unless($this->isValid($token), 404);

        try {
            return $work($this->path($token));
        } finally {
            $this->delete($token);
        }
    }

    public function isValid(mixed $token): bool
    {
        return TokenGenerator::isImportToken($token) && Storage::disk($this->disk())->exists($this->relative($token));
    }

    public function path(string $token): string
    {
        return Storage::disk($this->disk())->path($this->relative($token));
    }

    public function delete(string $token): void
    {
        if (TokenGenerator::isImportToken($token)) {
            Storage::disk($this->disk())->delete($this->relative($token));
        }
    }

    private function relative(string $token): string
    {
        return self::FOLDER.'/'.$token.'.csv';
    }

    private function disk(): string
    {
        return (string) config('store.imports.disk', 'local');
    }
}
