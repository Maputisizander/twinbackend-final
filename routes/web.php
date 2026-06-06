<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/apiconsumption', function () {
    $password = request()->query('key') ?? request()->cookie('dev_key');
    if (hash('sha256', $password ?? '') !== hash('sha256', env('DEV_CONSOLE_KEY', ''))) {
        return response('
            <html><head><title>TelcoVantage Dev Console</title>
            <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;background:#0f1117;color:#fff}
            .card{background:#1e2433;border:1px solid #2a3144;border-radius:16px;padding:40px;width:100%;max-width:360px;text-align:center}
            h2{font-size:16px;font-weight:700;color:#e2e8f0;margin-bottom:8px}p{font-size:12px;color:#64748b;margin-bottom:24px}
            input{width:100%;background:#0f1117;border:1px solid #2a3144;border-radius:8px;padding:10px 14px;color:#fff;font-size:13px;outline:none;margin-bottom:12px}
            button{width:100%;background:#0A5C3B;border:none;border-radius:8px;padding:10px;color:#fff;font-size:13px;font-weight:700;cursor:pointer}</style></head>
            <body><div class="card">
            <h2>TelcoVantage Dev Console</h2><p>Enter access password</p>
            <form method="GET"><input type="password" name="key" placeholder="Password" autofocus/><button type="submit">Access</button></form>
            </div></body></html>
        ', 401)->withCookie(cookie()->forget('dev_key'));
    }
    return response(view('apiconsumption'))->withCookie(cookie('dev_key', $password, 60 * 8));
});

Route::get('/clear-cache', function () {
    $password = request()->query('key') ?? request()->cookie('dev_key');
    if (hash('sha256', $password ?? '') !== hash('sha256', env('DEV_CONSOLE_KEY', ''))) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $results = [];

    try { \Artisan::call('view:clear');   $results['view_cache']   = 'cleared'; } catch (\Exception $e) { $results['view_cache']   = $e->getMessage(); }
    try { \Artisan::call('cache:clear');  $results['app_cache']    = 'cleared'; } catch (\Exception $e) { $results['app_cache']    = $e->getMessage(); }
    try { \Artisan::call('config:clear'); $results['config_cache'] = 'cleared'; } catch (\Exception $e) { $results['config_cache'] = $e->getMessage(); }
    try { \Artisan::call('route:clear');  $results['route_cache']  = 'cleared'; } catch (\Exception $e) { $results['route_cache']  = $e->getMessage(); }

    return response()->json([
        'status'  => 'ok',
        'message' => 'All caches cleared.',
        'results' => $results,
        'time'    => now()->toDateTimeString(),
    ])->withCookie(cookie('dev_key', $password, 60 * 8));
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


