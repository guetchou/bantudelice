<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Guide d'exécution du projet — lecture et écriture d'un fichier JSON local.
 * Aucune écriture DB. Dépendance : système de fichiers uniquement.
 */
class GuidanceController extends Controller
{
    public function executionGuide(): View
    {
        $guidancePath = base_path('guidance.md');

        if (! File::exists($guidancePath)) {
            $fallbackPath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'guidance.md';
            if (File::exists($fallbackPath)) {
                $guidancePath = $fallbackPath;
            }
        }

        $markdown = File::exists($guidancePath)
            ? File::get($guidancePath)
            : "# Guidance execution\n\n- guidance.md introuvable";

        $pageTitle = 'Guidance execution';
        if (preg_match('/^#\s+(.+)$/m', $markdown, $titleMatch)) {
            $pageTitle = trim($titleMatch[1]);
        }

        $sections       = $this->parseExecutionGuideSections($markdown);
        $checklistCount = collect($sections)->sum(fn ($s) => (int) ($s['item_count'] ?? 0));

        return view('frontend.execution_guide', [
            'pageTitle'          => $pageTitle,
            'sections'           => $sections,
            'checklistCount'     => $checklistCount,
            'guidancePath'       => $guidancePath,
            'teamState'          => $this->readExecutionGuideState(),
            'developerProfiles'  => $this->executionGuideDeveloperProfiles(),
        ]);
    }

    public function updateExecutionGuideTask(Request $request): JsonResponse
    {
        $data    = $request->validate([
            'item_key' => 'required|string|max:190',
            'status'   => 'required|in:todo,started,in_progress,blocked,done',
            'assignee' => 'nullable|string|max:120',
            'note'     => 'nullable|string|max:2000',
        ]);

        $state   = $this->readExecutionGuideState();
        $items   = $state['items'] ?? [];
        $itemKey = $data['item_key'];

        $items[$itemKey] = [
            'status'     => $data['status'],
            'assignee'   => trim((string) ($data['assignee'] ?? '')),
            'note'       => trim((string) ($data['note'] ?? '')),
            'updated_at' => now()->toIso8601String(),
        ];

        if (
            $items[$itemKey]['status'] === 'todo'
            && $items[$itemKey]['assignee'] === ''
            && $items[$itemKey]['note'] === ''
        ) {
            unset($items[$itemKey]);
        }

        $state['items']      = $items;
        $state['updated_at'] = now()->toIso8601String();
        $this->writeExecutionGuideState($state);

        return response()->json([
            'ok'   => true,
            'item' => $items[$itemKey] ?? [
                'status'     => 'todo',
                'assignee'   => '',
                'note'       => '',
                'updated_at' => null,
            ],
        ]);
    }

    public function resetExecutionGuideState(): JsonResponse
    {
        $state = [
            'items'      => [],
            'updated_at' => now()->toIso8601String(),
        ];

        $this->writeExecutionGuideState($state);

        return response()->json(['ok' => true, 'state' => $state]);
    }

    // -------------------------------------------------------------------------
    // Helpers privés — parsing markdown et état JSON
    // -------------------------------------------------------------------------

    private function parseExecutionGuideSections(string $markdown): array
    {
        preg_match_all('/^##\s+([^\r\n]+)\R(.*?)(?=^##\s+[^\r\n]+\R|\z)/ms', $markdown, $matches, PREG_SET_ORDER);

        $sections = [];
        foreach ($matches as $match) {
            $title     = trim($match[1]);
            $body      = trim($match[2]);
            $anchor    = Str::slug($title);
            $blocks    = $this->parseExecutionGuideBlocks($body, $anchor);
            $itemCount = 0;

            foreach ($blocks as $block) {
                if ($block['type'] === 'checklist') {
                    $itemCount += count($block['items']);
                }
            }

            $sections[] = [
                'title'      => $title,
                'anchor'     => $anchor,
                'blocks'     => $blocks,
                'item_count' => $itemCount,
            ];
        }

        return $sections;
    }

    private function parseExecutionGuideBlocks(string $body, string $sectionAnchor): array
    {
        $lines          = preg_split("/\R/", $body);
        $blocks         = [];
        $paragraphLines = [];
        $checklistItems = [];
        $itemSequence   = 0;

        $flushParagraph = function () use (&$blocks, &$paragraphLines): void {
            if (empty($paragraphLines)) return;
            $blocks[]       = ['type' => 'paragraph', 'text' => trim(implode("\n", $paragraphLines))];
            $paragraphLines = [];
        };

        $flushChecklist = function () use (&$blocks, &$checklistItems): void {
            if (empty($checklistItems)) return;
            $blocks[]       = ['type' => 'checklist', 'items' => array_values($checklistItems)];
            $checklistItems = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || $trimmed === '---') {
                $flushParagraph();
                $flushChecklist();
                continue;
            }

            if (preg_match('/^###\s+(.+)$/', $trimmed, $headingMatch)) {
                $flushParagraph();
                $flushChecklist();
                $blocks[] = ['type' => 'subheading', 'text' => trim($headingMatch[1]), 'anchor' => Str::slug($headingMatch[1])];
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $itemMatch) || preg_match('/^\d+\.\s+(.+)$/', $trimmed, $itemMatch)) {
                $flushParagraph();
                $itemSequence++;
                $checklistItems[] = ['key' => $sectionAnchor . '--' . $itemSequence, 'text' => trim($itemMatch[1])];
                continue;
            }

            $flushChecklist();
            $paragraphLines[] = $trimmed;
        }

        $flushParagraph();
        $flushChecklist();

        return $blocks;
    }

    private function executionGuideStatePath(): string
    {
        return storage_path('app/execution-guide-state.json');
    }

    private function executionGuideDeveloperProfiles(): array
    {
        return ['Non assigne', 'Dev A', 'Dev B', 'Dev C', 'Dev D', 'QA'];
    }

    private function readExecutionGuideState(): array
    {
        $path = $this->executionGuideStatePath();

        if (! File::exists($path)) {
            return ['items' => [], 'updated_at' => null];
        }

        $decoded = json_decode(File::get($path), true);

        if (! is_array($decoded)) {
            return ['items' => [], 'updated_at' => null];
        }

        return [
            'items'      => is_array($decoded['items'] ?? null) ? $decoded['items'] : [],
            'updated_at' => $decoded['updated_at'] ?? null,
        ];
    }

    private function writeExecutionGuideState(array $state): void
    {
        $path = $this->executionGuideStatePath();
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true, true);
        }
        File::put($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
