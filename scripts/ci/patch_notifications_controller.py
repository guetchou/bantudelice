from pathlib import Path

path = Path('app/Http/Controllers/ProfileController.php')
text = path.read_text(encoding='utf-8')
start = text.index('    public function notifications(')
end = text.index('    protected function resolveProfileDashboardAccess(', start)
block = text[start:end]

block = block.replace(
    "            ->where('recipient_id', $userId)\n            ->orderByDesc('created_at')",
    "            ->where('recipient_id', $userId)\n            ->whereNull('archived_at')\n            ->orderByDesc('created_at')",
)
block = block.replace(
    "            ->where('recipient_id', $userId)\n            ->whereNull('read_at')",
    "            ->where('recipient_id', $userId)\n            ->whereNull('archived_at')\n            ->whereNull('read_at')",
)
block = block.replace(
    "            ->where('recipient_id', auth()->id())\n            ->whereNull('read_at')",
    "            ->where('recipient_id', auth()->id())\n            ->whereNull('archived_at')\n            ->whereNull('read_at')",
)

if block.count("->whereNull('archived_at')") < 4:
    raise SystemExit('archived filters missing')

path.write_text(text[:start] + block + text[end:], encoding='utf-8')
