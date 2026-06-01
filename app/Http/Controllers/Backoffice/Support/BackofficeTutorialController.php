<?php

namespace App\Http\Controllers\Backoffice\Support;

use App\Actions\Support\ManageTutorialAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreTutorialRequest;
use App\Http\Requests\Support\UpdateTutorialRequest;
use App\Models\Support\Tutorial;
use App\Models\Support\TutorialCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BackofficeTutorialController extends Controller
{
    public function index(): Response
    {
        $tutorials = Tutorial::on('support')
            ->with('category')
            ->orderBy('position')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Backoffice/Support/Tutorials/Index', [
            'tutorials' => $tutorials,
            'categories' => TutorialCategory::on('support')->where('active', true)->orderBy('position')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Backoffice/Support/Tutorials/Form', [
            'categories' => TutorialCategory::on('support')->where('active', true)->orderBy('position')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTutorialRequest $request, ManageTutorialAction $action): RedirectResponse
    {
        $tutorial = $action->create($request);

        return redirect()->route('platform.support.tutorials.edit', $tutorial->id)
            ->with('success', 'Tutorial criado.');
    }

    public function edit(string $tutorialId): Response
    {
        $tutorial = Tutorial::on('support')->where('id', $tutorialId)->with('category')->firstOrFail();

        return Inertia::render('Backoffice/Support/Tutorials/Form', [
            'tutorial' => $tutorial,
            'categories' => TutorialCategory::on('support')->where('active', true)->orderBy('position')->get(['id', 'name']),
        ]);
    }

    public function update(string $tutorialId, UpdateTutorialRequest $request, ManageTutorialAction $action): RedirectResponse
    {
        $tutorial = Tutorial::on('support')->where('id', $tutorialId)->firstOrFail();

        $action->update($tutorial, $request);

        return back()->with('success', 'Tutorial atualizado.');
    }

    public function destroy(string $tutorialId): RedirectResponse
    {
        $tutorial = Tutorial::on('support')->where('id', $tutorialId)->firstOrFail();

        if ($tutorial->featured_image) {
            Storage::disk('public')->delete($tutorial->featured_image);
        }

        $tutorial->delete();

        return redirect()->route('platform.support.tutorials.index')
            ->with('success', 'Tutorial excluído.');
    }

    public function togglePublished(string $tutorialId, ManageTutorialAction $action): RedirectResponse
    {
        $tutorial = Tutorial::on('support')->where('id', $tutorialId)->firstOrFail();

        if ($tutorial->published) {
            $action->unpublish($tutorial);
        } else {
            $action->publish($tutorial);
        }

        return back()->with('success', $tutorial->published ? 'Tutorial publicado.' : 'Tutorial despublicado.');
    }
}
