<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Jobs\TestJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestController extends Controller
{
    public function test()
    {
        $checks = [];

        try {
            Session::put('test', 10);

            if (Session::get('test') != 10) {
                throw new \Exception('Failed to match session value');
            }

            $checks['session'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['session'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            $checks['assets'] = ['status' => 'ok', 'files' => [asset('css/app.css'), asset('robots.txt')]];
        } catch (\Exception $e) {
            $checks['assets'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            Cache::put('test', 10);

            if (Cache::get('test') != 10) {
                throw new \Exception('Failed to match session value');
            }

            $checks['cache'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            Test::query()->updateOrCreate(['test' => Str::random(16)]);
            Test::query()->delete();

            $checks['db'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['db'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            $message = Str::random(16);
            TestJob::dispatch($message);
            sleep(5);
            Test::query()->where('test', $message)->firstOrFail();
            Test::query()->delete();
            $checks['job'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $checks['job'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            $checks['view'] = ['status' => 'ok', 'html' => view('sample')->render()];
        } catch (\Exception $e) {
            $checks['view'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            $checks['http'] = ['status' => 'ok', 'html' => Http::get('https://example.com')->body()];
        } catch (\Exception $e) {
            $checks['http'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        try {
            Storage::disk('s3')->put('test.txt', 'hello world');

            $checks['disk'] = ['status' => 'ok', 'content' => Storage::disk('s3')->get('test.txt')];
        } catch (\Exception $e) {
            $checks['disk'] = ['status' => 'failed', 'reason' => $e->getMessage()];
        }

        return response()->json($checks);
    }
}
