<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Mask;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FailedJobController extends Controller
{
    public function index(): Response
    {
        $jobs = DB::table('failed_jobs')
            ->latest('failed_at')
            ->paginate(20)
            ->through(fn ($job) => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'payload' => Mask::arraySensitive(json_decode($job->payload, true) ?: []),
                'exception_summary' => str((string) $job->exception)->limit(500)->toString(),
                'failed_at' => $job->failed_at,
            ]);

        return Inertia::render('Admin/FailedJobs/Index', [
            'jobs' => $jobs,
        ]);
    }
}

