<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class UtilityController extends Controller
{
    public function index()
    {
        return response()->json([
            'server_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
            ],
            'tenant_info' => [
                'tenant_id' => tenant('id'),
                'tenant_name' => tenant('name'),
                'domains' => tenant()->domains()->count(),
            ],
            'stats' => [
                'entries' => \DB::table('entries')->where('tenant_id', tenant('id'))->count(),
                'collections' => \DB::table('collections')->where('tenant_id', tenant('id'))->count(),
                'users' => \DB::table('tenant_users')->where('tenant_id', tenant('id'))->count(),
                'storage_mb' => round(\DB::table('assets')->where('tenant_id', tenant('id'))->sum('size') / 1024 / 1024, 2),
            ],
        ]);
    }

    public function clearCache()
    {
        Cache::flush();
        Artisan::call('view:clear');
        return response()->json(['message' => 'Cache cleared successfully.']);
    }

    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        Mail::raw('This is a test email from CMS V4.', function ($message) use ($request) {
            $message->to($request->input('email'))->subject('CMS V4 Test Email');
        });

        return response()->json(['message' => 'Test email sent.']);
    }

    public function failedJobs()
    {
        $jobs = \DB::table('failed_jobs')->orderByDesc('failed_at')->paginate(20);
        return response()->json($jobs);
    }

    public function retryFailedJob(string $id)
    {
        Artisan::call('queue:retry', ['id' => $id]);
        return response()->json(['message' => 'Job retried.']);
    }
}
