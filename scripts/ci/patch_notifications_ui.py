from pathlib import Path

controller = Path('app/Http/Controllers/ProfileController.php')
text = controller.read_text(encoding='utf-8')
replacements = {
    "            ->where('recipient_id', $userId)\n            ->orderByDesc('created_at')": "            ->where('recipient_id', $userId)\n            ->whereNull('archived_at')\n            ->orderByDesc('created_at')",
    "            ->where('recipient_id', $userId)\n            ->whereNull('read_at')": "            ->where('recipient_id', $userId)\n            ->whereNull('archived_at')\n            ->whereNull('read_at')",
    "            ->where('recipient_id', auth()->id())\n            ->whereNull('read_at')": "            ->where('recipient_id', auth()->id())\n            ->whereNull('archived_at')\n            ->whereNull('read_at')",
}
for old, new in replacements.items():
    if old not in text:
        raise SystemExit(f'controller marker missing: {old}')
    text = text.replace(old, new)
controller.write_text(text, encoding='utf-8')

view = Path('resources/views/frontend/notifications.blade.php')
text = view.read_text(encoding='utf-8')

if '    display: block;\n    font-size: .98rem;' not in text:
    text = text.replace('.ntf-item-title {\n', '.ntf-item-title {\n    display: block;\n', 1)
if '    display: block;\n    font-size: .86rem;' not in text:
    text = text.replace('.ntf-item-body {\n', '.ntf-item-body {\n    display: block;\n', 1)

marker = "    var READ_ALL_URL = @json(route('user.notifications.read_all'));\n"
if marker not in text:
    raise SystemExit('READ_ALL_URL marker missing')
if 'var unreadCount = @json($unreadCount);' not in text:
    text = text.replace(marker, marker + "    var unreadCount = @json($unreadCount);\n", 1)

update_header = """    function updateHeaderBadge(count) {
        var bell = document.getElementById('notifBadge');
        if (!bell) return;

        if (count > 0) {
            bell.textContent = String(count);
            bell.style.display = '';
        } else {
            bell.textContent = '0';
            bell.style.display = 'none';
        }
    }
"""
if update_header not in text:
    raise SystemExit('updateHeaderBadge block missing')
if 'function updateUnreadUi(count)' not in text:
    update_ui = update_header + """
    function updateUnreadUi(count) {
        unreadCount = Math.max(0, Number(count) || 0);
        var chip = document.querySelector('.ntf-unread-chip');
        var markAllButton = document.getElementById('ntfMarkAllBtn');

        if (unreadCount > 0) {
            if (chip) chip.textContent = unreadCount + ' non ' + (unreadCount > 1 ? 'lues' : 'lue');
        } else {
            if (chip) chip.remove();
            if (markAllButton) markAllButton.remove();
        }

        updateHeaderBadge(unreadCount);
    }
"""
    text = text.replace(update_header, update_ui, 1)

local_remove = """        if (!url) {
            el.classList.remove('is-unread');
            return Promise.resolve();
        }
"""
if local_remove in text:
    text = text.replace(local_remove, """        if (!url) {
            el.classList.remove('is-unread');
            updateUnreadUi(unreadCount - 1);
            return Promise.resolve();
        }
""", 1)

remote_remove = """            if (!response.ok) throw new Error('Lecture non confirmée');
            el.classList.remove('is-unread');
"""
if remote_remove in text:
    text = text.replace(remote_remove, """            if (!response.ok) throw new Error('Lecture non confirmée');
            el.classList.remove('is-unread');
            updateUnreadUi(unreadCount - 1);
""", 1)

mark_all = """            var chip = document.querySelector('.ntf-unread-chip');
            if (chip) chip.remove();
            if (btn) btn.remove();
            updateHeaderBadge(0);
"""
if mark_all in text:
    text = text.replace(mark_all, """            updateUnreadUi(0);
""", 1)

view.write_text(text, encoding='utf-8')
