<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class LogController extends Controller
{
    public function index(Request $request)
    {
        // Default to today's date if no input is provided
        $date = $request->input('date', date('Y-m-d'));
        $filename = "laravel-{$date}.log";
        $path = storage_path("logs/{$filename}");

        if (!File::exists($path)) {
            return response()->json([
                'status' => 'error',
                'message' => "Log file for date {$date} not found.",
                'data' => []
            ], 404);
        }

        $content = File::get($path);

        // Regex for parsing Laravel log format: [timestamp] env.LEVEL: message
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)/';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $logs = [];
        foreach ($matches as $match) {
            $logs[] = [
                'timestamp' => $match[1],
                'env'       => $match[2],
                'level'     => $match[3],
                'message'   => trim($match[4]),
            ];
        }

        // Return latest logs first (reverse)
        return response()->json([
            'status' => 'success',
            'date' => $date,
            'data' => array_reverse($logs)
        ]);
    }

    public function download($date)
    {
        $filename = "laravel-{$date}.log";
        $path = storage_path("logs/{$filename}");

        if (File::exists($path)) {
            return Response::download($path);
        }

        return abort(404, 'File not found');
    }
}
