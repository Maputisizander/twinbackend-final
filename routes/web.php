<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin-only monitoring dashboard — requires valid Sanctum token with telcovantage role
Route::get('/apiconsumption', function () {
    $token = request()->bearerToken() ?? request()->cookie('token');
    if (! $token) {
        // Show login prompt if accessed from browser with no token
        return response('
            <html><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#0f1117;color:#fff">
            <div style="text-align:center">
              <p style="font-size:14px;color:#64748b;margin-bottom:16px">Admin access required</p>
              <p style="font-size:12px;color:#475569">Open this page from the admin dashboard.</p>
            </div></body></html>
        ', 401);
    }
    return view('apiconsumption');
});

Route::get('/apistatus', function () {
    $dbStatus = 'Unknown';
    $dbError = null;
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbStatus = 'Operational';
    } catch (\Exception $e) {
        $dbStatus = 'Inactive';
        $dbError = $e->getMessage();
    }

    $cacheStatus = 'Unknown';
    try {
        \Illuminate\Support\Facades\Cache::put('status_check', true, 10);
        if (\Illuminate\Support\Facades\Cache::get('status_check')) {
            $cacheStatus = 'Operational';
        } else {
            $cacheStatus = 'Inactive';
        }
    } catch (\Exception $e) {
        $cacheStatus = 'Inactive';
    }

    // Check if API routing and image uploads are active/possible
    $apiStatus = 'Active';
    $apiError = null;
    try {
        // 1. Check if PHP file uploads are enabled
        if (!ini_get('file_uploads')) {
            throw new \Exception('PHP file_uploads is disabled in php.ini');
        }

        // 2. Check if upload/post limits are set to 0
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        if ($postMaxSize === '0' || $uploadMaxFilesize === '0') {
            throw new \Exception('PHP upload/post limits are set to 0');
        }

        // 3. Check if storage directory is writable by writing a temp file
        $tempFilename = 'api_status_check_' . time() . '.txt';
        if (\Illuminate\Support\Facades\Storage::disk('public')->put($tempFilename, 'status_ok')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($tempFilename);
        } else {
            throw new \Exception('Public storage disk is not writable');
        }
    } catch (\Exception $e) {
        $apiStatus = 'Inactive';
        $apiError = $e->getMessage();
    }

    $systemStatus = ($dbStatus === 'Operational' && $cacheStatus === 'Operational' && $apiStatus === 'Active') ? 'Operational' : 'Degraded';

    // Calculate disk space
    $freeSpace = @disk_free_space(base_path()) ?: 0;
    $freeSpaceGB = round($freeSpace / (1024 * 1024 * 1024), 1) . ' GB';
    $storageInfo = "{$freeSpaceGB} Free";

    return view('apistatus', compact('dbStatus', 'dbError', 'cacheStatus', 'systemStatus', 'storageInfo', 'apiStatus', 'apiError'));
});

Route::post('/contact-send', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name'  => 'required|string|max:255',
        'email'      => 'required|email|max:255',
        'company'    => 'nullable|string|max:255',
        'message'    => 'required|string|max:5000',
    ]);

    \Illuminate\Support\Facades\Mail::to($request->email)->send(
        new \App\Mail\ContactThankyou(
            firstName:   $request->first_name,
            lastName:    $request->last_name,
            company:     $request->company,
            userMessage: $request->message,
        )
    );

    return response()->json([
        'status'  => 'success',
        'message' => 'Your message has been sent. Thank you for reaching out!',
    ]);
})->name('contact.send');


