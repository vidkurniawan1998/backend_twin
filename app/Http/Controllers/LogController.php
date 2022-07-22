<?php


namespace App\Http\Controllers;


use App\Http\Resources\LogResources;
use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $keyword = $request->keyword;
        $per_page = $request->per_page;
        $logs = Log::with('user')
                ->when($keyword <> '', function ($q) use ($keyword) {
                    return $q->where('action', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%");
                })->orderBy('id', 'desc');
        $logs = $per_page == 'all' ? $logs->get():$logs->paginate($per_page);
        return LogResources::collection($logs);
    }
}