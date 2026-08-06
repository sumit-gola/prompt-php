<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Prompt;
use App\Models\PromptGenerationJob;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->adminView('admin/dashboard', [
            'title' => 'Admin dashboard',
            'stats' => Prompt::stats(),
            'recent' => Prompt::adminSearch(['sort' => 'newest'], 1, 8)['items'],
            'pendingJobs' => PromptGenerationJob::pendingCount(),
        ]);
    }
}

