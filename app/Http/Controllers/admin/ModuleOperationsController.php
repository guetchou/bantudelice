<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\EnvConfigService;
use App\Services\OperationalHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ModuleOperationsController extends Controller
{
    protected $health;

    public function __construct(OperationalHealthService $health)
    {
        $this->health = $health;
    }

    public function index()
    {
        $reportsPath = base_path('docs/development-reports');

        return view('admin.modules.index', [
            'modules' => $this->health->modules(),
            'dependencies' => $this->health->dependencies(),
            'queues' => $this->health->queues(),
            'failedJobs' => $this->health->failedJobsOverview(),
            'workers' => $this->health->workers(),
            'developmentReportsPath' => $reportsPath,
            'developmentReports' => File::exists($reportsPath)
                ? collect(File::files($reportsPath))
                    ->sortByDesc(fn ($file) => $file->getFilename())
                    ->map(function ($file) {
                        $contents = File::get($file->getPathname());
                        preg_match('/^#\s+(.+)$/m', $contents, $titleMatch);
                        preg_match('/^- Date:\s+(.+)$/m', $contents, $dateMatch);

                        return [
                            'filename' => $file->getFilename(),
                            'path' => $file->getPathname(),
                            'title' => $titleMatch[1] ?? $file->getFilename(),
                            'date' => $dateMatch[1] ?? null,
                        ];
                    })
                    ->values()
                : collect(),
        ]);
    }

    public function update(Request $request)
    {
        $payload = [
            'MODULE_FOOD_ENABLED' => $request->boolean('food_enabled') ? 'true' : 'false',
            'MODULE_COLIS_ENABLED' => $request->boolean('colis_enabled') ? 'true' : 'false',
            'MODULE_TRANSPORT_ENABLED' => $request->boolean('transport_enabled') ? 'true' : 'false',
            'QUEUE_FOOD' => $request->input('queue_food', 'food'),
            'QUEUE_COLIS' => $request->input('queue_colis', 'colis'),
            'QUEUE_TRANSPORT' => $request->input('queue_transport', 'transport'),
        ];

        $result = EnvConfigService::updateEnvVariables($payload);

        if (! ($result['success'] ?? false)) {
            return redirect()->back()->withErrors([
                'modules' => $result['message'] ?? 'Impossible de mettre a jour la configuration.',
            ]);
        }

        Artisan::call('config:clear');
        Artisan::call('route:clear');

        return redirect()->route('admin.modules.index')->with('success', 'Configuration des modules mise a jour.');
    }

    public function retryFailedJob(int $jobId)
    {
        $failedJob = DB::table('failed_jobs')->where('id', $jobId)->first();

        if (! $failedJob) {
            return redirect()->route('admin.modules.index')->withErrors([
                'modules' => 'Failed job introuvable.',
            ]);
        }

        Artisan::call('queue:retry', [
            'id' => [$jobId],
        ]);

        return redirect()->route('admin.modules.index')->with('success', "Failed job #{$jobId} remis en file.");
    }
}
